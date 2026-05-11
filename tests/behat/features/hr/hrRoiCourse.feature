Feature: HR ROI by course
  In order to track training investment per session
  As an administrator or HR user
  I need to be able to view sessions and set their costs

  Scenario: Admin sees ROI by course page
    Given I am a platform administrator
    And I am on "/hr/roi/courses"
    And I wait for the page to be loaded
    Then I should see "ROI by course"
    And I should not see an error

  Scenario: HR user sees ROI by course page
    Given I am an HR user
    And I am on "/hr/roi/courses"
    And I wait for the page to be loaded
    Then I should see "ROI by course"
    And I should not see an error

  Scenario: Student cannot access ROI by course page
    Given I am a student
    And I am on "/hr/roi/courses"
    And I wait for the page to be loaded
    Then I should not see "ROI by course"

  Scenario: Admin sees the date-range filter input
    Given I am a platform administrator
    And I am on "/hr/roi/courses"
    And I wait for the page to be loaded
    Then I should see "ROI by course"
    And I should see an element with id "dateRange"

  Scenario: HR user sees the date-range filter input
    Given I am an HR user
    And I am on "/hr/roi/courses"
    And I wait for the page to be loaded
    Then I should see "ROI by course"
    And I should see an element with id "dateRange"
