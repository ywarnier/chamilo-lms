Feature: My Career Plan
  In order to visualise my career progression within the organisation
  As an authenticated user
  I need to see my current position and potential career targets with skill gaps

  Scenario: Student without a position assignment sees the empty state
    Given I am a student
    And I am on "/hr/my-career-plan"
    And I wait for the page to be loaded
    Then I should see "My career plan"
    And I should see "You must be assigned to a position to see your career plan."
    And I should not see an error

  Scenario: Admin can also access the My Career Plan page
    Given I am a platform administrator
    And I am on "/hr/my-career-plan"
    And I wait for the page to be loaded
    Then I should see "My career plan"
    And I should not see an error

  Scenario: HR user can access the My Career Plan page
    Given I am an HR user
    And I am on "/hr/my-career-plan"
    And I wait for the page to be loaded
    Then I should see "My career plan"
    And I should not see an error