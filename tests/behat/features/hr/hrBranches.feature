Feature: HR Branches
  In order to manage physical and virtual office locations
  As an administrator or HR user
  I need to be able to create, edit and delete branches

  Scenario: Admin creates a branch without a geographic zone
    Given I am a platform administrator
    And I am on "/hr/branches"
    And I wait for the page to be loaded
    Then I should see "Branches"
    When I press "Add branch"
    And I wait for the page to be loaded
    And I fill in "branch_title" with "Main Office"
    And I fill in "branch_address" with "123 Main Street, Springfield"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Main Office"
    And I should not see an error

  Scenario: Admin creates a geographic zone then a branch linked to it
    Given I am a platform administrator
    And I am on "/hr/geographic-zones"
    And I wait for the page to be loaded
    When I press "Add geographic zone"
    And I wait for the page to be loaded
    And I fill in "zone_title" with "South Region"
    And I press "Save"
    And I wait for the page to be loaded
    And I am on "/hr/branches"
    And I wait for the page to be loaded
    When I press "Add branch"
    And I wait for the page to be loaded
    And I fill in "branch_title" with "South Office"
    And I select "South Region" from "branch_zone"
    And I fill in "branch_latitude" with "48.8566"
    And I fill in "branch_longitude" with "2.3522"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "South Office"
    And I should see "South Region"
    And I should not see an error

  Scenario: Admin edits a branch
    Given I am a platform administrator
    And I am on "/hr/branches"
    And I wait for the page to be loaded
    Then I should see "Main Office"
    When I click the edit button in the row containing "Main Office"
    And I wait for the page to be loaded
    And I fill in "branch_title" with "Main Office Updated"
    And I fill in "branch_address" with "456 Updated Avenue, Springfield"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Main Office Updated"
    And I should not see an error

  Scenario: Admin deletes branches and cleanup the geographic zone
    Given I am a platform administrator
    And I am on "/hr/branches"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "Main Office Updated"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    And I should not see an error
    When I click the delete button in the row containing "South Office"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Main Office Updated"
    And I should not see "South Office"
    And I should not see an error
    And I am on "/hr/geographic-zones"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "South Region"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "South Region"
    And I should not see an error

  Scenario: HR user creates a branch
    Given I am an HR user
    And I am on "/hr/branches"
    And I wait for the page to be loaded
    Then I should see "Branches"
    When I press "Add branch"
    And I wait for the page to be loaded
    And I fill in "branch_title" with "HR Branch"
    And I fill in "branch_address" with "789 HR Road, Capital City"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "HR Branch"
    And I should not see an error

  Scenario: HR user edits a branch
    Given I am an HR user
    And I am on "/hr/branches"
    And I wait for the page to be loaded
    Then I should see "HR Branch"
    When I click the edit button in the row containing "HR Branch"
    And I wait for the page to be loaded
    And I fill in "branch_title" with "HR Branch Updated"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "HR Branch Updated"
    And I should not see an error

  Scenario: HR user deletes a branch
    Given I am an HR user
    And I am on "/hr/branches"
    And I wait for the page to be loaded
    Then I should see "HR Branch Updated"
    When I click the delete button in the row containing "HR Branch Updated"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "HR Branch Updated"
    And I should not see an error
