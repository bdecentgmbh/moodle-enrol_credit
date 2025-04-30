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
 * Enrolment form displayed on the course enrol page.
 *
 * @package    enrol_credit
 * @copyright  2021 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_credit\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Enrol form called from course enrolment hook, Using this form user will enrol into course from course enrol page.
 */
class enrol_form extends \moodleform {

    /**
     * Custom data related to the enrol plugin instance.
     *
     * @var stdclass
     */
    protected $instance;

    /**
     * Status of max enroled users are reached.
     *
     * @var bool
     */
    protected $toomany = false;

    /**
     * Overriding this function to get unique form id for multiple credit enrolments.
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->id.'_'.get_class($this);
        return $formid;
    }

    /**
     * Credits enrol form elements and notifications are defined.
     *
     * @return void
     */
    public function definition(): void {
        global $USER, $OUTPUT, $CFG;

        $mform = $this->_form;
        $instance = $this->_customdata;
        $this->instance = $instance;
        $plugin = enrol_get_plugin('credit');

        $heading = $plugin->get_instance_name($instance);
        $mform->addElement('header', 'creditheader', $heading);

        $mform->addElement('html', get_string('checkout', 'enrol_credit',
            ['credit_cost' => $instance->customint7, 'user_credits' => \enrol_credit_plugin::get_user_credits($USER->id)]));

        $this->add_action_buttons(false, get_string('purchase', 'enrol_credit'));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $instance->courseid);

        $mform->addElement('hidden', 'instance');
        $mform->setType('instance', PARAM_INT);
        $mform->setDefault('instance', $instance->id);
    }

    /**
     * Validate the processed data is valid and user is qulified to enrol into course using there credits.
     *
     * @param array $data User data to enrol.
     * @param array $files List of files data submitted from form.
     * @return array $errors List of errors prevent the user enrollment.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $instance = $this->instance;

        if ($this->toomany) {
            $errors['notice'] = get_string('error');
            return $errors;
        }

        return $errors;
    }
}
