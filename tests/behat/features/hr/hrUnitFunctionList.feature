Feature: HR Unit Function List
  In order to view headcount distribution across the organisation
  As an administrator
  I need to see the hierarchical unit-function list with a headcount chart

  Scenario: Admin views the unit function list page
    Given I am a platform administrator
    And I am on "/hr/unit-function-list"
    And I wait for the page to be loaded
    Then I should see "Unit function list"
    And I should see "Select a unit to see headcount distribution"
    And I should not see an error

  Scenario: Admin selects a unit and sees headcount information
    Given I am a platform administrator
    And I am on "/hr/unit-function-list"
    And I wait for the page to be loaded
    When I click on the first tree node button
    Then I should see "Headcount"
    And I should not see "Select a unit to see headcount distribution"
    And I should not see an error

  Scenario: Non-admin HR user cannot access the unit function list page
    Given I am an HR user
    And I am on "/hr/unit-function-list"
    And I wait for the page to be loaded
    Then I should not see "Unit function list"

  Scenario: HR manager cannot access the unit function list page
    Given I am an HR manager
    And I am on "/hr/unit-function-list"
    And I wait for the page to be loaded
    Then I should not see "Unit function list"

  Scenario: Student cannot access the unit function list page
    Given I am a student
    And I am on "/hr/unit-function-list"
    And I wait for the page to be loaded
    Then I should not see "Unit function list"
