Feature: HR Geographic Zones
  In order to manage geographic zones
  As an administrator
  I need to be able to create, edit and delete geographic zones,
  and HR users must not have access to this admin-only page

  Scenario: Admin creates a geographic zone
    Given I am a platform administrator
    And I am on "/hr/geographic-zones"
    And I wait for the page to be loaded
    Then I should see "Geographic zones"
    When I press "Add geographic zone"
    And I wait for the page to be loaded
    And I fill in "zone_title" with "North Region"
    And I fill in "zone_description" with "All branches in the northern area"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "North Region"
    And I should not see an error

  Scenario: Admin edits a geographic zone
    Given I am a platform administrator
    And I am on "/hr/geographic-zones"
    And I wait for the page to be loaded
    Then I should see "North Region"
    When I click the edit button in the row containing "North Region"
    And I wait for the page to be loaded
    And I fill in "zone_title" with "North Region Updated"
    And I fill in "zone_description" with "Updated northern area description"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "North Region Updated"
    And I should not see an error

  Scenario: Admin deletes a geographic zone
    Given I am a platform administrator
    And I am on "/hr/geographic-zones"
    And I wait for the page to be loaded
    Then I should see "North Region Updated"
    When I click the delete button in the row containing "North Region Updated"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "North Region Updated"
    And I should not see an error

  Scenario: HR user cannot access the geographic zones admin page
    Given I am an HR user
    And I am on "/hr/geographic-zones"
    And I wait for the page to be loaded
    Then I should not see "Add geographic zone"
