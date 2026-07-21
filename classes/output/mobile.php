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
 * Mobile output class for the credit enrolment plugin.
 *
 * @package    enrol_credit
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_credit\output;

/**
 * Provides the JavaScript implementing the Moodle App enrolment handler.
 */
class mobile {
    /**
     * Return the JavaScript implementing app support for credit enrolment.
     *
     * @param array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and other data
     */
    public static function mobile_enrol($args) {
        global $CFG;

        return [
            'templates' => [],
            'javascript' => file_get_contents($CFG->dirroot . '/enrol/credit/mobileapp/mobile.js'),
            'otherdata' => '',
            'files' => '',
        ];
    }
}
