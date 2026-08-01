<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * Maximum age in seconds for the session-backed launch handoff between view.php and auth.php.
 */
const TINY_ZOOMCLASSROOM_LAUNCH_STATE_TTL = 600;

/**
 * Prefix for publicly stored embed identifiers.
 */
const TINY_ZOOMCLASSROOM_EMBED_PUBLICID_PREFIX = 'tzc_';

/**
 * Validate a configured allowed-domain entry.
 *
 * Supported formats:
 * - example.com
 * - *.example.com
 *
 * @param string $pattern
 * @return string
 */
function tiny_zoomclassroom_validate_allowed_domain_pattern(string $pattern): string {
    $pattern = strtolower(trim($pattern));
    if ($pattern === '') {
        return '';
    }

    if (!preg_match('/^(\\*\\.)?[a-z0-9-]+(\\.[a-z0-9-]+)+$/', $pattern)) {
        throw new moodle_exception('invalidalloweddomain', 'tiny_zoomclassroom', '', $pattern);
    }

    return $pattern;
}

/**
 * Get the configured allowed launch domains.
 *
 * @return array
 */
function tiny_zoomclassroom_get_allowed_domain_patterns(): array {
    $configured = (string)get_config('tiny_zoomclassroom', 'alloweddomains');
    $patterns = [];

    if ($configured !== '') {
        $lines = preg_split('/\r\n|\r|\n/', $configured);
        foreach ($lines as $line) {
            $pattern = tiny_zoomclassroom_validate_allowed_domain_pattern($line);
            if ($pattern !== '') {
                $patterns[$pattern] = true;
            }
        }
    }

    if (empty($patterns)) {
        $patterns['*.zoom.us'] = true;
    }

    return array_keys($patterns);
}

/**
 * Determine whether a hostname matches a configured domain pattern.
 *
 * @param string $host
 * @param string $pattern
 * @return bool
 */
function tiny_zoomclassroom_host_matches_allowed_pattern(string $host, string $pattern): bool {
    if (strpos($pattern, '*.') === 0) {
        $suffix = substr($pattern, 1);
        return str_ends_with($host, $suffix);
    }

    return $host === $pattern;
}

/**
 * Validate that a returned LTI launch URL is HTTPS and on an allowed Zoom host.
 *
 * @param string $url
 * @return string
 */
function tiny_zoomclassroom_validate_zoom_url(string $url): string {
    $cleanurl = clean_param($url, PARAM_URL);
    if ($cleanurl === '') {
        return '';
    }

    $parts = parse_url($cleanurl);
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $host = strtolower(trim((string)($parts['host'] ?? '')));

    if ($scheme !== 'https' || $host === '') {
        throw new moodle_exception('invalidtoolurl', 'tiny_zoomclassroom');
    }

    $allowedpatterns = tiny_zoomclassroom_get_allowed_domain_patterns();
    foreach ($allowedpatterns as $pattern) {
        if (tiny_zoomclassroom_host_matches_allowed_pattern($host, $pattern)) {
            return $cleanurl;
        }
    }

    throw new moodle_exception('invalidtoolurl', 'tiny_zoomclassroom');
}

/**
 * Build the canonical Zoom Classroom deep-linking URI for a validated launch URL.
 *
 * @param string $url
 * @return string
 */
function tiny_zoomclassroom_build_canonical_deeplink_url(string $url): string {
    $validatedurl = tiny_zoomclassroom_validate_zoom_url($url);
    $parts = parse_url($validatedurl);
    $scheme = strtolower((string)($parts['scheme'] ?? 'https'));
    $host = strtolower((string)($parts['host'] ?? ''));
    $port = isset($parts['port']) ? ':' . (int)$parts['port'] : '';

    if ($host === '') {
        throw new moodle_exception('invalidtoolurl', 'tiny_zoomclassroom');
    }

    return $scheme . '://' . $host . $port . '/classroom/lti/advantage';
}

/**
 * Normalize Zoom Classroom tool configuration returned from dynamic registration.
 *
 * @param stdClass $config
 * @return stdClass
 */
function tiny_zoomclassroom_normalize_registered_tool_config(stdClass $config): stdClass {
    $candidateurl = (string)($config->lti_toolurl_ContentItemSelectionRequest ?? '');
    if ($candidateurl === '') {
        $candidateurl = (string)($config->toolurl_ContentItemSelectionRequest ?? '');
    }
    if ($candidateurl === '') {
        $candidateurl = (string)($config->lti_toolurl ?? '');
    }
    if ($candidateurl === '') {
        $candidateurl = (string)($config->lti_initiatelogin ?? '');
    }

    if ($candidateurl === '') {
        return $config;
    }

    try {
        $canonicalurl = tiny_zoomclassroom_build_canonical_deeplink_url($candidateurl);
        $config->lti_toolurl_ContentItemSelectionRequest = $canonicalurl;
        $config->toolurl_ContentItemSelectionRequest = $canonicalurl;
    } catch (moodle_exception $exception) {
        return $config;
    }

    return $config;
}

/**
 * Build a stable resource link id from the selected asset config.
 *
 * @param array $launchconfig
 * @return string
 */
function tiny_zoomclassroom_build_resource_link_id(array $launchconfig): string {
    return 'tiny_zoomclassroom_' . sha1(json_encode([
        'toolid' => (int)$launchconfig['toolid'],
        'toolurl' => (string)$launchconfig['toolurl'],
        'securetoolurl' => (string)$launchconfig['securetoolurl'],
        'custom' => (string)$launchconfig['custom'],
        'name' => (string)$launchconfig['name'],
    ]));
}

/**
 * Build the normalized launch config from a deep-link response object.
 *
 * @param int $courseid
 * @param int $toolid
 * @param stdClass $config
 * @return array
 */
function tiny_zoomclassroom_build_launch_config(int $courseid, int $toolid, stdClass $config): array {
    $typeconfig = lti_get_type_type_config($toolid);
    $name = clean_param($config->name ?? get_string('launchpage', 'tiny_zoomclassroom'), PARAM_TEXT);
    $toolurl = tiny_zoomclassroom_validate_zoom_url((string)($config->toolurl ?? ''));
    $securetoolurl = tiny_zoomclassroom_validate_zoom_url((string)($config->securetoolurl ?? ''));
    $custom = clean_param($config->instructorcustomparameters ?? '', PARAM_RAW_TRIMMED);

    if ($toolurl === '' && $securetoolurl === '') {
        $defaulttoolurl = tiny_zoomclassroom_validate_zoom_url((string)($typeconfig->lti_toolurl ?? ''));
        if ($defaulttoolurl === '') {
            throw new moodle_exception('invalidtoolurl', 'tiny_zoomclassroom');
        }

        $securetoolurl = $defaulttoolurl;
    }

    $launchconfig = [
        'courseid' => $courseid,
        'toolid' => $toolid,
        'name' => $name,
        'toolurl' => $toolurl,
        'securetoolurl' => $securetoolurl,
        'custom' => $custom,
        'sendname' => clean_param($config->instructorchoicesendname ?? LTI_SETTING_DELEGATE, PARAM_ALPHAEXT),
        'sendemail' => clean_param($config->instructorchoicesendemailaddr ?? LTI_SETTING_DELEGATE, PARAM_ALPHAEXT),
    ];
    $launchconfig['resource_link_id'] = tiny_zoomclassroom_build_resource_link_id($launchconfig);

    return $launchconfig;
}

/**
 * Generate a new opaque public identifier for a saved embed record.
 *
 * @return string
 */
function tiny_zoomclassroom_generate_embed_publicid(): string {
    return TINY_ZOOMCLASSROOM_EMBED_PUBLICID_PREFIX . bin2hex(random_bytes(16));
}

/**
 * Persist launch metadata server-side and return the public embed identifier.
 *
 * @param array $launchconfig
 * @return string
 */
function tiny_zoomclassroom_create_embed_record(array $launchconfig): string {
    global $DB;

    $publicid = tiny_zoomclassroom_generate_embed_publicid();
    $record = (object)[
        'publicid' => $publicid,
        'courseid' => (int)$launchconfig['courseid'],
        'toolid' => (int)$launchconfig['toolid'],
        'title' => (string)$launchconfig['name'],
        'launchconfigjson' => json_encode($launchconfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'timecreated' => time(),
        'timemodified' => time(),
    ];

    $DB->insert_record('tiny_zoomclassroom_emb', $record);

    return $publicid;
}

/**
 * Load a saved embed record by its opaque public identifier.
 *
 * @param string $publicid
 * @return stdClass
 */
function tiny_zoomclassroom_get_embed_record(string $publicid): stdClass {
    global $DB;

    $publicid = clean_param($publicid, PARAM_ALPHANUMEXT);
    if ($publicid === '') {
        throw new moodle_exception('invalidlaunch', 'tiny_zoomclassroom');
    }

    return $DB->get_record('tiny_zoomclassroom_emb', ['publicid' => $publicid], '*', MUST_EXIST);
}

/**
 * Decode saved launch metadata from an embed record.
 *
 * @param stdClass $record
 * @return array
 */
function tiny_zoomclassroom_decode_embed_launch_config(stdClass $record): array {
    $launchconfig = json_decode((string)$record->launchconfigjson, true);
    if (!is_array($launchconfig) || empty($launchconfig['courseid']) || empty($launchconfig['toolid'])) {
        throw new moodle_exception('invalidlaunch', 'tiny_zoomclassroom');
    }

    return $launchconfig;
}

/**
 * Parse newline-separated custom parameters into request params.
 *
 * @param string $custom
 * @return array
 */
function tiny_zoomclassroom_parse_custom_parameters(string $custom): array {
    $params = [];
    $lines = preg_split('/\r\n|\r|\n/', $custom);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = clean_param(trim($key), PARAM_ALPHANUMEXT);
        if ($key === '') {
            continue;
        }
        $params['custom_' . $key] = clean_param(trim($value), PARAM_RAW_TRIMMED);
    }

    return $params;
}

/**
 * Build a pseudo LTI instance for a signed asset launch.
 *
 * @param array $launchconfig
 * @return stdClass
 */
function tiny_zoomclassroom_build_pseudo_instance(array $launchconfig): stdClass {
    $instance = (object)[
        'course' => (int)$launchconfig['courseid'],
        'cmid' => 0,
        'id' => 0,
        'typeid' => (int)$launchconfig['toolid'],
        'name' => (string)$launchconfig['name'],
        'toolurl' => (string)$launchconfig['toolurl'],
        'securetoolurl' => (string)$launchconfig['securetoolurl'],
        'resource_link_id' => (string)$launchconfig['resource_link_id'],
        'instructorcustomparameters' => (string)$launchconfig['custom'],
        'instructorchoicesendname' => (string)$launchconfig['sendname'],
        'instructorchoicesendemailaddr' => (string)$launchconfig['sendemail'],
        'instructorchoiceacceptgrades' => 0,
        'instructorchoiceallowroster' => 0,
        'launchcontainer' => LTI_LAUNCH_CONTAINER_EMBED,
    ];

    return $instance;
}
