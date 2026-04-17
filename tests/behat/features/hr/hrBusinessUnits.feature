Feature: HR Business Units
  In order to manage the hierarchical department and team structure
  As an administrator or HR user
  I need to be able to create, edit and delete business units

  Scenario: Admin creates a top-level business unit
    Given I am a platform administrator
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    Then I should see "Business units"
    When I press "Add business unit"
    And I wait for the page to be loaded
    And I fill in "unit_title" with "Engineering"
    And I fill in "unit_description" with "Software and hardware engineering teams"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Engineering"
    And I should not see an error

  Scenario: Admin creates a child business unit under a parent
    Given I am a platform administrator
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    When I press "Add business unit"
    And I wait for the page to be loaded
    And I fill in "unit_title" with "Backend Team"
    And I fill in "unit_description" with "Server-side developers"
    And I select "Engineering" from "unit_parent"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Backend Team"
    And I should see "Engineering"
    And I should not see an error

  Scenario: Admin creates a branch then links a business unit to it
    Given I am a platform administrator
    And I am on "/hr/branches"
    And I wait for the page to be loaded
    When I press "Add branch"
    And I wait for the page to be loaded
    And I fill in "branch_title" with "HQ Branch"
    And I press "Save"
    And I wait for the page to be loaded
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    When I press "Add business unit"
    And I wait for the page to be loaded
    And I fill in "unit_title" with "Executive Board"
    And I select "HQ Branch" from "unit_branch"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Executive Board"
    And I should see "HQ Branch"
    And I should not see an error

  Scenario: Admin edits a business unit
    Given I am a platform administrator
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    Then I should see "Engineering"
    When I click the edit button in the row containing "Engineering"
    And I wait for the page to be loaded
    And I fill in "unit_title" with "Engineering Division"
    And I fill in "unit_description" with "All engineering departments"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Engineering Division"
    And I should not see an error

  Scenario: Admin deletes business units and cleanup branch
    Given I am a platform administrator
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "Engineering Division"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    And I should not see an error
    When I click the delete button in the row containing "Backend Team"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    And I should not see an error
    When I click the delete button in the row containing "Executive Board"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Engineering Division"
    And I should not see "Backend Team"
    And I should not see "Executive Board"
    And I should not see an error
    And I am on "/hr/branches"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "HQ Branch"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "HQ Branch"
    And I should not see an error

  Scenario: HR user creates a business unit
    Given I am an HR user
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    Then I should see "Business units"
    When I press "Add business unit"
    And I wait for the page to be loaded
    And I fill in "unit_title" with "HR Department"
    And I fill in "unit_description" with "Human Resources department"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "HR Department"
    And I should not see an error

  Scenario: HR user creates a child business unit
    Given I am an HR user
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    When I press "Add business unit"
    And I wait for the page to be loaded
    And I fill in "unit_title" with "Recruitment Team"
    And I select "HR Department" from "unit_parent"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Recruitment Team"
    And I should see "HR Department"
    And I should not see an error

  Scenario: HR user edits a business unit
    Given I am an HR user
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    Then I should see "HR Department"
    When I click the edit button in the row containing "HR Department"
    And I wait for the page to be loaded
    And I fill in "unit_title" with "Human Resources"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Human Resources"
    And I should not see an error

  Scenario: HR user deletes business units
    Given I am an HR user
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "Human Resources"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    And I should not see an error
    When I click the delete button in the row containing "Recruitment Team"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Human Resources"
    And I should not see "Recruitment Team"
    And I should not see an error
