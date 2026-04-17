Feature: HR Staff Statuses
  In order to manage staff employment status labels
  As an administrator or HR user
  I need to be able to create, edit and delete staff statuses

  Scenario: Admin creates a staff status
    Given I am a platform administrator
    And I am on "/hr/staff-statuses"
    And I wait for the page to be loaded
    Then I should see "Staff statuses"
    When I press "Add staff status"
    And I wait for the page to be loaded
    And I fill in "status_title" with "Full-time"
    And I fill in "status_description" with "Employees working full hours"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Full-time"
    And I should not see an error

  Scenario: Admin creates a second staff status
    Given I am a platform administrator
    And I am on "/hr/staff-statuses"
    And I wait for the page to be loaded
    When I press "Add staff status"
    And I wait for the page to be loaded
    And I fill in "status_title" with "Part-time"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Part-time"
    And I should not see an error

  Scenario: Admin edits a staff status
    Given I am a platform administrator
    And I am on "/hr/staff-statuses"
    And I wait for the page to be loaded
    Then I should see "Full-time"
    When I click the "table tbody tr:first-of-type i.mdi-pencil" element
    And I wait for the page to be loaded
    And I fill in "status_title" with "Full-time Permanent"
    And I fill in "status_description" with "Permanent full-time employees"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Full-time Permanent"
    And I should not see an error

  Scenario: Admin deletes staff statuses
    Given I am a platform administrator
    And I am on "/hr/staff-statuses"
    And I wait for the page to be loaded
    When I click the "table tbody tr:first-of-type i.mdi-delete" element
    And I wait for the page to be loaded
    And I click the ".p-confirmdialog-accept-button button" element
    And I wait for the page to be loaded
    And I should not see an error
    When I click the "table tbody tr:first-of-type i.mdi-delete" element
    And I wait for the page to be loaded
    And I click the ".p-confirmdialog-accept-button button" element
    And I wait for the page to be loaded
    Then I should not see "Full-time Permanent"
    And I should not see "Part-time"
    And I should not see an error

  Scenario: HR user creates a staff status
    Given I am an HR user
    And I am on "/hr/staff-statuses"
    And I wait for the page to be loaded
    Then I should see "Staff statuses"
    When I press "Add staff status"
    And I wait for the page to be loaded
    And I fill in "status_title" with "Contractor"
    And I fill in "status_description" with "External contractors"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Contractor"
    And I should not see an error

  Scenario: HR user edits a staff status
    Given I am an HR user
    And I am on "/hr/staff-statuses"
    And I wait for the page to be loaded
    Then I should see "Contractor"
    When I click the "table tbody tr:first-of-type i.mdi-pencil" element
    And I wait for the page to be loaded
    And I fill in "status_title" with "External Contractor"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "External Contractor"
    And I should not see an error

  Scenario: HR user deletes a staff status
    Given I am an HR user
    And I am on "/hr/staff-statuses"
    And I wait for the page to be loaded
    Then I should see "External Contractor"
    When I click the "table tbody tr:first-of-type i.mdi-delete" element
    And I wait for the page to be loaded
    And I click the ".p-confirmdialog-accept-button button" element
    And I wait for the page to be loaded
    Then I should not see "External Contractor"
    And I should not see an error
