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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

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
                array('instanceid' => new external_value(PARAM_INT, 'instance id of credit enrolment plugin.'))
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

        $params = self::validate_parameters(self::get_instance_info_parameters(), array('instanceid' => $instanceid));

        // Retrieve credit enrolment plugin.
        $enrolplugin = enrol_get_plugin('credit');
        if (empty($enrolplugin)) {
            throw new moodle_exception('invaliddata', 'error');
        }

        self::validate_context(context_system::instance());

        $enrolinstance = $DB->get_record('enrol', array('id' => $params['instanceid']), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $enrolinstance->courseid), '*', MUST_EXIST);
        if (!core_course_category::can_view_course_info($course) && !can_access_course($course)) {
            throw new moodle_exception('coursehidden');
        }

        $instanceinfo = (array) $enrolplugin->get_enrol_info($enrolinstance);
        if (isset($instanceinfo['requiredparam']->enrolpassword)) {
            $instanceinfo['enrolpassword'] = $instanceinfo['requiredparam']->enrolpassword;
        }
        unset($instanceinfo->requiredparam);

        return $instanceinfo;
    }

    /**
     * Returns description of get_instance_info() result value.
     *
     * @return external_description
     */
    public static function get_instance_info_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'id of course enrolment instance'),
                'courseid' => new external_value(PARAM_INT, 'id of course'),
                'type' => new external_value(PARAM_PLUGIN, 'type of enrolment plugin'),
                'name' => new external_value(PARAM_RAW, 'name of enrolment plugin'),
                'status' => new external_value(PARAM_RAW, 'status of enrolment plugin'),
                'enrolpassword' => new external_value(PARAM_RAW, 'password required for enrolment', VALUE_OPTIONAL),
            )
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
            array(
                'courseid' => new external_value(PARAM_INT, 'Id of the course'),
                'password' => new external_value(PARAM_RAW, 'Enrolment key', VALUE_DEFAULT, ''),
                'instanceid' => new external_value(PARAM_INT, 'Instance id of credit enrolment plugin.', VALUE_DEFAULT, 0)
            )
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
        global $CFG;

        require_once($CFG->libdir . '/enrollib.php');

        $params = self::validate_parameters(self::enrol_user_parameters(),
                                            array(
                                                'courseid' => $courseid,
                                                'password' => $password,
                                                'instanceid' => $instanceid
                                            ));

        $warnings = array();

        $course = get_course($params['courseid']);
        $context = context_course::instance($course->id);
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
        $instances = array();
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
            $enrolstatus = $enrol->can_credit_enrol($instance);
            if ($enrolstatus === true) {
                if ($instance->password and $params['password'] !== $instance->password) {

                    // Check if we are using group enrolment keys.
                    if ($instance->customint1) {
                        require_once($CFG->dirroot . "/enrol/credit/locallib.php");

                        if (!enrol_credit_check_group_enrolment_key($course->id, $params['password'])) {
                            $warnings[] = array(
                                'item' => 'instance',
                                'itemid' => $instance->id,
                                'warningcode' => '2',
                                'message' => get_string('passwordinvalid', 'enrol_credit')
                            );
                            continue;
                        }
                    } else {
                        if ($enrol->get_config('showhint')) {
                            $hint = core_text::substr($instance->password, 0, 1);
                            $warnings[] = array(
                                'item' => 'instance',
                                'itemid' => $instance->id,
                                'warningcode' => '3',
                                'message' => s(get_string('passwordinvalidhint', 'enrol_credit', $hint)) // Message is PARAM_TEXT.
                            );
                            continue;
                        } else {
                            $warnings[] = array(
                                'item' => 'instance',
                                'itemid' => $instance->id,
                                'warningcode' => '4',
                                'message' => get_string('passwordinvalid', 'enrol_credit')
                            );
                            continue;
                        }
                    }
                }

                // Do the enrolment.
                $data = array('enrolpassword' => $params['password']);
                $enrol->enrol_credit($instance, (object) $data);
                $enrolled = true;
                break;
            } else {
                $warnings[] = array(
                    'item' => 'instance',
                    'itemid' => $instance->id,
                    'warningcode' => '1',
                    'message' => $enrolstatus
                );
            }
        }

        $result = array();
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
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if the user is enrolled, false otherwise'),
                'warnings' => new external_warnings()
            )
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
            array(
                'credits' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'userid' => new external_value(PARAM_INT, 'The user that is going to be given new course credits'),
                            'credit' => new external_value(PARAM_INT, 'The amount of credit to be added'),
                            'quantity' => new external_value(PARAM_INT, 'The quantity of credits to be added'),
                        )
                    )
                )
            )
        );
    }

    /**
     * Enrolment of users.
     *
     * Function throw an exception at the first error encountered.
     * @param array $coursecredits Credits for the course to enrol.
     * @since Moodle 2.2
     */
    public static function credit_users($coursecredits) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/enrol/credit/lib.php');

        $params = self::validate_parameters(self::credit_users_parameters(),
                array('credits' => $coursecredits));

        foreach ($coursecredits as $coursecredit) {
            enrol_credit_plugin::add_credits($coursecredit['userid'], $coursecredit['credit'] * $coursecredit['quantity']);
        }
        $result = array();
        $result['status'] = true;
        $result['warnings'] = array();
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
            array(
                'status' => new external_value(PARAM_BOOL, 'Status: true only if token is valid'),
                'warnings' => new external_warnings()
            )
        );
    }
}
