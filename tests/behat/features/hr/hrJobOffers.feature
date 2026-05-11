Feature: HR Job Offers
  In order to manage job postings for recruitment
  As an administrator or HR user
  I need to be able to create, edit, add selection tests, and delete job offers

  Scenario: Setup prerequisites for job offer tests
    Given I am a platform administrator
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    When I press "Add business unit"
    And I wait for the page to be loaded
    And I fill in "unit_title" with "JO Test Unit"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "JO Test Unit"
    And I should not see an error
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    When I press "Add function"
    And I wait for the page to be loaded
    And I fill in "function_title" with "JO Test Function"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "JO Test Function"
    And I should not see an error
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    When I press "Add association"
    And I wait for the page to be loaded
    And I fill in "fiu_title" with "JO Test Position"
    And I select "JO Test Function" from "fiu_professional_function"
    And I select "JO Test Unit" from "fiu_business_unit"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "JO Test Position"
    And I should not see an error

  Scenario: Admin creates a job offer
    Given I am a platform administrator
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    Then I should see "Job offers"
    When I press "Add job offer"
    And I wait for the page to be loaded
    And I fill in "title" with "Admin Test Job Offer"
    And I select "JO Test Position — JO Test Unit" from "function_in_unit"
    And I fill in "description" with "This is an admin test job offer description"
    And I fill in "salary" with "3000 USD"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Admin Test Job Offer"
    And I should not see an error

  Scenario: Admin edits a job offer
    Given I am a platform administrator
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    Then I should see "Admin Test Job Offer"
    When I click the edit button in the row containing "Admin Test Job Offer"
    And I wait for the page to be loaded
    And I fill in "title" with "Admin Test Job Offer Updated"
    And I fill in "salary" with "3500 USD"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Admin Test Job Offer Updated"
    And I should not see an error

  Scenario: HR user creates a job offer
    Given I am an HR user
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    Then I should see "Job offers"
    When I press "Add job offer"
    And I wait for the page to be loaded
    And I fill in "title" with "HR Test Job Offer"
    And I select "JO Test Position — JO Test Unit" from "function_in_unit"
    And I fill in "description" with "This is an HR test job offer description"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "HR Test Job Offer"
    And I should not see an error

  Scenario: HR user edits a job offer
    Given I am an HR user
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    Then I should see "HR Test Job Offer"
    When I click the edit button in the row containing "HR Test Job Offer"
    And I wait for the page to be loaded
    And I fill in "title" with "HR Test Job Offer Updated"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "HR Test Job Offer Updated"
    And I should not see an error

  Scenario: HR user deletes their job offer
    Given I am an HR user
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "HR Test Job Offer Updated"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "HR Test Job Offer Updated"
    And I should not see an error

  Scenario: Admin deletes job offer and cleans up prerequisites
    Given I am a platform administrator
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "Admin Test Job Offer Updated"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Admin Test Job Offer Updated"
    And I should not see an error
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "JO Test Position"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "JO Test Position"
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "JO Test Function"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "JO Test Function"
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "JO Test Unit"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "JO Test Unit"
    And I should not see an error
