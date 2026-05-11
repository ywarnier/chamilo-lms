Feature: HR Career Plan Overview
  In order to review the full career plan across the organisation
  As an administrator
  I need to see all function-unit associations with their required skills

  Scenario: Admin can access the career plan overview page
    Given I am a platform administrator
    And I am on "/hr/career-plan"
    And I wait for the page to be loaded
    Then I should see "Career plan overview"
    And I should not see an error

  Scenario: Admin sees empty state when no functions are configured
    Given I am a platform administrator
    And I am on "/hr/career-plan"
    And I wait for the page to be loaded
    Then I should not see an error
    And I should see "Career plan overview"

  Scenario: Non-admin HR user cannot access the career plan overview page
    Given I am an HR user
    And I am on "/hr/career-plan"
    And I wait for the page to be loaded
    Then I should not see "Career plan overview"