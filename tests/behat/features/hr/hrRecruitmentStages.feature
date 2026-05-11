Feature: HR Recruitment Stages
  In order to define structured stages for the hiring process
  As an administrator or HR user
  I need to be able to create, edit and delete recruitment stages

  Scenario: Admin creates a recruitment stage
    Given I am a platform administrator
    And I am on "/hr/recruitment-stages"
    And I wait for the page to be loaded
    Then I should see "Recruitment stages"
    When I press "Add stage"
    And I wait for the page to be loaded
    And I fill in "title" with "Initial Screening"
    And I fill in "description" with "First contact and CV review"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Initial Screening"
    And I should not see an error

  Scenario: Admin creates a second recruitment stage
    Given I am a platform administrator
    And I am on "/hr/recruitment-stages"
    And I wait for the page to be loaded
    When I press "Add stage"
    And I wait for the page to be loaded
    And I fill in "title" with "Technical Interview"
    And I fill in "description" with "Technical skills assessment"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Technical Interview"
    And I should not see an error

  Scenario: Admin edits a recruitment stage
    Given I am a platform administrator
    And I am on "/hr/recruitment-stages"
    And I wait for the page to be loaded
    Then I should see "Initial Screening"
    When I click the edit button in the row containing "Initial Screening"
    And I wait for the page to be loaded
    And I fill in "title" with "Initial Screening Updated"
    And I fill in "description" with "First contact, CV review and phone screening"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Initial Screening Updated"
    And I should not see an error

  Scenario: HR user creates a recruitment stage
    Given I am an HR user
    And I am on "/hr/recruitment-stages"
    And I wait for the page to be loaded
    Then I should see "Recruitment stages"
    When I press "Add stage"
    And I wait for the page to be loaded
    And I fill in "title" with "HR Stage"
    And I fill in "description" with "Created by HR user"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "HR Stage"
    And I should not see an error

  Scenario: HR user edits a recruitment stage
    Given I am an HR user
    And I am on "/hr/recruitment-stages"
    And I wait for the page to be loaded
    When I click the edit button in the row containing "HR Stage"
    And I wait for the page to be loaded
    And I fill in "title" with "HR Stage Updated"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "HR Stage Updated"
    And I should not see an error

  Scenario: HR user deletes their recruitment stage
    Given I am an HR user
    And I am on "/hr/recruitment-stages"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "HR Stage Updated"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "HR Stage Updated"
    And I should not see an error

  Scenario: Admin deletes recruitment stages
    Given I am a platform administrator
    And I am on "/hr/recruitment-stages"
    And I wait for the page to be loaded
    When I click the delete button in the row containing "Initial Screening Updated"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    And I should not see an error
    When I click the delete button in the row containing "Technical Interview"
    And I wait for the page to be loaded
    And I confirm the PrimeVue dialog
    And I wait for the page to be loaded
    Then I should not see "Initial Screening Updated"
    And I should not see "Technical Interview"
    And I should not see an error
