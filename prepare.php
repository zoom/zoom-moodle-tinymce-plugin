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
require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once($CFG->dirroot . '/course/modlib.php');

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
require_capability('moodle/course:manageactivities', $context);
require_capability('mod/lti:addcoursetool', $context);
require_capability('mod/lti:addpreconfiguredinstance', $context);

$typeconfig = lti_get_type_type_config($toolid);
if (($typeconfig->lti_ltiversion ?? '') !== LTI_VERSION_1P3) {
    throw new moodle_exception('invalidtool', 'tiny_zoomclassroom');
}

if (!$config || !is_object($config)) {
    throw new moodle_exception('invalidresponse', 'tiny_zoomclassroom');
}

$name = clean_param($config->name ?? get_string('launchpage', 'tiny_zoomclassroom'), PARAM_TEXT);
$toolurl = clean_param($config->toolurl ?? '', PARAM_URL);
$securetoolurl = clean_param($config->securetoolurl ?? '', PARAM_URL);
$custom = clean_param($config->instructorcustomparameters ?? '', PARAM_RAW_TRIMMED);

$dedupekey = 'tiny_zoomclassroom:' . sha1(json_encode([
    'course' => $courseid,
    'toolid' => $toolid,
    'name' => $name,
    'toolurl' => $toolurl,
    'securetoolurl' => $securetoolurl,
    'custom' => $custom,
]));

$cmid = null;
$sql = "SELECT cm.id
          FROM {course_modules} cm
          JOIN {modules} m ON m.id = cm.module
          JOIN {lti} l ON l.id = cm.instance
         WHERE cm.course = :courseid
           AND m.name = 'lti'
           AND cm.idnumber = :idnumber
           AND l.typeid = :toolid";
$existing = $DB->get_field_sql($sql, [
    'courseid' => $courseid,
    'idnumber' => $dedupekey,
    'toolid' => $toolid,
]);

if ($existing) {
    $cmid = (int)$existing;
    set_coursemodule_visible($cmid, 1, 0);
} else {
    $module = $DB->get_record('modules', ['name' => 'lti'], '*', MUST_EXIST);

    $moduleinfo = (object)[
        'course' => $courseid,
        'modulename' => 'lti',
        'module' => $module->id,
        'section' => 0,
        'visible' => 1,
        'visibleoncoursepage' => 0,
        'groupmode' => 0,
        'groupingid' => 0,
        'name' => $name,
        'introeditor' => [
            'text' => '',
            'format' => FORMAT_HTML,
            'itemid' => 0,
        ],
        'showdescription' => 0,
        'typeid' => $toolid,
        'toolurl' => $toolurl,
        'securetoolurl' => $securetoolurl,
        'instructorcustomparameters' => $custom,
        'instructorchoicesendname' => $config->instructorchoicesendname ?? LTI_SETTING_DELEGATE,
        'instructorchoicesendemailaddr' => $config->instructorchoicesendemailaddr ?? LTI_SETTING_DELEGATE,
        'instructorchoiceacceptgrades' => 0,
        'grade' => 0,
        'launchcontainer' => LTI_LAUNCH_CONTAINER_EMBED,
        'showtitlelaunch' => 0,
        'showdescriptionlaunch' => 0,
        'cmidnumber' => $dedupekey,
    ];

    if (isset($config->icon)) {
        $moduleinfo->icon = clean_param($config->icon, PARAM_URL);
    }
    if (isset($config->secureicon)) {
        $moduleinfo->secureicon = clean_param($config->secureicon, PARAM_URL);
    }

    $created = add_moduleinfo($moduleinfo, $course, null);
    $cmid = (int)$created->coursemodule;
}

$launchurl = new moodle_url('/mod/lti/launch.php', [
    'id' => $cmid,
]);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'launchurl' => $launchurl->out(false),
    'title' => $name,
]);
