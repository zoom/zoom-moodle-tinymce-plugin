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
 * Admin configuration page for tiny_zoomclassroom.
 *
 * @package     tiny_zoomclassroom
 * @copyright   2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once(__DIR__ . '/locallib.php');

/**
 * Load eligible LTI 1.3 tools with preview details.
 *
 * @return array
 */
function tiny_zoomclassroom_get_registered_tools(): array {
    global $DB;

    $tools = [];
    if (!$DB->get_manager()->table_exists('lti_types')) {
        return $tools;
    }

    $records = $DB->get_records('lti_types', [], 'id DESC', 'id, name');
    foreach ($records as $record) {
        $config = lti_get_type_type_config((int)$record->id);
        if (($config->lti_ltiversion ?? '') !== LTI_VERSION_1P3) {
            continue;
        }

        $name = trim((string)($record->name ?? ''));
        if ($name === '') {
            $name = get_string('unnamedtool', 'tiny_zoomclassroom', $record->id);
        }

        $tools[] = [
            'id' => (int)$record->id,
            'name' => $name,
            'clientid' => (string)($config->lti_clientid ?? ''),
            'deploymentid' => (string)$record->id,
            'toolurl' => (string)($config->lti_toolurl ?? ''),
            'deeplinkurl' => (function() use ($config) {
                $deeplinkurl = (string)($config->lti_toolurl_ContentItemSelectionRequest ?? '');
                $toolurl = (string)($config->lti_toolurl ?? '');
                try {
                    return tiny_zoomclassroom_build_canonical_deeplink_url($toolurl !== '' ? $toolurl : $deeplinkurl);
                } catch (moodle_exception $exception) {
                    return $deeplinkurl;
                }
            })(),
            'loginurl' => (string)($config->lti_initiatelogin ?? ''),
            'jwksurl' => (string)($config->lti_publickeyset ?? ''),
            'description' => trim((string)($config->lti_description ?? '')),
        ];
    }

    return $tools;
}

/**
 * Find a tool by id from the preview array.
 *
 * @param array $tools
 * @param int $toolid
 * @return array|null
 */
function tiny_zoomclassroom_find_tool(array $tools, int $toolid): ?array {
    foreach ($tools as $tool) {
        if ((int)$tool['id'] === $toolid) {
            return $tool;
        }
    }

    return null;
}

/**
 * Render configured tool summary text.
 *
 * @param array|null $tool
 * @return string
 */
function tiny_zoomclassroom_render_tool_preview(?array $tool): string {
    if (!$tool) {
        return html_writer::tag('p', get_string('registrationpreviewempty', 'tiny_zoomclassroom'), ['class' => 'mb-0']);
    }

    $toollabel = trim((string)$tool['name']);
    if ($toollabel === '') {
        $toollabel = get_string('unnamedtool', 'tiny_zoomclassroom', $tool['id']);
    }
    $toollabel .= ' (#' . (int)$tool['id'] . ')';

    return html_writer::tag('p', s($toollabel), ['class' => 'mb-2']);
}

/**
 * Validate positive integer settings.
 *
 * @param int $value
 * @param string $stringkey
 * @return int
 */
function tiny_zoomclassroom_validate_dimension(int $value, string $stringkey): int {
    if ($value < 100 || $value > 5000) {
        throw new moodle_exception($stringkey, 'tiny_zoomclassroom');
    }

    return $value;
}

$context = context_system::instance();
require_login();
require_capability('moodle/site:config', $context);

$pageurl = new moodle_url('/lib/editor/tiny/plugins/zoomclassroom/register.php');
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'tiny_zoomclassroom'));
$PAGE->set_heading(get_string('pluginname', 'tiny_zoomclassroom'));

$registeredtools = tiny_zoomclassroom_get_registered_tools();
$storedtoolid = get_config('tiny_zoomclassroom', 'toolid');
$activetoolid = $storedtoolid === false ? 0 : (int)$storedtoolid;
$pendingregisteredtoolid = (int)get_config('tiny_zoomclassroom', 'pendingregisteredtoolid');
$latestknownid = optional_param('latestknownid', 0, PARAM_INT);
$registrationurl = optional_param('registrationurl', '', PARAM_URL);
$registrationcompleted = optional_param('registrationcompleted', 0, PARAM_BOOL);

if ($storedtoolid === false && count($registeredtools) === 1) {
    $activetoolid = (int)$registeredtools[0]['id'];
    set_config('toolid', $activetoolid, 'tiny_zoomclassroom');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = required_param('action', PARAM_ALPHA);

    if ($action === 'selectexistingtool') {
        $selectedexistingtoolid = required_param('selectedexistingtoolid', PARAM_INT);
        $selectedtool = tiny_zoomclassroom_find_tool($registeredtools, $selectedexistingtoolid);
        if (!$selectedtool) {
            \core\notification::error(get_string('invalidtool', 'tiny_zoomclassroom'));
        } else {
            set_config('toolid', $selectedexistingtoolid, 'tiny_zoomclassroom');
            $activetoolid = $selectedexistingtoolid;
            \core\notification::success(get_string('existingtoolselected', 'tiny_zoomclassroom', $selectedtool['name']));
        }
    } else if ($action === 'saveadvanced') {
        try {
            $popupwidth = tiny_zoomclassroom_validate_dimension(required_param('popupwidth', PARAM_INT), 'invalidpopupwidth');
            $popupheight = tiny_zoomclassroom_validate_dimension(required_param('popupheight', PARAM_INT), 'invalidpopupheight');
            $alloweddomains = trim((string)required_param('alloweddomains', PARAM_RAW));

            $validateddomains = [];
            $lines = preg_split('/\r\n|\r|\n/', $alloweddomains);
            foreach ($lines as $line) {
                $pattern = tiny_zoomclassroom_validate_allowed_domain_pattern($line);
                if ($pattern !== '') {
                    $validateddomains[$pattern] = true;
                }
            }

            if (empty($validateddomains)) {
                $validateddomains['*.zoom.us'] = true;
            }

            set_config('popupwidth', $popupwidth, 'tiny_zoomclassroom');
            set_config('popupheight', $popupheight, 'tiny_zoomclassroom');
            set_config('alloweddomains', implode(PHP_EOL, array_keys($validateddomains)), 'tiny_zoomclassroom');
            \core\notification::success(get_string('advancedsettingssaved', 'tiny_zoomclassroom'));
        } catch (moodle_exception $exception) {
            \core\notification::error($exception->getMessage());
        }
    } else if ($action === 'clearconfiguredtool') {
        set_config('toolid', 0, 'tiny_zoomclassroom');
        $activetoolid = 0;
        \core\notification::success(get_string('configuredtoolcleared', 'tiny_zoomclassroom'));
    } else if ($action === 'deleteconfiguredtool') {
        if ($activetoolid > 0) {
            lti_delete_type($activetoolid);
            set_config('toolid', 0, 'tiny_zoomclassroom');
            $activetoolid = 0;
            $registeredtools = tiny_zoomclassroom_get_registered_tools();
            \core\notification::success(get_string('configuredtooldeleted', 'tiny_zoomclassroom'));
        }
    }
}

if ($registrationcompleted && $pendingregisteredtoolid > 0) {
    $pendingtool = tiny_zoomclassroom_find_tool($registeredtools, $pendingregisteredtoolid);
    if ($pendingtool) {
        $activetoolid = $pendingregisteredtoolid;
        set_config('toolid', $activetoolid, 'tiny_zoomclassroom');
    }
    unset_config('pendingregisteredtoolid', 'tiny_zoomclassroom');
} else if ($latestknownid > 0) {
    foreach ($registeredtools as $tool) {
        if ((int)$tool['id'] > $latestknownid) {
            $activetoolid = (int)$tool['id'];
            set_config('toolid', $activetoolid, 'tiny_zoomclassroom');
            break;
        }
    }
}

$currenttool = tiny_zoomclassroom_find_tool($registeredtools, $activetoolid);

$manageurl = new moodle_url('/mod/lti/toolconfigure.php');
$openidconfigurationurl = new moodle_url('/lib/editor/tiny/plugins/zoomclassroom/openid-configuration.php');
$registrationendpointurl = new moodle_url('/lib/editor/tiny/plugins/zoomclassroom/openid-registration.php');
$authorizationendpointurl = new moodle_url('/lib/editor/tiny/plugins/zoomclassroom/auth.php');
$jwksurl = new moodle_url('/mod/lti/certs.php');

$platformitems = [
    get_string('platformissuerlabel', 'tiny_zoomclassroom', s($CFG->wwwroot)),
    get_string('platformopenidconfigurationlabel', 'tiny_zoomclassroom', s($openidconfigurationurl->out(false))),
    get_string('platformregistrationendpointlabel', 'tiny_zoomclassroom', s($registrationendpointurl->out(false))),
    get_string('platformauthorizationendpointlabel', 'tiny_zoomclassroom', s($authorizationendpointurl->out(false))),
    get_string('platformjwkslabel', 'tiny_zoomclassroom', s($jwksurl->out(false))),
];

$popupwidth = (int)get_config('tiny_zoomclassroom', 'popupwidth');
if ($popupwidth <= 0) {
    $popupwidth = 1200;
}

$popupheight = (int)get_config('tiny_zoomclassroom', 'popupheight');
if ($popupheight <= 0) {
    $popupheight = 800;
}

$alloweddomains = (string)get_config('tiny_zoomclassroom', 'alloweddomains');
if ($alloweddomains === '') {
    $alloweddomains = '*.zoom.us';
}

$previewjson = json_encode([
    'latestKnownId' => $registeredtools[0]['id'] ?? 0,
    'currentToolId' => $activetoolid,
    'registrationStartUrl' => (new moodle_url('/lib/editor/tiny/plugins/zoomclassroom/startregistration.php', ['sesskey' => sesskey()]))->out(false),
    'returnUrl' => $pageurl->out(false),
    'strings' => [
        'invalidRegistrationUrl' => get_string('registrationurlinvalid', 'tiny_zoomclassroom'),
        'missingRegistrationUrl' => get_string('registrationurlempty', 'tiny_zoomclassroom'),
    ],
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$hasconfiguredtool = !empty($currenttool);
$registrationheading = $hasconfiguredtool
    ? get_string('configuredtoolheading', 'tiny_zoomclassroom')
    : get_string('registrationworkflowheading', 'tiny_zoomclassroom');
$registrationintro = $hasconfiguredtool
    ? get_string('configuredtoolwithupdatedesc', 'tiny_zoomclassroom')
    : get_string('registrationworkflowdesc', 'tiny_zoomclassroom');
$registrationbuttonlabel = $hasconfiguredtool
    ? get_string('registrationupdate', 'tiny_zoomclassroom')
    : get_string('registrationstart', 'tiny_zoomclassroom');
$existingtooloptions = [];
foreach ($registeredtools as $tool) {
    $existingtooloptions[(int)$tool['id']] = $tool['name'] . ' (#' . (int)$tool['id'] . ')';
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tiny_zoomclassroom'));
echo html_writer::tag('p', get_string('registrationpage_desc', 'tiny_zoomclassroom'));

echo $OUTPUT->box_start();
echo html_writer::tag('h3', $registrationheading);
echo html_writer::tag('p', $registrationintro);
if ($hasconfiguredtool) {
    echo html_writer::start_div('border rounded p-3 mb-3');
    echo tiny_zoomclassroom_render_tool_preview($currenttool);
    $configuredtoolurl = new moodle_url('/mod/lti/typessettings.php', [
        'action' => 'update',
        'id' => (int)$currenttool['id'],
        'returnto' => 'toolconfigure',
        'sesskey' => sesskey(),
    ]);
    echo html_writer::start_div('mt-3 d-flex justify-content-between align-items-center gap-2 flex-wrap');
    echo html_writer::link($configuredtoolurl, get_string('configuredtoollinklabel', 'tiny_zoomclassroom'), [
        'class' => 'btn btn-secondary',
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
    ]);
    echo html_writer::start_div('d-flex gap-2 flex-wrap');
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'm-0',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'clearconfiguredtool',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-outline-secondary',
        'value' => get_string('configuredtoolclearbutton', 'tiny_zoomclassroom'),
        'onclick' => 'return confirm(' . json_encode(get_string('configuredtoolclearconfirm', 'tiny_zoomclassroom')) . ');',
    ]);
    echo html_writer::end_tag('form');

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'm-0',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'deleteconfiguredtool',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-outline-danger',
        'value' => get_string('configuredtooldeletebutton', 'tiny_zoomclassroom'),
        'onclick' => 'return confirm(' . json_encode(get_string('configuredtooldeleteconfirm', 'tiny_zoomclassroom')) . ');',
    ]);
    echo html_writer::end_tag('form');
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
} else {
    echo html_writer::start_tag('details', ['class' => 'mb-3']);
    echo html_writer::tag('summary', get_string('moodleplatformheading', 'tiny_zoomclassroom'));
    echo html_writer::tag('p', get_string('moodleplatformregisterhelp', 'tiny_zoomclassroom'), ['class' => 'mt-2']);
    echo html_writer::alist($platformitems);
    echo html_writer::end_tag('details');
    echo html_writer::tag('label', get_string('registrationurl', 'tiny_zoomclassroom'), ['for' => 'id_registrationurl']);
    echo html_writer::empty_tag('input', [
        'type' => 'url',
        'class' => 'form-control',
        'id' => 'id_registrationurl',
        'maxlength' => 2048,
        'required' => 'required',
        'placeholder' => 'https://example.com/lti/dynamic-registration',
        'value' => $registrationurl,
    ]);
    echo html_writer::tag('p', get_string('registrationurl_desc', 'tiny_zoomclassroom'), ['class' => 'mt-2']);
    echo html_writer::div(
        html_writer::tag('button', $registrationbuttonlabel, [
            'type' => 'button',
            'class' => 'btn btn-primary me-2',
            'id' => 'tiny_zoomclassroom_start_registration',
        ]) .
        html_writer::link($manageurl, get_string('registrationviewtools', 'tiny_zoomclassroom'), [
            'class' => 'btn btn-secondary',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ]),
        'mt-3'
    );
    if (!empty($existingtooloptions)) {
        echo html_writer::tag('hr', '', ['class' => 'my-4']);
        echo html_writer::tag('p', get_string('existingtoolpickerdesc', 'tiny_zoomclassroom'));
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'd-flex align-items-end gap-2 flex-wrap',
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'selectexistingtool',
        ]);
        echo html_writer::start_div('flex-grow-1');
        echo html_writer::tag('label', get_string('existingtoolpickerlabel', 'tiny_zoomclassroom'), [
            'for' => 'id_selectedexistingtoolid',
            'class' => 'form-label',
        ]);
        echo html_writer::select($existingtooloptions, 'selectedexistingtoolid', $activetoolid, false, [
            'id' => 'id_selectedexistingtoolid',
            'class' => 'form-select',
        ]);
        echo html_writer::end_div();
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'class' => 'btn btn-secondary',
            'value' => get_string('existingtoolpickerbutton', 'tiny_zoomclassroom'),
        ]);
        echo html_writer::end_tag('form');
    }
}
echo $OUTPUT->box_end();

echo $OUTPUT->box_start();
echo html_writer::tag('h3', get_string('advancedsettingsheading', 'tiny_zoomclassroom'));
echo html_writer::tag('p', get_string('advancedsettingsdesc', 'tiny_zoomclassroom'));
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'action',
    'value' => 'saveadvanced',
]);
echo html_writer::tag('label', get_string('popupwidth', 'tiny_zoomclassroom'), ['for' => 'id_popupwidth']);
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'class' => 'form-control mb-3',
    'id' => 'id_popupwidth',
    'name' => 'popupwidth',
    'min' => 100,
    'max' => 5000,
    'value' => $popupwidth,
]);
echo html_writer::tag('label', get_string('popupheight', 'tiny_zoomclassroom'), ['for' => 'id_popupheight']);
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'class' => 'form-control mb-3',
    'id' => 'id_popupheight',
    'name' => 'popupheight',
    'min' => 100,
    'max' => 5000,
    'value' => $popupheight,
]);
echo html_writer::tag('label', get_string('alloweddomains', 'tiny_zoomclassroom'), ['for' => 'id_alloweddomains']);
echo html_writer::tag('textarea', s($alloweddomains), [
    'class' => 'form-control',
    'id' => 'id_alloweddomains',
    'name' => 'alloweddomains',
    'rows' => 4,
]);
echo html_writer::tag('p', get_string('alloweddomains_desc', 'tiny_zoomclassroom'), ['class' => 'mt-2']);
echo html_writer::div(
    html_writer::empty_tag('input', [
        'type' => 'submit',
        'class' => 'btn btn-primary',
        'value' => get_string('savechanges'),
    ]),
    'mt-3'
);
echo html_writer::end_tag('form');
echo $OUTPUT->box_end();

echo html_writer::start_div('', ['id' => 'tiny_zoomclassroom_registration_root', 'data-config' => $previewjson]);
echo html_writer::end_div();
?>
<style>
#tiny_zoomclassroom_modal_backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1050;
}

#tiny_zoomclassroom_modal_backdrop.is-open {
    display: flex;
}

#tiny_zoomclassroom_modal {
    width: min(1200px, 94vw);
    height: min(820px, 90vh);
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

#tiny_zoomclassroom_modal_header,
#tiny_zoomclassroom_modal_footer {
    padding: 12px 16px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

#tiny_zoomclassroom_modal_footer {
    border-top: 1px solid #dee2e6;
    border-bottom: 0;
}

#tiny_zoomclassroom_modal_iframe {
    border: 0;
    width: 100%;
    flex: 1 1 auto;
}
</style>
<div id="tiny_zoomclassroom_modal_backdrop" aria-hidden="true">
    <div id="tiny_zoomclassroom_modal" role="dialog" aria-modal="true" aria-labelledby="tiny_zoomclassroom_modal_title">
        <div id="tiny_zoomclassroom_modal_header">
            <strong id="tiny_zoomclassroom_modal_title"><?php echo s(get_string('registrationmodaltitle', 'tiny_zoomclassroom')); ?></strong>
            <button type="button" class="btn btn-secondary btn-sm" id="tiny_zoomclassroom_close_modal_top">
                <?php echo s(get_string('registrationclosebutton', 'tiny_zoomclassroom')); ?>
            </button>
        </div>
        <iframe id="tiny_zoomclassroom_modal_iframe" title="<?php echo s(get_string('registrationmodaltitle', 'tiny_zoomclassroom')); ?>"></iframe>
        <div id="tiny_zoomclassroom_modal_footer">
            <span id="tiny_zoomclassroom_modal_status"><?php echo s(get_string('registrationrefreshnotice', 'tiny_zoomclassroom')); ?></span>
            <div>
                <button type="button" class="btn btn-secondary" id="tiny_zoomclassroom_close_modal_bottom">
                    <?php echo s(get_string('registrationdonebutton', 'tiny_zoomclassroom')); ?>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    const root = document.getElementById('tiny_zoomclassroom_registration_root');
    if (!root) {
        return;
    }

    const config = JSON.parse(root.dataset.config || '{}');
    const registrationInput = document.getElementById('id_registrationurl');
    const startButton = document.getElementById('tiny_zoomclassroom_start_registration');
    const backdrop = document.getElementById('tiny_zoomclassroom_modal_backdrop');
    const iframe = document.getElementById('tiny_zoomclassroom_modal_iframe');
    const closeTop = document.getElementById('tiny_zoomclassroom_close_modal_top');
    const closeBottom = document.getElementById('tiny_zoomclassroom_close_modal_bottom');

    const closeAndRefresh = () => {
        iframe.removeAttribute('src');
        backdrop.classList.remove('is-open');
        const nextUrl = new URL(config.returnUrl, window.location.origin);
        nextUrl.searchParams.set('latestknownid', String(config.latestKnownId || 0));
        nextUrl.searchParams.set('registrationcompleted', '1');
        window.location.assign(nextUrl.toString());
    };

    const openModal = () => {
        const value = (registrationInput?.value || '').trim();
        if (!value) {
            window.alert(config.strings?.missingRegistrationUrl || 'Missing URL');
            return;
        }

        let parsedUrl;
        try {
            parsedUrl = new URL(value);
        } catch (error) {
            window.alert(config.strings?.invalidRegistrationUrl || 'Invalid URL');
            return;
        }

        if (parsedUrl.protocol !== 'https:') {
            window.alert(config.strings?.invalidRegistrationUrl || 'Invalid URL');
            return;
        }

        const startUrl = new URL(config.registrationStartUrl, window.location.origin);
        startUrl.searchParams.set('url', parsedUrl.toString());
        if ((config.currentToolId || 0) > 0) {
            startUrl.searchParams.set('type', String(config.currentToolId));
        }
        iframe.setAttribute('src', startUrl.toString());
        backdrop.classList.add('is-open');
    };

    window.addEventListener('message', (event) => {
        if (event?.data?.subject === 'org.imsglobal.lti.close') {
            closeAndRefresh();
        }
    });

    startButton?.addEventListener('click', openModal);
    closeTop?.addEventListener('click', closeAndRefresh);
    closeBottom?.addEventListener('click', closeAndRefresh);
})();
</script>
<?php
echo $OUTPUT->footer();
