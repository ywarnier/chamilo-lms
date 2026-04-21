Feature: HR Professional Functions
  In order to manage job function definitions in the organisation
  As an administrator
  I need to be able to create, edit and delete professional functions

  Scenario: Admin creates a professional function
    Given I am a platform administrator
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    Then I should see "Professional functions"
    When I press "Add function"
    And I wait for the page to be loaded
    And I fill in "function_title" with "Software Engineer"
    And I fill in "function_description" with "Develops and maintains software systems"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Software Engineer"
    And I should not see an error

  Scenario: Admin creates a child professional function
    Given I am a platform administrator
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    When I press "Add function"
    And I wait for the page to be loaded
    And I fill in "function_title" with "Frontend Developer"
    And I select "Software Engineer" from "function_parent"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Frontend Developer"
    And I should see "Software Engineer"
    And I should not see an error

  Scenario: Admin edits a professional function
    Given I am a platform administrator
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    Then I should see "Software Engineer"
    When I click the edit button in the row containing "Software Engineer"
    And I wait for the page to be loaded
    And I fill in "function_title" with "Software Engineer Updated"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Software Engineer Updated"
    And I should not see an error

  Scenario: Admin deletes a child professional function
    Given I am a platform administrator
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    Then I should see "Frontend Developer"
    When I click the delete button in the row containing "Frontend Developer"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Frontend Developer"
    And I should not see an error

  Scenario: Admin deletes a professional function
    Given I am a platform administrator
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    Then I should see "Software Engineer Updated"
    When I click the delete button in the row containing "Software Engineer Updated"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Software Engineer Updated"
    And I should not see an error

  Scenario: Non-admin cannot access the professional functions admin page
    Given I am an HR user
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    Then I should not see "Add function"
