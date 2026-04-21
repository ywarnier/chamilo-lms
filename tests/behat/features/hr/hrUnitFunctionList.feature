Feature: HR Unit Function List
  In order to view headcount distribution across the organisation
  As an administrator
  I need to see the hierarchical unit-function list with a headcount chart

  Scenario: Admin views the unit function list page
    Given I am a platform administrator
    And I am on "/hr/unit-function-list"
    And I wait for the page to be loaded
    Then I should see "Unit function list"
    And I should not see an error

  Scenario: Admin sees the unit tree and can select a unit
    Given I am a platform administrator
    And I am on "/hr/unit-function-list"
    And I wait for the page to be loaded
    Then I should see "Select a unit to see headcount distribution"
    And I should not see an error

  Scenario: Non-admin cannot access the unit function list page
    Given I am an HR user
    And I am on "/hr/unit-function-list"
    And I wait for the page to be loaded
    Then I should not see "Unit function list"
