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
 * Settings for tiny_zoomclassroom.
 *
 * @package     tiny_zoomclassroom
 * @copyright   2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $ADMIN, $DB;

$ADMIN->add('editortiny', new admin_category('tiny_zoomclassroom', new lang_string('pluginname', 'tiny_zoomclassroom')));

$settings = new admin_settingpage('tiny_zoomclassroom_settings', new lang_string('settings', 'tiny_zoomclassroom'));

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'tiny_zoomclassroom/toolheading',
        new lang_string('toolheading', 'tiny_zoomclassroom'),
        new lang_string('toolheading_desc', 'tiny_zoomclassroom')
    ));

    $options = [0 => get_string('notconfigured', 'tiny_zoomclassroom')];
    if ($DB->get_manager()->table_exists('lti_types')) {
        $records = $DB->get_records('lti_types', [], 'name ASC', 'id, name');
        foreach ($records as $record) {
            $config = lti_get_type_type_config((int)$record->id);
            if (($config->lti_ltiversion ?? '') !== LTI_VERSION_1P3) {
                continue;
            }

            $label = trim((string)($record->name ?? ''));
            if ($label === '') {
                $label = get_string('unnamedtool', 'tiny_zoomclassroom', $record->id);
            }
            $options[(int)$record->id] = '[' . $record->id . '] ' . $label;
        }
    }

        $settings->add(new admin_setting_configselect(
            'tiny_zoomclassroom/toolid',
            new lang_string('toolid', 'tiny_zoomclassroom'),
            new lang_string('toolid_desc', 'tiny_zoomclassroom'),
            0,
            $options
        ));

    $settings->add(new admin_setting_configtext(
        'tiny_zoomclassroom/popupwidth',
        new lang_string('popupwidth', 'tiny_zoomclassroom'),
        new lang_string('popupwidth_desc', 'tiny_zoomclassroom'),
        '1200',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'tiny_zoomclassroom/popupheight',
        new lang_string('popupheight', 'tiny_zoomclassroom'),
        new lang_string('popupheight_desc', 'tiny_zoomclassroom'),
        '800',
        PARAM_INT
    ));
}

$ADMIN->add('tiny_zoomclassroom', $settings);
