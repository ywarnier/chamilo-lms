Feature: HR Function-Unit Associations
  In order to link professional functions to business units
  As an administrator
  I need to be able to create, edit and delete function-unit associations

  Background:
    Given I am a platform administrator
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    And I press "Add function"
    And I wait for the page to be loaded
    And I fill in "function_title" with "FIU Test Function"
    And I press "Save"
    And I wait for the page to be loaded

  Scenario: Admin creates a function-unit association
    Given I am a platform administrator
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    Then I should see "Function-unit associations"
    When I press "Add association"
    And I wait for the page to be loaded
    And I fill in "fiu_title" with "Senior Engineer – R&D"
    And I select "FIU Test Function" from "fiu_professional_function"
    And I select "Engineering" from "fiu_business_unit"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Senior Engineer – R&D"
    And I should see "FIU Test Function"
    And I should not see an error

  Scenario: Admin edits a function-unit association
    Given I am a platform administrator
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    Then I should see "Senior Engineer – R&D"
    When I click the edit button in the row containing "Senior Engineer – R&D"
    And I wait for the page to be loaded
    And I fill in "fiu_title" with "Senior Engineer – R&D Updated"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Senior Engineer – R&D Updated"
    And I should not see an error

  Scenario: Admin deletes a function-unit association
    Given I am a platform administrator
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    Then I should see "Senior Engineer – R&D Updated"
    When I click the delete button in the row containing "Senior Engineer – R&D Updated"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Senior Engineer – R&D Updated"
    And I should not see an error

  Scenario: Admin cleans up test professional function
    Given I am a platform administrator
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "FIU Test Function"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "FIU Test Function"

  Scenario: Non-admin cannot access the function-unit association admin page
    Given I am an HR user
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    Then I should not see "Add association"
