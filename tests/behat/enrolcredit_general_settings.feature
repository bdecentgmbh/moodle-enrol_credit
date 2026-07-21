@enrol @enrol_credit @enrolcredit_general_settings @javascript
Feature: Credit enrolment general settings

  Background:
    Given the following "custom profile fields" exist:
      | datatype | shortname | name    | visible |
      | text     | credit    | Credit  | 0       |
    And the following "course" exist:
      | fullname | shortname | category |
      | Test     | C1        | 0        |
      | Course B | C2        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email             | profile_field_credit |
      | student1 | Student   | User 1   | student1@test.com | 100                  |
      | student2 | Student   | User 2   | student2@test.com | 100                  |
      | teacher1 | Teacher   | User 1   | teacher1@test.com | 100                  |
      | teacher2 | Teacher   | User 2   | teacher2@test.com | 100                  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "admin"
    And I navigate to "Plugins > Enrolments > Manage enrol plugins" in site administration
    And I click on "Enable" "link" in the "Course credit enrolment" "table_row"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Profile field mapping" to "Credit"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: General settings: Add credit enrolment instances to new coureses
    Given I log in as "admin"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Add instance to new courses" to "1"
    And I set the field "Allow existing enrolments" to "Yes"
    And I press "Save changes"
    And I am on the "My courses" page
    And I click on "Create course" "button"
    And I should see "Add a new course"
    And I set the following fields to these values:
      | Course full name  | Credit enrolment enable course |
      | Course short name | EC1                            |
    And I press "Save and display"
    And I am on the "Credit enrolment enable course" "enrolment methods" page
    And I should see "Course credit enrolment (Student)" in the "generaltable" "table"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Add instance to new courses" to "0"
    And I press "Save changes"
    And I am on the "My courses" page
    And I click on "Create course" "button"
    And I should see "Add a new course"
    And I set the following fields to these values:
      | Course full name  | Credit enrolment disbable course |
      | Course short name | EC2                              |
    And I press "Save and display"
    And I am on the "Credit enrolment disbable course" "enrolment methods" page
    And I should not see "Course credit enrolment (Student)" in the "generaltable" "table"
    And I log out

  @javascript
  Scenario: General settings: Allow existing enrolments in new courses
    Given I log in as "admin"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Allow existing enrolments" to "No"
    And I press "Save changes"
    And I am on the "My courses" page
    And I click on "Create course" "button"
    And I should see "Add a new course"
    And I set the following fields to these values:
      | Course full name  | Credit enrol test |
      | Course short name | CET1              |
    And I press "Save and display"
    And I am on the "Credit enrol test" "enrolment methods" page
    And I should see "Course credit enrolment (Student)" in the "generaltable" "table"
    And "Enable" "link" should exist in the "Course credit enrolment (Student)" "table_row"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Allow existing enrolments" to "Yes"
    And I press "Save changes"
    And I am on the "My courses" page
    And I click on "Create course" "button"
    And I should see "Add a new course"
    And I set the following fields to these values:
      | Course full name  | Credit enrol test 1 |
      | Course short name | CET2                |
    And I press "Save and display"
    And I am on the "Credit enrol test 1" "enrolment methods" page
    And I should see "Course credit enrolment (Student)" in the "generaltable" "table"
    And "Disable" "link" should exist in the "Course credit enrolment (Student)" "table_row"
    And I log out

  @javascript
  Scenario: General settings: Default role assignment
    Given I log in as "admin"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Allow existing enrolments" to "Yes"
    And I set the field "Default role assignment" to "Teacher"
    And I press "Save changes"
    And I am on the "My courses" page
    And I click on "Create course" "button"
    And I should see "Add a new course"
    And I set the following fields to these values:
      | Course full name  | Credit enrolment test |
      | Course short name | CE1                   |
    And I press "Save and display"
    And I am on the "Credit enrolment test" "enrolment methods" page
    And I should see "Course credit enrolment (Teacher)" in the "generaltable" "table"
    And "Disable" "link" should exist in the "Course credit enrolment (Teacher)" "table_row"
    And I click on "Edit" "link" in the "Course credit enrolment (Teacher)" "table_row"
    And the field "Default assigned role" matches value "Teacher"
    And I press "Save changes"
    And I log out
    And I am on the "Credit enrolment test" course page logged in as teacher1
    And I should see "Course credit enrolment (Teacher)"
    And I should see "course credits will be deducted from your balance of 100."
    And I click on "Purchase" "button"
    And I navigate to course participants
    Then I should see "Teacher" in the "Teacher User 1" "table_row"
    And I log out

  @javascript
  Scenario: General settings: Enrolment duration
    Given I log in as "admin"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Allow existing enrolments" to "Yes"
    And I set the following fields to these values:
      | id_s_enrol_credit_enrolperiodv | 1       |
      | id_s_enrol_credit_enrolperiodu | minutes |
    And I press "Save changes"
    And I am on the "My courses" page
    And I click on "Create course" "button"
    And I should see "Add a new course"
    And I set the following fields to these values:
      | Course full name  | Credit enrolment test |
      | Course short name | CE1                   |
    And I press "Save and display"
    And I am on the "Credit enrolment test" "enrolment methods" page
    And I should see "Course credit enrolment (Student)" in the "generaltable" "table"
    And "Disable" "link" should exist in the "Course credit enrolment (Student)" "table_row"
    And I click on "Edit" "link" in the "Course credit enrolment (Student)" "table_row"
    Then the following fields match these values:
      | enrolperiod[number]   | 1       |
      | enrolperiod[timeunit] | minutes |
      | enrolperiod[enabled]  | 1       |
    And I press "Save changes"
    And I log out
    And I am on the "Credit enrolment test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "course credits will be deducted from your balance of 100."
    And I click on "Purchase" "button"
    And I am on the "Credit enrolment test" course page
    And I navigate to course participants
    Then I should see "Student" in the "Student User 1" "table_row"
    And I wait "60" seconds
    And I log out
    And I am on the "Credit enrolment test" course page logged in as admin
    And I navigate to course participants
    And I should see "Not current" in the "Student User 1" "table_row"
    And I log out

  @javascript
  Scenario: General settings: Max enrolled users
    Given I log in as "admin"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Allow existing enrolments" to "Yes"
    And I set the field "Max enrolled users" to "1"
    And I press "Save changes"
    And I am on the "My courses" page
    And I click on "Create course" "button"
    And I should see "Add a new course"
    And I set the following fields to these values:
      | Course full name  | Credit enrolment test |
      | Course short name | CE1                   |
    And I press "Save and display"
    And I am on the "Credit enrolment test" "enrolment methods" page
    And I should see "Course credit enrolment (Student)" in the "generaltable" "table"
    And "Disable" "link" should exist in the "Course credit enrolment (Student)" "table_row"
    And I click on "Edit" "link" in the "Course credit enrolment (Student)" "table_row"
    And the field "Max enrolled users" matches value "1"
    And I log out
    And I am on the "Credit enrolment test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "course credits will be deducted from your balance of 100."
    And I click on "Purchase" "button"
    And I log out
    And I am on the "Credit enrolment test" course page logged in as student2
    And I should see "Course credit enrolment (Student)"
    And I should see "Maximum number of users allowed to enrol was already reached."
    And I log out
