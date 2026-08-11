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

/**
 * Plugin-owned response to LTI 1.3 initiate-login requests for embedded Zoom Classroom resources.
 *
 * @package     tiny_zoomclassroom
 * @copyright   2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once(__DIR__ . '/locallib.php');

global $_POST, $_SERVER;

if (!isloggedin() && empty($_POST['repost'])) {
    header_remove('Set-Cookie');
    $PAGE->set_pagelayout('popup');
    $PAGE->set_context(context_system::instance());
    $output = $PAGE->get_renderer('mod_lti');
    $page = new \mod_lti\output\repost_crosssite_page($_SERVER['REQUEST_URI'], $_POST);
    echo $output->header();
    echo $output->render($page);
    echo $output->footer();
    return;
}

$scope = optional_param('scope', '', PARAM_TEXT);
$responsetype = optional_param('response_type', '', PARAM_TEXT);
$clientid = optional_param('client_id', '', PARAM_TEXT);
$redirecturi = optional_param('redirect_uri', '', PARAM_URL);
$loginhint = optional_param('login_hint', '', PARAM_TEXT);
$ltimessagehintenc = optional_param('lti_message_hint', '', PARAM_TEXT);
$state = optional_param('state', '', PARAM_TEXT);
$responsemode = optional_param('response_mode', '', PARAM_TEXT);
$nonce = optional_param('nonce', '', PARAM_TEXT);
$prompt = optional_param('prompt', '', PARAM_TEXT);

$ok = !empty($scope) && !empty($responsetype) && !empty($clientid) &&
    !empty($redirecturi) && !empty($loginhint) && !empty($nonce);

if (!$ok) {
    $error = 'invalid_request';
}

$ltimessagehint = json_decode($ltimessagehintenc);
$ok = $ok && isset($ltimessagehint->launchid);
if (!$ok) {
    $error = 'invalid_request';
    $desc = 'No launch id in LTI hint';
}
if ($ok && ($scope !== 'openid')) {
    $ok = false;
    $error = 'invalid_scope';
}
if ($ok && ($responsetype !== 'id_token')) {
    $ok = false;
    $error = 'unsupported_response_type';
}

$launchconfig = null;
$typeconfig = null;
$launchmode = null;
$courseid = 0;
$typeid = 0;
$messagetype = '';
$foruserid = 0;
$title = '';
$text = '';
if ($ok) {
    $launchid = $ltimessagehint->launchid;
    $launchstatestring = $SESSION->$launchid ?? '';
    unset($SESSION->$launchid);

    if (strpos($launchid, 'tinyzoomlaunch_') === 0) {
        $launchmode = 'plugin';
        $launchrecord = json_decode($launchstatestring, true);
        $timecreated = (int)($launchrecord['timecreated'] ?? 0);
        if (empty($launchrecord['toolid']) || empty($launchrecord['courseid'])) {
            $ok = false;
            $error = 'invalid_request';
            $desc = 'Missing launch state';
        } else if ($timecreated <= 0 || (time() - $timecreated) > TINY_ZOOMCLASSROOM_LAUNCH_STATE_TTL) {
            $ok = false;
            $error = 'invalid_request';
            $desc = 'Launch state expired';
        } else {
            if (!empty($launchrecord['embedid'])) {
                $embedrecord = tiny_zoomclassroom_get_embed_record((string)$launchrecord['embedid']);
                $launchconfig = tiny_zoomclassroom_decode_embed_launch_config($embedrecord);
            } else {
                $ok = false;
                $error = 'invalid_request';
                $desc = 'Missing launch embed id';
            }
            $typeid = (int)$launchrecord['toolid'];
            $courseid = (int)$launchrecord['courseid'];
            tiny_zoomclassroom_require_configured_toolid($typeid);
            $typeconfig = lti_get_type_type_config($typeid);
        }
    } else if (strpos($launchid, 'ltilaunch_') === 0 && $launchstatestring !== '') {
        $launchmode = 'core';
        [$courseid, $typeid, $instanceid, $messagetype, $foruserid, $titleb64, $textb64] =
            array_pad(explode(',', $launchstatestring, 7), 7, '');
        $courseid = (int)$courseid;
        $typeid = (int)$typeid;
        $foruserid = (int)$foruserid;
        $messagetype = (string)$messagetype;
        $title = base64_decode($titleb64, true) ?: '';
        $text = base64_decode($textb64, true) ?: '';
        tiny_zoomclassroom_require_configured_toolid($typeid);
        $typeconfig = lti_get_type_type_config($typeid);
    } else {
        $ok = false;
        $error = 'invalid_request';
        $desc = 'Missing launch state';
    }

    if ($ok) {
        $ok = ($clientid === ($typeconfig->lti_clientid ?? ''));
        if (!$ok) {
            $error = 'unauthorized_client';
        }
    }
}
if ($ok && ($loginhint !== (string)$USER->id)) {
    $ok = false;
    $error = 'access_denied';
}

if (empty($typeconfig)) {
    throw new moodle_exception('invalidrequest', 'error');
}

$allowedredirects = array_map('trim', explode("\n", (string)($typeconfig->lti_redirectionuris ?? '')));
if (!in_array($redirecturi, $allowedredirects, true)) {
    throw new moodle_exception('invalidrequest', 'error');
}

if ($ok) {
    if ($responsemode !== 'form_post') {
        $ok = false;
        $error = 'invalid_request';
        $desc = 'Invalid response_mode';
    }
}
if ($ok && !empty($prompt) && ($prompt !== 'none')) {
    $ok = false;
    $error = 'invalid_request';
    $desc = 'Invalid prompt';
}

if ($ok) {
    $course = get_course($courseid);

    if ($launchmode === 'plugin') {
        require_login($course);
        $PAGE->set_course($course);
        $PAGE->set_context(context_course::instance($course->id));

        $instance = tiny_zoomclassroom_build_pseudo_instance($launchconfig);
        [$endpoint, $params] = lti_get_launch_data($instance, $nonce, 'basic-lti-launch-request', 0);
    } else {
        require_login($course);
        $context = context_course::instance($courseid);
        if (isguestuser()) {
            throw new required_capability_exception($context, 'moodle/course:view', 'nopermissions', '');
        }

        $returnurlparams = [
            'course' => $courseid,
            'id' => $typeid,
            'sesskey' => sesskey(),
        ];
        $returnurl = new moodle_url('/lib/editor/tiny/plugins/zoomclassroom/contentitem_return.php', $returnurlparams);
        $request = lti_build_content_item_selection_request(
            $typeid,
            $course,
            $returnurl,
            $title,
            $text,
            [],
            [],
            false,
            true,
            false,
            false,
            false,
            $nonce
        );
        $endpoint = $request->url;
        $params = $request->params;
    }
} else {
    $params = ['error' => $error];
    if (!empty($desc)) {
        $params['error_description'] = $desc;
    }
}

if (isset($state)) {
    $params['state'] = $state;
}

echo lti_post_launch_html($params, $redirecturi);
