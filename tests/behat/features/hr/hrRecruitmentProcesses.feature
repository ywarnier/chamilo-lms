Feature: HR Recruitment Processes
  In order to track the hiring progress for each candidate
  As an HR user or administrator
  I need to be able to create recruitment processes and add tracking entries

  Scenario: Setup prerequisites for recruitment process tests
    Given I am a platform administrator
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    When I press "Add business unit"
    And I wait for the page to be loaded
    And I fill in "unit_title" with "RP Test Unit"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "RP Test Unit"
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    When I press "Add function"
    And I wait for the page to be loaded
    And I fill in "function_title" with "RP Test Function"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "RP Test Function"
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    When I press "Add association"
    And I wait for the page to be loaded
    And I fill in "fiu_title" with "RP Test Position"
    And I select "RP Test Function" from "fiu_professional_function"
    And I select "RP Test Unit" from "fiu_business_unit"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "RP Test Position"
    And I should not see an error
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    When I press "Add job offer"
    And I wait for the page to be loaded
    And I fill in "title" with "RP Test Offer"
    And I select "RP Test Position — RP Test Unit" from "function_in_unit"
    And I fill in "description" with "A job offer for recruitment process testing"
    And I check "is_public"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "RP Test Offer"
    And I should not see an error
    And I am on "/hr/recruitment-stages"
    And I wait for the page to be loaded
    When I press "Add stage"
    And I wait for the page to be loaded
    And I fill in "title" with "RP Test Stage"
    And I fill in "description" with "Stage for recruitment process testing"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "RP Test Stage"
    And I should not see an error

  Scenario: Student applies to the RP test offer
    Given I am a student
    And I am on "/job-offers"
    And I wait for the page to be loaded
    Then I should see "RP Test Offer"
    When I press "See details"
    And I wait for the page to be loaded
    When I press "Apply now"
    And I wait for the page to be loaded
    And I fill in "introduction" with "I am applying for the RP test offer."
    And I press "Submit application"
    And I wait for the page to be loaded
    Then I should see "Already applied"
    And I should not see an error

  Scenario: HR user creates a recruitment process
    Given I am an HR user
    And I am on "/hr/recruitment-processes"
    And I wait for the page to be loaded
    Then I should see "Recruitment processes"
    When I press "Add process"
    And I wait for the page to be loaded
    And I select "RP Test Offer" from "job_offer"
    And I wait for the page to be loaded
    And I select "Andrea Costea" from "application"
    And I fill in "notes" with "Process started for Andrea Costea on RP Test Offer"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "RP Test Offer"
    And I should see "Andrea Costea"
    And I should not see an error

  Scenario: HR user views the recruitment process detail
    Given I am an HR user
    And I am on "/hr/recruitment-processes"
    And I wait for the page to be loaded
    Then I should see "RP Test Offer"
    When I click the first "format-list-checks" icon button in the table
    And I wait for the page to be loaded
    Then I should see "RP Test Offer"
    And I should see "Andrea Costea"
    And I should see "Process tracking"
    And I should not see an error

  Scenario: HR user adds a tracking entry to the process
    Given I am an HR user
    And I am on "/hr/recruitment-processes"
    And I wait for the page to be loaded
    When I click the first "format-list-checks" icon button in the table
    And I wait for the page to be loaded
    Then I should see "Process tracking"
    When I press "Add tracking entry"
    And I wait for the page to be loaded
    And I select "RP Test Stage" from "stage"
    And I fill in "notes" with "Candidate passed the initial screening stage."
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "RP Test Stage"
    And I should see "Candidate passed the initial screening stage."
    And I should not see an error

  Scenario: HR user deletes the tracking entry
    Given I am an HR user
    And I am on "/hr/recruitment-processes"
    And I wait for the page to be loaded
    When I click the first "format-list-checks" icon button in the table
    And I wait for the page to be loaded
    Then I should see "RP Test Stage"
    When I click the delete button near "RP Test Stage"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "RP Test Stage"
    And I should not see an error

  Scenario: HR user deletes the recruitment process
    Given I am an HR user
    And I am on "/hr/recruitment-processes"
    And I wait for the page to be loaded
    Then I should see "RP Test Offer"
    When I click the delete button in the row containing "RP Test Offer"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "RP Test Offer"
    And I should not see an error

  Scenario: Admin deletes the application and cleans up prerequisites
    Given I am a platform administrator
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    When I click the "format-list-bulleted" icon button in the row containing "RP Test Offer"
    And I wait for the page to be loaded
    Then I should see "Andrea Costea"
    When I click the delete button in the row containing "Andrea Costea"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Andrea Costea"
    And I should not see an error
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "RP Test Offer"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "RP Test Offer"
    And I should not see an error
    And I am on "/hr/recruitment-stages"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "RP Test Stage"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "RP Test Stage"
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "RP Test Position"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "RP Test Position"
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "RP Test Function"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "RP Test Function"
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "RP Test Unit"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "RP Test Unit"
    And I should not see an error
