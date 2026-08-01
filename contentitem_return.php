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
 * Plugin-owned deep-linking return handler for TinyMCE editor launches.
 *
 * @package     tiny_zoomclassroom
 * @copyright   2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

$id = required_param('id', PARAM_INT);
$courseid = required_param('course', PARAM_INT);
$jwt = optional_param('JWT', '', PARAM_RAW);

$context = context_course::instance($courseid);
$pageurl = new moodle_url('/lib/editor/tiny/plugins/zoomclassroom/contentitem_return.php');
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('popup');
$PAGE->set_context($context);

global $_POST;
if (!empty($_POST['repost'])) {
    unset($_POST['repost']);
} else if (!isloggedin()) {
    header_remove('Set-Cookie');
    $output = $PAGE->get_renderer('mod_lti');
    $page = new \mod_lti\output\repost_crosssite_page($_SERVER['REQUEST_URI'], $_POST);
    echo $output->header();
    echo $output->render($page);
    echo $output->footer();
    return;
}

if (!empty($jwt)) {
    $params = lti_convert_from_jwt($id, $jwt);
    $consumerkey = $params['oauth_consumer_key'] ?? '';
    $messagetype = $params['lti_message_type'] ?? '';
    $version = $params['lti_version'] ?? '';
    $items = $params['content_items'] ?? '';
    $errormsg = $params['lti_errormsg'] ?? '';
    $msg = $params['lti_msg'] ?? '';
} else {
    $consumerkey = required_param('oauth_consumer_key', PARAM_RAW);
    $messagetype = required_param('lti_message_type', PARAM_TEXT);
    $version = required_param('lti_version', PARAM_TEXT);
    $items = optional_param('content_items', '', PARAM_RAW);
    $errormsg = optional_param('lti_errormsg', '', PARAM_TEXT);
    $msg = optional_param('lti_msg', '', PARAM_TEXT);
    lti_verify_oauth_signature($id, $consumerkey);
}

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);
require_sesskey();
if (isguestuser()) {
    throw new required_capability_exception($context, 'moodle/course:view', 'nopermissions', '');
}

$returndata = null;
if (empty($errormsg) && !empty($items)) {
    try {
        $returndata = lti_tool_configuration_from_content_item($id, $messagetype, $version, $consumerkey, $items);
    } catch (moodle_exception $exception) {
        $errormsg = $exception->getMessage();
    }
}

echo $OUTPUT->header();
$PAGE->requires->js_call_amd('mod_lti/contentitem_return', 'init', [$returndata]);
echo $OUTPUT->footer();

if ($errormsg) {
    \core\notification::error($errormsg);
} else if (!empty($returndata) && !$msg) {
    \core\notification::success(get_string('successfullyfetchedtoolconfigurationfromcontent', 'lti'));
} else if (!empty($msg)) {
    \core\notification::success($msg);
}
