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
 * credit enrol plugin external functions
 *
 * @package    enrol_credit
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;

/**
 * credit enrolment external functions.
 *
 * @package   enrol_credit
 * @copyright 2020 Derick Turner derick@e-learndesign.co.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.6
 */
class enrol_credit_external extends external_api {
    /**
     * Returns description of get_instance_info() parameters.
     *
     * @return external_function_parameters
     */
    public static function get_instance_info_parameters() {
        return new external_function_parameters(
            ['instanceid' => new external_value(PARAM_INT, 'instance id of credit enrolment plugin.')]
        );
    }

    /**
     * Return credit-enrolment instance information.
     *
     * @param int $instanceid instance id of credit enrolment plugin.
     * @return array instance information.
     * @throws moodle_exception
     */
    public static function get_instance_info($instanceid) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::get_instance_info_parameters(), ['instanceid' => $instanceid]);

        // Retrieve credit enrolment plugin.
        $enrolplugin = enrol_get_plugin('credit');
        if (empty($enrolplugin)) {
            throw new moodle_exception('invaliddata', 'error');
        }

        self::validate_context(context_system::instance());

        $enrolinstance = $DB->get_record('enrol', ['id' => $params['instanceid']], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $enrolinstance->courseid], '*', MUST_EXIST);
        if (!core_course_category::can_view_course_info($course) && !can_access_course($course)) {
            throw new moodle_exception('coursehidden');
        }

        $instanceinfo = (array) $enrolplugin->get_enrol_info($enrolinstance);

        return $instanceinfo;
    }

    /**
     * Returns description of get_instance_info() result value.
     *
     * @return external_description
     */
    public static function get_instance_info_returns() {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'id of course enrolment instance'),
                'courseid' => new external_value(PARAM_INT, 'id of course'),
                'type' => new external_value(PARAM_PLUGIN, 'type of enrolment plugin'),
                'name' => new external_value(PARAM_RAW, 'name of enrolment plugin'),
                'status' => new external_value(PARAM_RAW, 'status of enrolment plugin'),
                'enrolpassword' => new external_value(PARAM_RAW, 'password required for enrolment', VALUE_OPTIONAL),
            ]
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function enrol_user_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Id of the course'),
                'password' => new external_value(PARAM_RAW, 'Enrolment key', VALUE_DEFAULT, ''),
                'instanceid' => new external_value(PARAM_INT, 'Instance id of credit enrolment plugin.', VALUE_DEFAULT, 0),
            ]
        );
    }

    /**
     * credit enrol the current user in the given course.
     *
     * @param int $courseid id of course
     * @param string $password enrolment key
     * @param int $instanceid instance id of credit enrolment plugin
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function enrol_user($courseid, $password = '', $instanceid = 0) {
        global $CFG, $USER;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(
            self::enrol_user_parameters(),
            [
                'courseid' => $courseid,
                'password' => $password,
                'instanceid' => $instanceid,
            ]
        );

        $warnings = [];

        $course = get_course($params['courseid']);
        $context = \context_course::instance($course->id);
        self::validate_context(context_system::instance());

        if (!core_course_category::can_view_course_info($course)) {
            throw new moodle_exception('coursehidden');
        }

        // Retrieve the credit enrolment plugin.
        $enrol = enrol_get_plugin('credit');
        if (empty($enrol)) {
            throw new moodle_exception('canntenrol', 'enrol_credit');
        }

        // We can expect multiple credit-enrolment instances.
        $instances = [];
        $enrolinstances = enrol_get_instances($course->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "credit") {
                // Instance specified.
                if (!empty($params['instanceid'])) {
                    if ($courseenrolinstance->id == $params['instanceid']) {
                        $instances[] = $courseenrolinstance;
                        break;
                    }
                } else {
                    $instances[] = $courseenrolinstance;
                }
            }
        }
        if (empty($instances)) {
            throw new moodle_exception('canntenrol', 'enrol_credit');
        }

        // Try to enrol the user in the instance/s.
        $enrolled = false;
        foreach ($instances as $instance) {
            $enrolstatus = $enrol->can_self_enrol($instance);
            if ($enrolstatus === true) {
                // Do the enrolment, deducting the credit cost from the user. The deduction
                // is atomic and re-checks the balance, so a concurrent enrolment cannot
                // spend the same credits twice.
                if ($enrol->enrol_self($instance, $USER)) {
                    $enrolled = true;
                    break;
                }
                $warnings[] = [
                    'item' => 'instance',
                    'itemid' => $instance->id,
                    'warningcode' => '2',
                    'message' => get_string('insufficient_credits', 'enrol_credit', [
                        'credit_cost' => $instance->customint7,
                        'user_credits' => enrol_credit_plugin::get_user_credits($USER->id),
                    ]),
                ];
            } else {
                $warnings[] = [
                    'item' => 'instance',
                    'itemid' => $instance->id,
                    'warningcode' => '1',
                    'message' => $enrolstatus,
                ];
            }
        }

        $result = [];
        $result['status'] = $enrolled;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function enrol_user_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'status: true if the user is enrolled, false otherwise'),
                'warnings' => new external_warnings(),
            ]
        );
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function credit_users_parameters() {
        return new external_function_parameters(
            [
                'credits' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'userid' => new external_value(PARAM_INT, 'The user that is going to be given new course credits'),
                            'credit' => new external_value(PARAM_INT, 'The amount of credit to be added'),
                            'quantity' => new external_value(PARAM_INT, 'The quantity of credits to be added'),
                        ]
                    )
                ),
            ]
        );
    }

    /**
     * Add course credits to the given users.
     *
     * Function throws an exception at the first error encountered.
     * @param array $coursecredits Credits to add per user.
     * @since Moodle 2.2
     */
    public static function credit_users($coursecredits) {
        global $CFG;

        require_once($CFG->dirroot . '/enrol/credit/lib.php');

        $params = self::validate_parameters(
            self::credit_users_parameters(),
            ['credits' => $coursecredits]
        );

        self::validate_context(context_system::instance());
        require_capability('enrol/credit:managecredits', context_system::instance());

        foreach ($params['credits'] as $coursecredit) {
            if ($coursecredit['credit'] < 0 || $coursecredit['quantity'] < 1) {
                throw new invalid_parameter_exception('Credit must not be negative and quantity must be at least 1.');
            }
            // Ensure the target user exists and is neither deleted nor the guest user.
            $user = core_user::get_user($coursecredit['userid'], '*', MUST_EXIST);
            core_user::require_active_user($user);

            enrol_credit_plugin::add_credits($coursecredit['userid'], $coursecredit['credit'] * $coursecredit['quantity']);
        }
        $result = [];
        $result['status'] = true;
        $result['warnings'] = [];
        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return null
     * @since Moodle 2.2
     */
    public static function credit_users_returns() {
        return  new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'Status: true only if token is valid'),
                'warnings' => new external_warnings(),
            ]
        );
    }
}
