Feature: HR Periodicities
  In order to define evaluation rhythms
  As an administrator or HR user
  I need to be able to create, edit and delete periodicities

  Scenario: Admin creates a periodicity
    Given I am a platform administrator
    And I am on "/hr/periodicities"
    And I wait for the page to be loaded
    Then I should see "Periodicities"
    When I press "Add periodicity"
    And I wait for the page to be loaded
    And I fill in "title" with "Semi-annual"
    And I fill in "days" with "180"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Semi-annual"
    And I should see "180"
    And I should not see an error

  Scenario: Admin creates a second periodicity
    Given I am a platform administrator
    And I am on "/hr/periodicities"
    And I wait for the page to be loaded
    When I press "Add periodicity"
    And I wait for the page to be loaded
    And I fill in "title" with "Annual"
    And I fill in "days" with "365"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Annual"
    And I should see "365"
    And I should not see an error

  Scenario: Admin edits a periodicity
    Given I am a platform administrator
    And I am on "/hr/periodicities"
    And I wait for the page to be loaded
    When I click the edit button in the row containing "Semi-annual"
    And I wait for the page to be loaded
    And I fill in "title" with "Bi-annual"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Bi-annual"
    And I should not see an error

  Scenario: Admin deletes periodicities
    Given I am a platform administrator
    And I am on "/hr/periodicities"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "Bi-annual"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Bi-annual"
    And I should not see an error
    When I click the delete button in the row containing "Annual"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Annual"
    And I should not see an error

  Scenario: HR user can access periodicities
    Given I am an HR user
    And I am on "/hr/periodicities"
    And I wait for the page to be loaded
    Then I should see "Periodicities"
    And I should not see an error

  Scenario: Student cannot access periodicity management
    Given I am a student
    And I am on "/hr/periodicities"
    And I wait for the page to be loaded
    Then I should not see "Add periodicity"
