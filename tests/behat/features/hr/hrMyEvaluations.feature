Feature: HR My Evaluations (personal space)
  In order to view and manage my own performance appraisals
  As any authenticated user
  I need to be able to access my personal evaluation dashboard

  Scenario: Admin can see the My Evaluations page with two tabs
    Given I am a platform administrator
    And I am on "/hr/my-evaluations"
    And I wait for the page to be loaded
    Then I should see "My evaluations"
    And I should see "Evaluations of me"
    And I should see "Evaluations I must conduct"
    And I should not see an error

  Scenario: Admin can switch to the evaluator tab
    Given I am a platform administrator
    And I am on "/hr/my-evaluations"
    And I wait for the page to be loaded
    When I press "Evaluations I must conduct"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: HR user can access My Evaluations
    Given I am an HR user
    And I am on "/hr/my-evaluations"
    And I wait for the page to be loaded
    Then I should see "My evaluations"
    And I should see "Evaluations of me"
    And I should not see an error

  Scenario: Student can access My Evaluations page
    Given I am a student
    And I am on "/hr/my-evaluations"
    And I wait for the page to be loaded
    Then I should see "My evaluations"
    And I should not see an error
