Feature: HR Positions
  In order to assign staff to organisational roles
  As an administrator
  I need to be able to create, edit and delete positions

  Background:
    Given I am a platform administrator
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    And I press "Add function"
    And I wait for the page to be loaded
    And I fill in "function_title" with "Pos Test Function"
    And I press "Save"
    And I wait for the page to be loaded
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    And I press "Add association"
    And I wait for the page to be loaded
    And I fill in "fiu_title" with "Pos Test Role"
    And I select "Pos Test Function" from "fiu_professional_function"
    And I select "Engineering" from "fiu_business_unit"
    And I press "Save"
    And I wait for the page to be loaded

  Scenario: Admin creates a position
    Given I am a platform administrator
    And I am on "/hr/positions"
    And I wait for the page to be loaded
    Then I should see "Positions"
    When I press "Add position"
    And I wait for the page to be loaded
    And I select "admin admin (admin)" from "position_user"
    And I select "Pos Test Role" from "position_function_in_unit"
    And I fill in "position_start_date" with "2025-01-01"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "admin"
    And I should see "Pos Test Role"
    And I should not see an error

  Scenario: Admin edits a position
    Given I am a platform administrator
    And I am on "/hr/positions"
    And I wait for the page to be loaded
    When I click the edit button in the row containing "Pos Test Role"
    And I wait for the page to be loaded
    And I fill in "position_end_date" with "2026-12-31"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "2026-12-31"
    And I should not see an error

  Scenario: Admin deletes a position
    Given I am a platform administrator
    And I am on "/hr/positions"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "Pos Test Role"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Admin cleans up test data
    Given I am a platform administrator
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "Pos Test Role"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "Pos Test Function"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Pos Test Function"

  Scenario: Non-admin cannot access the positions admin page
    Given I am an HR user
    And I am on "/hr/positions"
    And I wait for the page to be loaded
    Then I should not see "Add position"
