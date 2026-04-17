Feature: HR Contract Types
  In order to manage contract type labels
  As an administrator or HR user
  I need to be able to create, edit and delete contract types

  Scenario: Admin creates a contract type
    Given I am a platform administrator
    And I am on "/hr/contract-types"
    And I wait for the page to be loaded
    Then I should see "Contract types"
    When I press "Add contract type"
    And I wait for the page to be loaded
    And I fill in "contract_title" with "Permanent"
    And I fill in "contract_description" with "Open-ended employment contract"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Permanent"
    And I should not see an error

  Scenario: Admin creates a second contract type
    Given I am a platform administrator
    And I am on "/hr/contract-types"
    And I wait for the page to be loaded
    When I press "Add contract type"
    And I wait for the page to be loaded
    And I fill in "contract_title" with "Fixed-term"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Fixed-term"
    And I should not see an error

  Scenario: Admin edits a contract type
    Given I am a platform administrator
    And I am on "/hr/contract-types"
    And I wait for the page to be loaded
    Then I should see "Permanent"
    When I click the "table tbody tr:first-of-type i.mdi-pencil" element
    And I wait for the page to be loaded
    And I fill in "contract_title" with "Permanent CDI"
    And I fill in "contract_description" with "Contrat à durée indéterminée"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Permanent CDI"
    And I should not see an error

  Scenario: Admin deletes contract types
    Given I am a platform administrator
    And I am on "/hr/contract-types"
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
    Then I should not see "Permanent CDI"
    And I should not see "Fixed-term"
    And I should not see an error

  Scenario: HR user creates a contract type
    Given I am an HR user
    And I am on "/hr/contract-types"
    And I wait for the page to be loaded
    Then I should see "Contract types"
    When I press "Add contract type"
    And I wait for the page to be loaded
    And I fill in "contract_title" with "Freelance"
    And I fill in "contract_description" with "Independent freelance agreement"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Freelance"
    And I should not see an error

  Scenario: HR user edits a contract type
    Given I am an HR user
    And I am on "/hr/contract-types"
    And I wait for the page to be loaded
    Then I should see "Freelance"
    When I click the "table tbody tr:first-of-type i.mdi-pencil" element
    And I wait for the page to be loaded
    And I fill in "contract_title" with "Freelance Agreement"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Freelance Agreement"
    And I should not see an error

  Scenario: HR user deletes a contract type
    Given I am an HR user
    And I am on "/hr/contract-types"
    And I wait for the page to be loaded
    Then I should see "Freelance Agreement"
    When I click the "table tbody tr:first-of-type i.mdi-delete" element
    And I wait for the page to be loaded
    And I click the ".p-confirmdialog-accept-button button" element
    And I wait for the page to be loaded
    Then I should not see "Freelance Agreement"
    And I should not see an error
