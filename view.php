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
 * Plugin-owned LTI launch bootstrap for embedded Zoom Classroom resources.
 *
 * @package     tiny_zoomclassroom
 * @copyright   2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once(__DIR__ . '/locallib.php');

$embedid = required_param('id', PARAM_ALPHANUMEXT);

if ($embedid !== '') {
    $embedrecord = tiny_zoomclassroom_get_embed_record($embedid);
    $launchconfig = tiny_zoomclassroom_decode_embed_launch_config($embedrecord);
} else {
    throw new moodle_exception('invalidlaunch', 'tiny_zoomclassroom');
}

tiny_zoomclassroom_require_configured_toolid((int)$launchconfig['toolid']);

$course = get_course((int)$launchconfig['courseid']);
require_login($course);

$typeconfig = lti_get_type_type_config((int)$launchconfig['toolid']);
if (($typeconfig->lti_ltiversion ?? '') !== LTI_VERSION_1P3) {
    throw new moodle_exception('invalidtool', 'tiny_zoomclassroom');
}

$PAGE->set_course($course);
$PAGE->set_context(context_course::instance($course->id));
$PAGE->set_pagelayout('embedded');
$PAGE->set_url(new moodle_url('/lib/editor/tiny/plugins/zoomclassroom/view.php', ['id' => $embedid]));
$PAGE->set_title($launchconfig['name']);

$launchid = 'tinyzoomlaunch_' . bin2hex(random_bytes(16));
$launchstate = [
    'courseid' => (int)$launchconfig['courseid'],
    'toolid' => (int)$launchconfig['toolid'],
    'userid' => (int)$USER->id,
    'timecreated' => time(),
    'embedid' => $embedid,
];
$SESSION->$launchid = json_encode($launchstate);

$toolurl = $launchconfig['toolurl'];
if (lti_request_is_using_ssl() && $launchconfig['securetoolurl'] !== '') {
    $toolurl = $launchconfig['securetoolurl'];
}

$params = [
    'iss' => $CFG->wwwroot,
    'target_link_uri' => $toolurl,
    'login_hint' => (string)$USER->id,
    'lti_message_hint' => json_encode(['launchid' => $launchid]),
    'client_id' => $typeconfig->lti_clientid,
    'lti_deployment_id' => (string)$launchconfig['toolid'],
];

echo $OUTPUT->header();
echo lti_post_launch_html($params, $typeconfig->lti_initiatelogin);
echo $OUTPUT->footer();
