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

namespace tiny_zoomclassroom;

use context;
use editor_tiny\editor;
use editor_tiny\plugin;
use editor_tiny\plugin_with_buttons;
use editor_tiny\plugin_with_configuration;
use editor_tiny\plugin_with_menuitems;

/**
 * Tiny Zoom Classroom plugin definition.
 *
 * @package     tiny_zoomclassroom
 * @copyright   2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo extends plugin implements
    plugin_with_buttons,
    plugin_with_configuration,
    plugin_with_menuitems {

    /**
     * Plugin component name.
     */
    public const COMPONENT = 'tiny_zoomclassroom';

    /**
     * Get available toolbar buttons.
     *
     * @return array
     */
    public static function get_available_buttons(): array {
        return [
            'tiny_zoomclassroom/zoomclassroom',
        ];
    }

    /**
     * Get available menu items.
     *
     * @return array
     */
    public static function get_available_menuitems(): array {
        return [
            'tiny_zoomclassroom/zoomclassroom',
        ];
    }

    /**
     * Determine whether the plugin should be shown.
     *
     * @param context $context
     * @param array $options
     * @param array $fpoptions
     * @param editor|null $editor
     * @return bool
     */
    public static function is_enabled(
        context $context,
        array $options,
        array $fpoptions,
        ?editor $editor = null
    ): bool {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $toolid = (int)\get_config(self::COMPONENT, 'toolid');
        if (empty($toolid)) {
            return false;
        }

        if (!$DB->get_manager()->table_exists('lti_types')) {
            return false;
        }

        $config = \lti_get_type_type_config($toolid);
        if (($config->lti_ltiversion ?? '') !== LTI_VERSION_1P3) {
            return false;
        }

        $coursecontext = $context->get_course_context(false);
        if (!$coursecontext) {
            return false;
        }

        if ((int)$coursecontext->instanceid === SITEID) {
            return false;
        }

        return !\isguestuser();
    }

    /**
     * Provide editor configuration.
     *
     * @param context $context
     * @param array $options
     * @param array $fpoptions
     * @param editor|null $editor
     * @return array
     */
    public static function get_plugin_configuration_for_context(
        context $context,
        array $options,
        array $fpoptions,
        ?editor $editor = null
    ): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $toolid = (int)\get_config(self::COMPONENT, 'toolid');
        $tool = null;
        if ($toolid > 0 && $DB->get_manager()->table_exists('lti_types')) {
            $config = \lti_get_type_type_config($toolid);
            if (($config->lti_ltiversion ?? '') === LTI_VERSION_1P3) {
                $tool = $DB->get_record('lti_types', ['id' => $toolid], 'id, name');
            }
        }

        $courseid = 0;
        $coursecontext = $context->get_course_context(false);
        if ($coursecontext && (int)$coursecontext->instanceid !== SITEID) {
            $courseid = (int)$coursecontext->instanceid;
        }

        return [
            'courseid' => $courseid,
            'tool' => $tool ?: '',
            'launcherpath' => '/lib/editor/tiny/plugins/zoomclassroom/launcher.php',
            'sesskey' => \sesskey(),
            'popupwidth' => (string)((int)\get_config(self::COMPONENT, 'popupwidth') ?: 1200),
            'popupheight' => (string)((int)\get_config(self::COMPONENT, 'popupheight') ?: 800),
            'buttonlabel' => \get_string('buttonlabel', self::COMPONENT),
            'errormisconfigured' => \get_string('errormisconfigured', self::COMPONENT),
            'errornocourse' => \get_string('errornocourse', self::COMPONENT),
            'errorpopupblocked' => \get_string('errorpopupblocked', self::COMPONENT),
            'insertfallbacktext' => \get_string('insertfallbacktext', self::COMPONENT),
            'wwwroot' => $CFG->wwwroot,
        ];
    }
}
