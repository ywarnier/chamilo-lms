Feature: HR ROI by organizational unit
  In order to review training investment per business unit
  As an administrator or HR user
  I need to be able to filter sessions by organizational unit

  Scenario: Admin sees ROI by unit page
    Given I am a platform administrator
    And I am on "/hr/roi/unit"
    And I wait for the page to be loaded
    Then I should see "ROI by organizational unit"
    And I should not see an error

  Scenario: HR user sees ROI by unit page
    Given I am an HR user
    And I am on "/hr/roi/unit"
    And I wait for the page to be loaded
    Then I should see "ROI by organizational unit"
    And I should not see an error

  Scenario: Student cannot access ROI by unit page
    Given I am a student
    And I am on "/hr/roi/unit"
    And I wait for the page to be loaded
    Then I should not see "ROI by organizational unit"

  Scenario: HR user sees the unit select and filter controls
    Given I am an HR user
    And I am on "/hr/roi/unit"
    And I wait for the page to be loaded
    Then I should see "ROI by organizational unit"
    And I should see an element with id "unitSelect"
    And I should see "Filter"
