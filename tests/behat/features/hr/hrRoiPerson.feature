Feature: HR ROI by person
  In order to review training investment per employee
  As an administrator or HR user
  I need to be able to filter sessions by user

  Scenario: Admin sees ROI by person page
    Given I am a platform administrator
    And I am on "/hr/roi/person"
    And I wait for the page to be loaded
    Then I should see "ROI by person"
    And I should not see an error

  Scenario: HR user sees ROI by person page
    Given I am an HR user
    And I am on "/hr/roi/person"
    And I wait for the page to be loaded
    Then I should see "ROI by person"
    And I should not see an error

  Scenario: Student cannot access ROI by person page
    Given I am a student
    And I am on "/hr/roi/person"
    And I wait for the page to be loaded
    Then I should not see "ROI by person"

  Scenario: HR user sees the user search and date-range filter controls
    Given I am an HR user
    And I am on "/hr/roi/person"
    And I wait for the page to be loaded
    Then I should see "ROI by person"
    And I should see an element with id "userSearch"
    And I should see "Filter by date range"
