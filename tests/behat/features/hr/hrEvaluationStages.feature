Feature: HR Evaluation Stages
  In order to categorise evaluations by stage
  As an administrator or HR user
  I need to be able to create, edit and delete evaluation stages

  Scenario: Admin creates an evaluation stage
    Given I am a platform administrator
    And I am on "/hr/evaluation-stages"
    And I wait for the page to be loaded
    Then I should see "Evaluation stages"
    When I press "Add stage"
    And I wait for the page to be loaded
    And I fill in "title" with "Entry evaluation"
    And I fill in "description" with "Evaluation performed when joining the organisation"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Entry evaluation"
    And I should not see an error

  Scenario: Admin creates a second stage
    Given I am a platform administrator
    And I am on "/hr/evaluation-stages"
    And I wait for the page to be loaded
    When I press "Add stage"
    And I wait for the page to be loaded
    And I fill in "title" with "Mid-term review"
    And I fill in "description" with "Mid-year performance check"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Mid-term review"
    And I should not see an error

  Scenario: Admin edits an evaluation stage
    Given I am a platform administrator
    And I am on "/hr/evaluation-stages"
    And I wait for the page to be loaded
    When I click the edit button in the row containing "Entry evaluation"
    And I wait for the page to be loaded
    And I fill in "title" with "Onboarding evaluation"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Onboarding evaluation"
    And I should not see an error

  Scenario: Admin deletes evaluation stages
    Given I am a platform administrator
    And I am on "/hr/evaluation-stages"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "Onboarding evaluation"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Onboarding evaluation"
    And I should not see an error
    When I click the delete button in the row containing "Mid-term review"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Mid-term review"
    And I should not see an error

  Scenario: HR user can access evaluation stages
    Given I am an HR user
    And I am on "/hr/evaluation-stages"
    And I wait for the page to be loaded
    Then I should see "Evaluation stages"
    And I should not see an error
