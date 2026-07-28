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
 * Strings for tiny_zoomclassroom.
 *
 * @package     tiny_zoomclassroom
 * @copyright   2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Zoom Classroom';
$string['settings'] = 'Zoom Classroom settings';
$string['privacy:metadata'] = 'The Zoom Classroom TinyMCE plugin does not store personal data.';
$string['toolheading'] = 'LTI tool mapping';
$string['toolheading_desc'] = 'Choose the preconfigured Moodle external tool that should be launched when users click the Zoom Classroom TinyMCE button.';
$string['toolid'] = 'Registered LTI tool';
$string['toolid_desc'] = 'Pick the Moodle external tool registration that already supports LTI 1.3 deep linking for Zoom Classroom.';
$string['toolidnone'] = 'No eligible LTI 1.3 tools found';
$string['invalidtool'] = 'The configured external tool must support LTI 1.3 deep linking.';
$string['notconfigured'] = 'Not configured';
$string['unnamedtool'] = 'Unnamed external tool ({$a})';
$string['popupwidth'] = 'Popup width';
$string['popupwidth_desc'] = 'Width, in pixels, for the deep-linking popup window.';
$string['popupheight'] = 'Popup height';
$string['popupheight_desc'] = 'Height, in pixels, for the deep-linking popup window.';
$string['buttonlabel'] = 'Insert Zoom Classroom content';
$string['menuitem'] = 'Zoom Classroom';
$string['errormisconfigured'] = 'Zoom Classroom is not configured yet. Ask a Moodle administrator to map this button to a registered external tool.';
$string['errornocourse'] = 'Zoom Classroom can only be launched from a course context.';
$string['errorpopupblocked'] = 'The Zoom Classroom popup was blocked. Please allow popups for this site and try again.';
$string['insertfallbacktext'] = 'Open Zoom Classroom content';
$string['launchpage'] = 'Zoom Classroom';
$string['launchinstructions'] = 'Choose content in Zoom Classroom. When you finish, it will be inserted back into the editor.';
$string['launchfailed'] = 'Unable to start deep linking for the configured external tool.';
$string['invalidresponse'] = 'The LTI provider returned an invalid deep-linking response.';
$string['invalidlaunch'] = 'The Zoom Classroom launch request was invalid.';
