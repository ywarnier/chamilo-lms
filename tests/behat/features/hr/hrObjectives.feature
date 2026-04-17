Feature: HR Performance Objectives
  In order to manage performance objectives and their categories
  As an administrator
  I need to be able to create, edit and delete objective categories and performance objectives

  Scenario: Create an objective category
    Given I am a platform administrator
    And I am on "/hr/objectives"
    And I wait for the page to be loaded
    Then I should see "Objective categories"
    When I press "Add category"
    And I wait for the page to be loaded
    And I fill in "category_title" with "Leadership"
    And I fill in "category_description" with "Leadership-related objectives"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Leadership"
    And I should not see an error

  Scenario: Edit an objective category
    Given I am a platform administrator
    And I am on "/hr/objectives"
    And I wait for the page to be loaded
    Then I should see "Leadership"
    When I click the edit button in the row containing "Leadership"
    And I wait for the page to be loaded
    And I fill in "category_title" with "Leadership Updated"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Leadership Updated"
    And I should not see an error

  Scenario: Create a performance objective without a category
    Given I am a platform administrator
    And I am on "/hr/objectives"
    And I wait for the page to be loaded
    Then I should see "Performance objectives"
    When I press "Add objective"
    And I wait for the page to be loaded
    And I fill in "objective_title" with "Improve communication"
    And I fill in "objective_description" with "Develop clearer written and verbal communication"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Improve communication"
    And I should not see an error

  Scenario: Create a performance objective with a category
    Given I am a platform administrator
    And I am on "/hr/objectives"
    And I wait for the page to be loaded
    When I press "Add objective"
    And I wait for the page to be loaded
    And I fill in "objective_title" with "Lead a project"
    And I select "Leadership Updated" from "objective_category"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Lead a project"
    And I should see "Leadership Updated"
    And I should not see an error

  Scenario: Edit a performance objective
    Given I am a platform administrator
    And I am on "/hr/objectives"
    And I wait for the page to be loaded
    Then I should see "Improve communication"
    When I click the edit button in the row containing "Improve communication"
    And I wait for the page to be loaded
    And I fill in "objective_title" with "Improve communication skills"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Improve communication skills"
    And I should not see an error

  Scenario: Delete a performance objective
    Given I am a platform administrator
    And I am on "/hr/objectives"
    And I wait for the page to be loaded
    Then I should see "Improve communication skills"
    When I click the delete button in the row containing "Improve communication skills"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Improve communication skills"
    And I should not see an error

  Scenario: Delete a second performance objective
    Given I am a platform administrator
    And I am on "/hr/objectives"
    And I wait for the page to be loaded
    Then I should see "Lead a project"
    When I click the delete button in the row containing "Lead a project"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Lead a project"
    And I should not see an error

  Scenario: Delete an objective category
    Given I am a platform administrator
    And I am on "/hr/objectives"
    And I wait for the page to be loaded
    Then I should see "Leadership Updated"
    When I click the delete button in the row containing "Leadership Updated"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Leadership Updated"
    And I should not see an error

  Scenario: HR user cannot access the objectives admin page
    Given I am an HR user
    And I am on "/hr/objectives"
    And I wait for the page to be loaded
    Then I should not see "Add category"
    And I should not see "Add objective"
