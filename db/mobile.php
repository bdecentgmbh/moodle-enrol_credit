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
 * Moodle App support declaration for the credit enrolment plugin.
 *
 * Requires Moodle App 4.3 or later (CoreEnrolDelegate).
 *
 * @package    enrol_credit
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'enrol_credit' => [
        'handlers' => [
            'credit' => [
                'delegate' => 'CoreEnrolDelegate',
                'enrolmentAction' => 'self',
                'method' => 'mobile_enrol',
                'infoIcons' => [
                    [
                        'icon' => 'fas-coins',
                        'label' => 'plugin.enrol_credit.pluginname',
                    ],
                ],
            ],
        ],
        'lang' => [
            ['pluginname', 'enrol_credit'],
            ['checkout', 'enrol_credit'],
            ['purchase', 'enrol_credit'],
            ['canntenrol', 'enrol_credit'],
            ['insufficient_credits', 'enrol_credit'],
            ['enrolfailed', 'enrol_credit'],
        ],
    ],
];
