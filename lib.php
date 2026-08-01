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

/**
 * Legacy callbacks for tiny_zoomclassroom.
 *
 * @package     tiny_zoomclassroom
 * @copyright   2026
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Legacy before-footer callback to ensure the placeholder renderer loads on
 * Moodle pages that do not execute the newer hook path.
 *
 * @return string
 */
function tiny_zoomclassroom_before_footer(): string {
    global $PAGE;

    if (during_initial_install()) {
        return '';
    }

    if (in_array($PAGE->pagelayout, ['maintenance', 'redirect'], true)) {
        return '';
    }

    $PAGE->requires->js_call_amd('tiny_zoomclassroom/render', 'init');
    return '';
}
