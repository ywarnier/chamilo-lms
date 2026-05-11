Feature: HR Survey Distribution
  In order to distribute surveys to organizational units
  As an administrator or HR user
  I need to be able to manage training need and work climate survey distributions

  Scenario: Admin sees training needs surveys page
    Given I am a platform administrator
    And I am on "/surveys-list/training-need-assessments"
    And I wait for the page to be loaded
    Then I should see "Training needs assessment"
    And I should not see an error

  Scenario: HR user sees training needs surveys page
    Given I am an HR user
    And I am on "/surveys-list/training-need-assessments"
    And I wait for the page to be loaded
    Then I should see "Training needs assessment"
    And I should not see an error

  Scenario: Admin sees work climate surveys page
    Given I am a platform administrator
    And I am on "/surveys-list/work-climate"
    And I wait for the page to be loaded
    Then I should see "Work climate surveys"
    And I should not see an error

  Scenario: HR user sees work climate surveys page
    Given I am an HR user
    And I am on "/surveys-list/work-climate"
    And I wait for the page to be loaded
    Then I should see "Work climate surveys"
    And I should not see an error

  Scenario: Student cannot access training needs surveys page
    Given I am a student
    And I am on "/surveys-list/training-need-assessments"
    And I wait for the page to be loaded
    Then I should not see "Training needs assessment"

  Scenario: Student cannot access work climate surveys page
    Given I am a student
    And I am on "/surveys-list/work-climate"
    And I wait for the page to be loaded
    Then I should not see "Work climate surveys"

  Scenario: Admin creates and deletes a training needs distribution end-to-end
    Given a CSurvey titled "Behat training survey" exists in course "1"
    And a business unit titled "Behat Unit TN" exists
    And I am a platform administrator
    And I am on "/surveys-list/training-need-assessments"
    And I wait for the page to be loaded
    When I press "Add distribution"
    And I wait for the page to be loaded
    And I select "Behat training survey" from "survey"
    And I select "Behat Unit TN" from "business_unit"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Behat training survey"
    And I should see "Behat Unit TN"
    And I should not see an error
    When I click the delete button in the row containing "Behat training survey"
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Behat training survey"

  Scenario: Cross-category isolation — training_need distribution does NOT show in work_climate
    Given a CSurvey titled "Behat isolation survey" exists in course "1"
    And a business unit titled "Behat Unit Iso" exists
    And I am a platform administrator
    And I am on "/surveys-list/training-need-assessments"
    And I wait for the page to be loaded
    When I press "Add distribution"
    And I wait for the page to be loaded
    And I select "Behat isolation survey" from "survey"
    And I select "Behat Unit Iso" from "business_unit"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Behat isolation survey"
    When I am on "/surveys-list/work-climate"
    And I wait for the page to be loaded
    Then I should not see "Behat isolation survey"
    When I am on "/surveys-list/training-need-assessments"
    And I wait for the page to be loaded
    And I click the delete button in the row containing "Behat isolation survey"
    And I confirm the PrimeVue dialog

  Scenario: HR user creates and deletes a work climate distribution
    Given a CSurvey titled "Behat climate survey" exists in course "1"
    And a business unit titled "Behat Unit WC" exists
    And I am an HR user
    And I am on "/surveys-list/work-climate"
    And I wait for the page to be loaded
    When I press "Add distribution"
    And I wait for the page to be loaded
    And I select "Behat climate survey" from "survey"
    And I select "Behat Unit WC" from "business_unit"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Behat climate survey"
    When I click the delete button in the row containing "Behat climate survey"
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Behat climate survey"
