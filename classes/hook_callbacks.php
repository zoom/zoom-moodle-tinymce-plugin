<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tiny_zoomclassroom;

use core\hook\output\before_footer_html_generation;

/**
 * Hook callbacks for global Zoom Classroom placeholder hydration.
 *
 * @package     tiny_zoomclassroom
 * @copyright   2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Load the global placeholder renderer on standard pages.
     *
     * @param before_footer_html_generation $hook
     * @return void
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE;

        if (during_initial_install()) {
            return;
        }

        if (in_array($PAGE->pagelayout, ['maintenance', 'redirect'], true)) {
            return;
        }

        $PAGE->requires->js_call_amd('tiny_zoomclassroom/render', 'init');
    }
}
