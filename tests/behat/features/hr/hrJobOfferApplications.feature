Feature: HR Job Offer Applications
  In order to manage the application and candidate evaluation workflow
  As a student, HR user, or administrator
  I need to apply to job offers, view my applications, and evaluate candidates

  Scenario: Setup prerequisites and create a public job offer
    Given I am a platform administrator
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    When I press "Add business unit"
    And I wait for the page to be loaded
    And I fill in "unit_title" with "App Test Unit"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "App Test Unit"
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    When I press "Add function"
    And I wait for the page to be loaded
    And I fill in "function_title" with "App Test Function"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "App Test Function"
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    When I press "Add association"
    And I wait for the page to be loaded
    And I fill in "fiu_title" with "App Test Position"
    And I select "App Test Function" from "fiu_professional_function"
    And I select "App Test Unit" from "fiu_business_unit"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "App Test Position"
    And I should not see an error
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    When I press "Add job offer"
    And I wait for the page to be loaded
    And I fill in "title" with "App Test Public Offer"
    And I select "App Test Position — App Test Unit" from "function_in_unit"
    And I fill in "description" with "A public job offer for application testing"
    And I fill in "salary" with "2500 USD"
    And I check "is_public"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "App Test Public Offer"
    And I should not see an error

  Scenario: Public user sees the job offer list
    Given I am on "/job-offers"
    And I wait for the page to be loaded
    Then I should see "App Test Public Offer"
    And I should not see an error

  Scenario: Student sees job offer details with required skills section
    Given I am a student
    And I am on "/job-offers"
    And I wait for the page to be loaded
    Then I should see "App Test Public Offer"
    When I press "See details"
    And I wait for the page to be loaded
    Then I should see "App Test Public Offer"
    And I should see "A public job offer for application testing"
    And I should not see an error

  Scenario: Student applies to the job offer
    Given I am a student
    And I am on "/job-offers"
    And I wait for the page to be loaded
    When I press "See details"
    And I wait for the page to be loaded
    Then I should see "Apply now"
    When I press "Apply now"
    And I wait for the page to be loaded
    And I fill in "introduction" with "I am very interested in this position and believe my skills are a great match."
    And I fill in "salary_expectations" with "2800 USD"
    And I press "Submit application"
    And I wait for the page to be loaded
    Then I should see "Already applied"
    And I should not see an error

  Scenario: Student sees their application in My Applications
    Given I am a student
    And I am on "/job-offers/my-applications"
    And I wait for the page to be loaded
    Then I should see "My applications"
    And I should see "App Test Public Offer"
    And I should see "Under review"
    And I should not see an error

  Scenario: HR user views the applications list for the job offer
    Given I am an HR user
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    Then I should see "App Test Public Offer"
    When I click the "format-list-bulleted" icon button in the row containing "App Test Public Offer"
    And I wait for the page to be loaded
    Then I should see "Applications"
    And I should see "App Test Public Offer"
    And I should see "Andrea Costea"
    And I should not see an error

  Scenario: HR user evaluates the candidate
    Given I am an HR user
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    When I click the "format-list-bulleted" icon button in the row containing "App Test Public Offer"
    And I wait for the page to be loaded
    Then I should see "Andrea Costea"
    When I click the first "account-details" icon button in the table
    And I wait for the page to be loaded
    Then I should see "Evaluation"
    And I check the "Hired" radio button
    And I fill in "cv_observation" with "Strong CV with relevant experience."
    And I fill in "observation" with "Excellent candidate, highly recommended."
    When I press "Save evaluation"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Student sees updated hired status in My Applications
    Given I am a student
    And I am on "/job-offers/my-applications"
    And I wait for the page to be loaded
    Then I should see "App Test Public Offer"
    And I should see "Hired"
    And I should not see an error

  Scenario: HR user deletes the application and admin cleans up
    Given I am an HR user
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    When I click the "format-list-bulleted" icon button in the row containing "App Test Public Offer"
    And I wait for the page to be loaded
    Then I should see "Andrea Costea"
    When I click the delete button in the row containing "Andrea Costea"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Andrea Costea"
    And I should not see an error

  Scenario: Admin deletes job offer and cleans up prerequisites
    Given I am a platform administrator
    And I am on "/hr/job-offers"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "App Test Public Offer"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "App Test Public Offer"
    And I should not see an error
    And I am on "/hr/function-in-unit"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "App Test Position"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "App Test Position"
    And I am on "/hr/professional-functions"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "App Test Function"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "App Test Function"
    And I am on "/hr/business-units"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "App Test Unit"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "App Test Unit"
    And I should not see an error
