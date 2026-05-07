Feature: HR Appraisal Templates
  In order to define the structure of evaluation interviews
  As an administrator
  I need to be able to create, edit and delete appraisal templates

  Scenario: Admin sees the template list page
    Given I am a platform administrator
    And I am on "/hr/appraisal-templates"
    And I wait for the page to be loaded
    Then I should see "Evaluation templates"
    And I should see "Add template"
    And I should not see an error

  Scenario: Admin creates an appraisal template
    Given I am a platform administrator
    And I am on "/hr/appraisal-templates/edit"
    And I wait for the page to be loaded
    Then I should see "Add evaluation template"
    When I fill in "title" with "Annual performance review"
    And I press "Save template"
    And I wait for the page to be loaded
    Then I should see "Annual performance review"
    And I should not see an error

  Scenario: Admin edits an appraisal template title
    Given I am a platform administrator
    And I am on "/hr/appraisal-templates"
    And I wait for the page to be loaded
    Then I should see "Annual performance review"
    When I click the edit button in the row containing "Annual performance review"
    And I wait for the page to be loaded
    Then I should see "Edit evaluation template"
    When I fill in "title" with "Annual review updated"
    And I press "Save template"
    And I wait for the page to be loaded
    Then I should see "Annual review updated"
    And I should not see an error

  Scenario: Admin deletes an appraisal template
    Given I am a platform administrator
    And I am on "/hr/appraisal-templates"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "Annual review updated"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Annual review updated"
    And I should not see an error

  Scenario: HR user cannot access appraisal template management
    Given I am an HR user
    And I am on "/hr/appraisal-templates"
    And I wait for the page to be loaded
    Then I should not see "Add template"
