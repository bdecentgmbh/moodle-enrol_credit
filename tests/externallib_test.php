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

namespace enrol_credit;

use enrol_credit_external;
use enrol_credit_plugin;
use context_course;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/enrol/credit/externallib.php');

/**
 * Credit enrolment external functions tests.
 *
 * @package    enrol_credit
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(enrol_credit_external::class)]
final class externallib_test extends \advanced_testcase {
    /**
     * Enable the credit enrolment plugin and map the credit profile field.
     *
     * @return void
     */
    protected function setup_plugin(): void {
        $enabled = enrol_get_plugins(true);
        $enabled['credit'] = true;
        $enabled = array_keys($enabled);
        set_config('enrol_plugins_enabled', implode(',', $enabled));

        $field = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'credit',
            'name' => 'Credit',
        ]);
        set_config('credit_field', $field->id, 'enrol_credit');
    }

    /**
     * Create a course with an enabled credit enrolment instance.
     *
     * @param int $cost credit cost of the course
     * @return array [course record, enrol instance record]
     */
    protected function create_course_with_instance(int $cost): array {
        global $DB;

        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $course = $this->getDataGenerator()->create_course();
        $plugin = enrol_get_plugin('credit');
        $instanceid = $plugin->add_instance($course, [
            'status' => ENROL_INSTANCE_ENABLED,
            'roleid' => $studentrole->id,
            'customint4' => ENROL_DO_NOT_SEND_EMAIL,
            'customint6' => 1,
            'customint7' => $cost,
        ]);
        $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);

        return [$course, $instance];
    }

    /**
     * Users with enough credits can enrol through the web service.
     *
     * @return void
     */
    public function test_enrol_user(): void {
        $this->resetAfterTest();
        $this->setup_plugin();

        [$course, $instance] = $this->create_course_with_instance(20);

        $user = $this->getDataGenerator()->create_user();
        enrol_credit_plugin::add_credits($user->id, 100);
        $this->setUser($user);

        $result = enrol_credit_external::enrol_user($course->id);
        $result = \core_external\external_api::clean_returnvalue(enrol_credit_external::enrol_user_returns(), $result);

        $this->assertTrue($result['status']);
        $this->assertEmpty($result['warnings']);
        $this->assertTrue(is_enrolled(context_course::instance($course->id), $user));
        $this->assertEquals(80, enrol_credit_plugin::get_user_credits($user->id));
    }

    /**
     * Users without enough credits receive a warning and are not enrolled.
     *
     * @return void
     */
    public function test_enrol_user_insufficient_credits(): void {
        $this->resetAfterTest();
        $this->setup_plugin();

        [$course, $instance] = $this->create_course_with_instance(20);

        $user = $this->getDataGenerator()->create_user();
        enrol_credit_plugin::add_credits($user->id, 10);
        $this->setUser($user);

        $result = enrol_credit_external::enrol_user($course->id);
        $result = \core_external\external_api::clean_returnvalue(enrol_credit_external::enrol_user_returns(), $result);

        $this->assertFalse($result['status']);
        $this->assertCount(1, $result['warnings']);
        $this->assertFalse(is_enrolled(context_course::instance($course->id), $user));
        $this->assertEquals(10, enrol_credit_plugin::get_user_credits($user->id));
    }

    /**
     * Instance information is returned for a credit enrolment instance.
     *
     * @return void
     */
    public function test_get_instance_info(): void {
        $this->resetAfterTest();
        $this->setup_plugin();

        [$course, $instance] = $this->create_course_with_instance(20);

        $user = $this->getDataGenerator()->create_user();
        enrol_credit_plugin::add_credits($user->id, 100);
        $this->setUser($user);

        $result = enrol_credit_external::get_instance_info($instance->id);

        $this->assertEquals($instance->id, $result['id']);
        $this->assertEquals($course->id, $result['courseid']);
        $this->assertEquals('credit', $result['type']);
    }

    /**
     * Credits can be topped up through the web service.
     *
     * @return void
     */
    public function test_credit_users(): void {
        $this->resetAfterTest();
        $this->setup_plugin();
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();

        $result = enrol_credit_external::credit_users([
            ['userid' => $user->id, 'credit' => 10, 'quantity' => 3],
        ]);
        $result = \core_external\external_api::clean_returnvalue(enrol_credit_external::credit_users_returns(), $result);

        $this->assertTrue($result['status']);
        $this->assertEquals(30, enrol_credit_plugin::get_user_credits($user->id));
    }
}
