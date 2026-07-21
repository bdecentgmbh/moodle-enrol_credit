@enrol @enrol_credit @enrolcredit @javascript
Feature: Credit enrolment management

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
      | student3 | Student   | User 3   | student3@test.com | 10                   |
      | teacher1 | Teacher   | User 1   | teacher1@test.com | 100                  |
      | teacher2 | Teacher   | User 2   | teacher2@test.com | 100                  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "cohorts" exist:
      | name    | idnumber  |
      | Cohort1 | cohortid1 |
      | Cohort2 | cohortid2 |
    And the following "cohort members" exist:
      | user     | cohort    |
      | student1 | cohortid1 |
    And I log in as "admin"
    And I navigate to "Plugins > Enrolments > Manage enrol plugins" in site administration
    And I click on "Enable" "link" in the "Course credit enrolment" "table_row"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Profile field mapping" to "Credit"
    And I set the field "Allow existing enrolments" to "Yes"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Successful credit based enrolment with sufficient balance
    Given I log in as "admin"
    And I am on the "My courses" page
    And I click on "Create course" "button"
    And I should see "Add a new course"
    And I set the following fields to these values:
      | Course full name  | Credit enrolment test |
      | Course short name | EC1                   |
    And I press "Save and display"
    And I am on the "Credit enrolment test" "enrolment methods" page
    And I should see "Course credit enrolment (Student)" in the "generaltable" "table"
    And "Disable" "link" should exist in the "Course credit enrolment (Student)" "table_row"
    And I click on "Edit" "link" in the "Course credit enrolment (Student)" "table_row"
    And I set the field "Credit cost" to "20"
    And I press "Save changes"
    And I log out
    And I am on the "Credit enrolment test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "20 course credits will be deducted from your balance of 100."
    And I click on "Purchase" "button"
    Then I should see "New section"
    And I log out
    And I am on the "Credit enrolment test" course page logged in as admin
    And I navigate to course participants
    And I should see "Student" in the "Student User 1" "table_row"
    And I log out

  @javascript
  Scenario: Insufficient Credit Balance for Course Enrolment
    Given I log in as "admin"
    And I am on the "Test" "enrolment methods" page
    And I select "Course credit enrolment" from the "Add method" singleselect
    And I set the field "Credit cost" to "20"
    And I press "Add method"
    And I log out
    And I am on the "Test" course page logged in as student3
    And I should see "Course credit enrolment (Student)"
    And I should see "You have insufficient course credits to enroll. 20 credits are required, your balance is 10."
    And I log out

  @javascript
  Scenario: Credit enrolment method active but new enrolments disabled
    Given I log in as "admin"
    And I am on the "Test" "enrolment methods" page
    And I select "Course credit enrolment" from the "Add method" singleselect
    And I set the field "Allow new enrolments" to "No"
    And I press "Add method"
    And I log out
    And I am on the "Test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "Enrolment is disabled or inactive"
    And I log out

  @javascript
  Scenario: Credit enrolment not yet started
    Given I log in as "admin"
    And I am on the "Test" "enrolment methods" page
    And I select "Course credit enrolment" from the "Add method" singleselect
    And I wait "2" seconds
    And I set the following fields to these values:
      | id_enrolstartdate_enabled | 1            |
      | Start date                | ##tomorrow## |
    And I press "Add method"
    And I log out
    And I am on the "Test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "You cannot enrol yet; enrolment starts on"
    And I should see "##tomorrow## %d %B %Y, %I:%M %p##" in the ".form-control-static" "css_element"
    And I log out
    And I am on the "Test" course page logged in as admin
    And I am on the "Test" "enrolment methods" page
    And I should see "Course credit enrolment (Student)" in the "generaltable" "table"
    And "Disable" "link" should exist in the "Course credit enrolment (Student)" "table_row"
    And I click on "Edit" "link" in the "Course credit enrolment (Student)" "table_row"
    And I set the field "Credit cost" to "20"
    And I set the following fields to these values:
      | Start date  | ##today## |
    And I press "Save changes"
    And I log out
    And I am on the "Test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "20 course credits will be deducted from your balance of 100."
    And I click on "Purchase" "button"
    Then I should see "New section"
    And I log out
    And I am on the "Test" course page logged in as admin
    And I navigate to course participants
    And I should see "Student" in the "Student User 1" "table_row"
    And I log out

  @javascript
  Scenario: Credit enrolment method active but enrolment end date has passed
    Given I log in as "admin"
    And I am on the "Test" "enrolment methods" page
    And I select "Course credit enrolment" from the "Add method" singleselect
    And I wait "2" seconds
    And I set the following fields to these values:
      | id_enrolstartdate_enabled | 1         |
      | Start date                | ##today## |
      | id_enrolenddate_enabled   | 1         |
      | End date                  | ##today## |
    And I press "Add method"
    And I log out
    And I am on the "Test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "You cannot enrol any more, since enrolment ended on"
    And I should see "##today## %d %B %Y, %I:%M %p##" in the ".form-control-static" "css_element"
    And I log out
    And I am on the "Test" course page logged in as admin
    And I am on the "Test" "enrolment methods" page
    And I should see "Course credit enrolment (Student)" in the "generaltable" "table"
    And "Disable" "link" should exist in the "Course credit enrolment (Student)" "table_row"
    And I click on "Edit" "link" in the "Course credit enrolment (Student)" "table_row"
    And I set the field "Credit cost" to "20"
    And I set the following fields to these values:
      | End date | ##tomorrow## |
    And I press "Save changes"
    And I log out
    And I am on the "Test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "20 course credits will be deducted from your balance of 100."
    And I click on "Purchase" "button"
    Then I should see "New section"
    And I log out
    And I am on the "Test" course page logged in as admin
    And I navigate to course participants
    And I should see "Student" in the "Student User 1" "table_row"
    And I log out

  @javascript
  Scenario: Credit enrolment restricted to cohort members
    Given I log in as "admin"
    And I am on the "Test" "enrolment methods" page
    And I select "Course credit enrolment" from the "Add method" singleselect
    And I set the field "Credit cost" to "20"
    And I set the field "Only cohort members" to "Cohort1"
    And I press "Add method"
    And I log out
    And I am on the "Test" course page logged in as student2
    And I should see "Course credit enrolment (Student)"
    And I should see "Only members of cohort 'Cohort1' can enrol."
    And I log out
    And I am on the "Test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "20 course credits will be deducted from your balance of 100."
    And I click on "Purchase" "button"
    Then I should see "New section"
    And I log out
    And I am on the "Test" course page logged in as admin
    And I navigate to course participants
    And I should see "Student" in the "Student User 1" "table_row"
    And I log out

  @javascript
  Scenario: Successfully being enrolled in the course after the purchase
    Given I log in as "admin"
    And I am on the "Test" "enrolment methods" page
    And I select "Course credit enrolment" from the "Add method" singleselect
    And I wait "2" seconds
    And I set the field "Credit cost" to "20"
    And I press "Add method"
    And I navigate to "Users > Browse list of users" in site administration
    And I should see "Student User 1"
    And I open the action menu in "Student User 1" "table_row"
    And I choose "Edit" in the open action menu
    And I follow "Expand all"
    And I wait "2" seconds
    And the field "Credit" in the "Testing" "fieldset" matches value "100"
    And I log out
    And I am on the "Test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "20 course credits will be deducted from your balance of 100."
    And I click on "Purchase" "button"
    Then I should see "New section"
    And I log out
    And I am on the "Test" course page logged in as admin
    And I navigate to course participants
    And I should see "Student" in the "Student User 1" "table_row"
    And I navigate to "Users > Browse list of users" in site administration
    And I should see "Student User 1"
    And I open the action menu in "Student User 1" "table_row"
    And I choose "Edit" in the open action menu
    And I follow "Expand all"
    And I wait "2" seconds
    And the field "Credit" in the "Testing" "fieldset" matches value "80"
    And I log out

  @javascript
  Scenario: Credit enrolment expiry action: Disable course enrolment and remove roles option
    Given I log in as "admin"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Enrolment expiry action" to "Disable course enrolment and remove roles"
    And I press "Save changes"
    And I am on the "Test" "enrolment methods" page
    And I select "Course credit enrolment" from the "Add method" singleselect
    And I wait "2" seconds
    And I set the following fields to these values:
      | Credit cost           | 20      |
      | enrolperiod[enabled]  | 1       |
      | enrolperiod[number]   | 20      |
      | enrolperiod[timeunit] | seconds |
    And I press "Add method"
    And I log out
    And I am on the "Test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "20 course credits will be deducted from your balance of 100."
    And I click on "Purchase" "button"
    Then I should see "New section"
    And I log out
    And I am on the "Test" course page logged in as admin
    And I navigate to course participants
    And I should see "Student" in the "Student User 1" "table_row"
    And I should see "Active" in the "Student User 1" "table_row"
    And I wait "20" seconds
    And I trigger cron
    And I am on "Test" course homepage
    And I navigate to course participants
    Then I should see "No roles" in the "Student User 1" "table_row"
    And I should see "Suspended" in the "Student User 1" "table_row"
    And I log out

  @javascript
  Scenario: Credit enrolment expiry action: unenrol user from course option
    Given I log in as "admin"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Enrolment expiry action" to "Unenrol user from course"
    And I press "Save changes"
    And I am on the "Test" "enrolment methods" page
    And I select "Course credit enrolment" from the "Add method" singleselect
    And I wait "2" seconds
    And I set the following fields to these values:
      | Credit cost           | 20      |
      | enrolperiod[enabled]  | 1       |
      | enrolperiod[number]   | 20      |
      | enrolperiod[timeunit] | seconds |
    And I press "Add method"
    And I log out
    And I am on the "Test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "20 course credits will be deducted from your balance of 100."
    And I click on "Purchase" "button"
    Then I should see "New section"
    And I log out
    And I am on the "Test" course page logged in as admin
    And I navigate to course participants
    And I should see "Student" in the "Student User 1" "table_row"
    And I should see "Active" in the "Student User 1" "table_row"
    And I wait "20" seconds
    And I trigger cron
    And I am on "Test" course homepage
    And I navigate to course participants
    And I should not see "Student User 1" in the "table#participants" "css_element"
    And I log out

  @javascript
  Scenario: Credit enrolment expiry action: Keep user enrolled
    Given I log in as "admin"
    And I navigate to "Plugins > Enrolments > Course credit enrolment" in site administration
    And I set the field "Enrolment expiry action" to "Keep user enrolled"
    And I press "Save changes"
    And I am on the "Test" "enrolment methods" page
    And I select "Course credit enrolment" from the "Add method" singleselect
    And I wait "2" seconds
    And I set the following fields to these values:
      | Credit cost           | 20      |
      | enrolperiod[enabled]  | 1       |
      | enrolperiod[number]   | 20      |
      | enrolperiod[timeunit] | seconds |
    And I press "Add method"
    And I log out
    And I am on the "Test" course page logged in as student1
    And I should see "Course credit enrolment (Student)"
    And I should see "20 course credits will be deducted from your balance of 100."
    And I click on "Purchase" "button"
    Then I should see "New section"
    And I log out
    And I am on the "Test" course page logged in as admin
    And I navigate to course participants
    And I should see "Student" in the "Student User 1" "table_row"
    And I should see "Active" in the "Student User 1" "table_row"
    And I wait "20" seconds
    And I am on "Test" course homepage
    And I navigate to course participants
    And I should see "Student" in the "Student User 1" "table_row"
    And I should see "Not current" in the "Student User 1" "table_row"
    And I log out
