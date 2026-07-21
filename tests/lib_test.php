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

use enrol_credit_plugin;
use context_course;

/**
 * Credit enrolment plugin tests.
 *
 * @package    enrol_credit
 * @copyright  2026 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(enrol_credit_plugin::class)]
final class lib_test extends \advanced_testcase {
    /**
     * Enable the credit enrolment plugin.
     *
     * @return void
     */
    protected function enable_plugin(): void {
        $enabled = enrol_get_plugins(true);
        $enabled['credit'] = true;
        $enabled = array_keys($enabled);
        set_config('enrol_plugins_enabled', implode(',', $enabled));
    }

    /**
     * Create the credit profile field and map it in the plugin settings.
     *
     * @return \stdClass profile field record
     */
    protected function setup_credit_field(): \stdClass {
        $field = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => 'credit',
            'name' => 'Credit',
        ]);
        set_config('credit_field', $field->id, 'enrol_credit');
        return $field;
    }

    /**
     * Create a course with an enabled credit enrolment instance.
     *
     * @param int $cost credit cost of the course
     * @return array [course record, enrol instance record, plugin]
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

        return [$course, $instance, $plugin];
    }

    /**
     * Basic plugin presence checks.
     *
     * @return void
     */
    public function test_basics(): void {
        $this->resetAfterTest();
        $this->enable_plugin();

        $this->assertTrue(enrol_is_enabled('credit'));
        $plugin = enrol_get_plugin('credit');
        $this->assertInstanceOf('enrol_credit_plugin', $plugin);
    }

    /**
     * Adding an instance without fields must not raise deprecations on PHP 8.4.
     *
     * @return void
     */
    public function test_add_instance_null_fields(): void {
        global $DB;

        $this->resetAfterTest();
        $this->enable_plugin();

        $course = $this->getDataGenerator()->create_course();
        $plugin = enrol_get_plugin('credit');
        $instanceid = $plugin->add_instance($course);

        $this->assertNotEmpty($instanceid);
        $this->assertTrue($DB->record_exists('enrol', ['id' => $instanceid, 'enrol' => 'credit']));
    }

    /**
     * Expiry notify option is split into two columns on add.
     *
     * @return void
     */
    public function test_add_instance_expirynotify(): void {
        global $DB;

        $this->resetAfterTest();
        $this->enable_plugin();

        $course = $this->getDataGenerator()->create_course();
        $plugin = enrol_get_plugin('credit');
        $instanceid = $plugin->add_instance($course, ['expirynotify' => 2]);
        $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);

        $this->assertEquals(1, $instance->expirynotify);
        $this->assertEquals(1, $instance->notifyall);
    }

    /**
     * Add default instance uses the configured defaults.
     *
     * @return void
     */
    public function test_add_default_instance(): void {
        global $DB;

        $this->resetAfterTest();
        $this->enable_plugin();

        $course = $this->getDataGenerator()->create_course();
        $plugin = enrol_get_plugin('credit');
        $instanceid = $plugin->add_default_instance($course);
        $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);

        $this->assertEquals('credit', $instance->enrol);
        $this->assertEquals($plugin->get_config('roleid'), $instance->roleid);
    }

    /**
     * Credits are read from the mapped profile field.
     *
     * @return void
     */
    public function test_credit_accounting(): void {
        $this->resetAfterTest();
        $this->enable_plugin();
        $this->setup_credit_field();

        $user = $this->getDataGenerator()->create_user();

        // Without any credits assigned yet.
        $this->assertEquals(0, enrol_credit_plugin::get_user_credits($user->id));

        enrol_credit_plugin::add_credits($user->id, 100);
        $this->assertEquals(100, enrol_credit_plugin::get_user_credits($user->id));

        enrol_credit_plugin::add_credits($user->id, 20);
        $this->assertEquals(120, enrol_credit_plugin::get_user_credits($user->id));

        enrol_credit_plugin::deduct_credits($user->id, 50);
        $this->assertEquals(70, enrol_credit_plugin::get_user_credits($user->id));
    }

    /**
     * Credits default to zero when no profile field is mapped.
     *
     * @return void
     */
    public function test_get_user_credits_unconfigured(): void {
        $this->resetAfterTest();
        $this->enable_plugin();

        $user = $this->getDataGenerator()->create_user();
        $this->assertEquals(0, enrol_credit_plugin::get_user_credits($user->id));
    }

    /**
     * Users can only self enrol with sufficient credits.
     *
     * @return void
     */
    public function test_can_self_enrol(): void {
        $this->resetAfterTest();
        $this->enable_plugin();
        $this->setup_credit_field();

        [$course, $instance, $plugin] = $this->create_course_with_instance(20);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // No credits yet: enrolment must be refused.
        $status = $plugin->can_self_enrol($instance);
        $this->assertNotTrue($status);
        $this->assertStringContainsString('insufficient', strip_tags($status));

        // With enough credits the user may enrol.
        enrol_credit_plugin::add_credits($user->id, 100);
        $this->assertTrue($plugin->can_self_enrol($instance));

        // New enrolments disabled.
        $instance->customint6 = 0;
        $this->assertNotTrue($plugin->can_self_enrol($instance));
    }

    /**
     * Self enrolment enrols the user and deducts the credit cost.
     *
     * @return void
     */
    public function test_enrol_self(): void {
        $this->resetAfterTest();
        $this->enable_plugin();
        $this->setup_credit_field();

        [$course, $instance, $plugin] = $this->create_course_with_instance(20);

        $user = $this->getDataGenerator()->create_user();
        enrol_credit_plugin::add_credits($user->id, 100);
        $this->setUser($user);

        $plugin->enrol_self($instance, $user);

        $context = context_course::instance($course->id);
        $this->assertTrue(is_enrolled($context, $user));
        $this->assertEquals(80, enrol_credit_plugin::get_user_credits($user->id));
    }

    /**
     * Update instance keeps the expirynotify/notifyall pairing consistent.
     *
     * @return void
     */
    public function test_update_instance(): void {
        global $DB;

        $this->resetAfterTest();
        $this->enable_plugin();

        [$course, $instance, $plugin] = $this->create_course_with_instance(20);

        $data = clone $instance;
        $data->expirynotify = 2;
        $plugin->update_instance($instance, $data);

        $instance = $DB->get_record('enrol', ['id' => $instance->id], '*', MUST_EXIST);
        $this->assertEquals(1, $instance->expirynotify);
        $this->assertEquals(1, $instance->notifyall);
    }

    /**
     * Deduction fails and leaves the balance untouched when credits are insufficient.
     *
     * @return void
     */
    public function test_deduct_credits_insufficient(): void {
        $this->resetAfterTest();
        $this->enable_plugin();
        $this->setup_credit_field();

        $user = $this->getDataGenerator()->create_user();
        enrol_credit_plugin::add_credits($user->id, 10);

        $this->assertFalse(enrol_credit_plugin::deduct_credits($user->id, 20));
        $this->assertEquals(10, enrol_credit_plugin::get_user_credits($user->id));

        $this->assertTrue(enrol_credit_plugin::deduct_credits($user->id, 10));
        $this->assertEquals(0, enrol_credit_plugin::get_user_credits($user->id));
    }

    /**
     * Negative amounts are rejected outright.
     *
     * @return void
     */
    public function test_deduct_credits_negative_rejected(): void {
        $this->resetAfterTest();
        $this->enable_plugin();
        $this->setup_credit_field();

        $user = $this->getDataGenerator()->create_user();

        $this->expectException(\coding_exception::class);
        enrol_credit_plugin::deduct_credits($user->id, -5);
    }

    /**
     * Negative additions are rejected outright.
     *
     * @return void
     */
    public function test_add_credits_negative_rejected(): void {
        $this->resetAfterTest();
        $this->enable_plugin();
        $this->setup_credit_field();

        $user = $this->getDataGenerator()->create_user();

        $this->expectException(\coding_exception::class);
        enrol_credit_plugin::add_credits($user->id, -5);
    }

    /**
     * A non-numeric profile field value is treated as a zero balance.
     *
     * @return void
     */
    public function test_get_user_credits_non_numeric(): void {
        global $DB;

        $this->resetAfterTest();
        $this->enable_plugin();
        $field = $this->setup_credit_field();

        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('user_info_data', (object) [
            'userid' => $user->id,
            'fieldid' => $field->id,
            'data' => 'not a number',
        ]);

        $this->assertEquals(0, enrol_credit_plugin::get_user_credits($user->id));
    }

    /**
     * Self enrolment is refused, without enrolling, when the balance is insufficient.
     *
     * This simulates the race where credits are spent between the can_self_enrol()
     * check and the actual enrolment.
     *
     * @return void
     */
    public function test_enrol_self_insufficient_credits(): void {
        $this->resetAfterTest();
        $this->enable_plugin();
        $this->setup_credit_field();

        [$course, $instance, $plugin] = $this->create_course_with_instance(20);

        $user = $this->getDataGenerator()->create_user();
        enrol_credit_plugin::add_credits($user->id, 10);
        $this->setUser($user);

        $this->assertFalse($plugin->enrol_self($instance, $user));
        $this->assertFalse(is_enrolled(context_course::instance($course->id), $user));
        $this->assertEquals(10, enrol_credit_plugin::get_user_credits($user->id));
    }

    /**
     * Instance validation rejects a negative credit cost.
     *
     * @return void
     */
    public function test_edit_instance_validation_negative_cost(): void {
        global $DB;

        $this->resetAfterTest();
        $this->enable_plugin();

        [$course, $instance, $plugin] = $this->create_course_with_instance(20);
        $context = context_course::instance($course->id);

        $data = (array) $instance;
        $data['expirynotify'] = 0;
        $data['customint7'] = -5;

        $errors = $plugin->edit_instance_validation($data, [], $instance, $context);
        $this->assertArrayHasKey('customint7', $errors);

        $data['customint7'] = 5;
        $errors = $plugin->edit_instance_validation($data, [], $instance, $context);
        $this->assertArrayNotHasKey('customint7', $errors);
    }

    /**
     * Sync does not throw errors when there is nothing to do.
     *
     * @return void
     */
    public function test_sync_nothing(): void {
        global $SITE;

        $this->resetAfterTest();
        $this->enable_plugin();

        $plugin = enrol_get_plugin('credit');
        $trace = new \null_progress_trace();

        $plugin->sync($trace, null);
        $plugin->sync($trace, $SITE->id);
    }
}
