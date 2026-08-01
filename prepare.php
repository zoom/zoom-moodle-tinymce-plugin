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

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../../config.php');
require_once(__DIR__ . '/locallib.php');

$rawbody = file_get_contents('php://input');
$request = json_decode($rawbody);

if (!$request || !is_object($request)) {
    throw new moodle_exception('invalidresponse', 'tiny_zoomclassroom');
}

$sesskey = clean_param($request->sesskey ?? '', PARAM_RAW_TRIMMED);
$courseid = clean_param($request->course ?? 0, PARAM_INT);
$toolid = clean_param($request->toolid ?? 0, PARAM_INT);
$config = $request->config ?? null;

if (!confirm_sesskey($sesskey)) {
    throw new moodle_exception('invalidsesskey', 'error');
}

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
if (isguestuser()) {
    throw new required_capability_exception($context, 'moodle/course:view', 'nopermissions', '');
}

$typeconfig = lti_get_type_type_config($toolid);
if (($typeconfig->lti_ltiversion ?? '') !== LTI_VERSION_1P3) {
    throw new moodle_exception('invalidtool', 'tiny_zoomclassroom');
}

if (!$config || !is_object($config)) {
    throw new moodle_exception('invalidresponse', 'tiny_zoomclassroom');
}

$launchconfig = tiny_zoomclassroom_build_launch_config($courseid, $toolid, $config);
$embedid = tiny_zoomclassroom_create_embed_record($launchconfig);
$launchurl = new moodle_url('/lib/editor/tiny/plugins/zoomclassroom/view.php', [
    'id' => $embedid,
]);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'embedid' => $embedid,
    'launchurl' => $launchurl->out(false),
    'title' => $launchconfig['name'],
]);
