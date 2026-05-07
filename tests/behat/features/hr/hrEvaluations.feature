Feature: HR Scheduled Evaluations
  In order to manage staff performance appraisals
  As an administrator or HR user
  I need to be able to schedule, view and manage evaluations

  Scenario: Admin sees the evaluations list
    Given I am a platform administrator
    And I am on "/hr/evaluations"
    And I wait for the page to be loaded
    Then I should see "Scheduled evaluations"
    And I should see "Schedule evaluation"
    And I should not see an error

  Scenario: HR user sees the evaluations list
    Given I am an HR user
    And I am on "/hr/evaluations"
    And I wait for the page to be loaded
    Then I should see "Scheduled evaluations"
    And I should not see an error

  Scenario: Admin can filter evaluations by status
    Given I am a platform administrator
    And I am on "/hr/evaluations"
    And I wait for the page to be loaded
    When I select "Scheduled" from "status"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Admin can search evaluations by evaluatee name
    Given I am a platform administrator
    And I am on "/hr/evaluations"
    And I wait for the page to be loaded
    When I fill in "search" with "admin"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Student cannot access scheduled evaluations management
    Given I am a student
    And I am on "/hr/evaluations"
    And I wait for the page to be loaded
    Then I should not see "Schedule evaluation"
