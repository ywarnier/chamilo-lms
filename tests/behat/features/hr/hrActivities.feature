Feature: HR Activities
  In order to manage activities and their categories
  As an administrator
  I need to be able to create, edit and delete activity categories and activities

  Scenario: Create an activity category
    Given I am a platform administrator
    And I am on "/hr/activities"
    And I wait for the page to be loaded
    Then I should see "Activity categories"
    When I press "Add category"
    And I wait for the page to be loaded
    And I fill in "category_title" with "Work"
    And I fill in "category_description" with "Work-related activities"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Work"
    And I should not see an error

  Scenario: Edit an activity category
    Given I am a platform administrator
    And I am on "/hr/activities"
    And I wait for the page to be loaded
    Then I should see "Work"
    When I click the "section:first-of-type span.mdi-pencil" element
    And I wait for the page to be loaded
    And I fill in "category_title" with "Work Updated"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Work Updated"
    And I should not see an error

  Scenario: Create an activity without a category
    Given I am a platform administrator
    And I am on "/hr/activities"
    And I wait for the page to be loaded
    Then I should see "Activities"
    When I press "Add activity"
    And I wait for the page to be loaded
    And I fill in "activity_title" with "Presentation"
    And I fill in "activity_description" with "Giving a presentation to the team"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Presentation"
    And I should not see an error

  Scenario: Create an activity with a category
    Given I am a platform administrator
    And I am on "/hr/activities"
    And I wait for the page to be loaded
    When I press "Add activity"
    And I wait for the page to be loaded
    And I fill in "activity_title" with "Meeting"
    And I select "Work Updated" from "activity_category"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Meeting"
    And I should see "Work Updated"
    And I should not see an error

  Scenario: Edit an activity
    Given I am a platform administrator
    And I am on "/hr/activities"
    And I wait for the page to be loaded
    Then I should see "Presentation"
    When I click the "section:last-of-type span.mdi-pencil" element
    And I wait for the page to be loaded
    And I fill in "activity_title" with "Presentation Updated"
    And I press "Save"
    And I wait for the page to be loaded
    Then I should see "Presentation Updated"
    And I should not see an error

  Scenario: Delete an activity
    Given I am a platform administrator
    And I am on "/hr/activities"
    And I wait for the page to be loaded
    Then I should see "Presentation Updated"
    When I click the "section:last-of-type span.mdi-delete" element
    And I wait for the page to be loaded
    And I click the ".p-confirmdialog-accept-button button" element
    And I wait for the page to be loaded
    Then I should not see "Presentation Updated"
    And I should not see an error

  Scenario: Delete a second activity
    Given I am a platform administrator
    And I am on "/hr/activities"
    And I wait for the page to be loaded
    Then I should see "Meeting"
    When I click the "section:last-of-type span.mdi-delete" element
    And I wait for the page to be loaded
    And I click the ".p-confirmdialog-accept-button button" element
    And I wait for the page to be loaded
    Then I should not see "Meeting"
    And I should not see an error

  Scenario: Delete an activity category
    Given I am a platform administrator
    And I am on "/hr/activities"
    And I wait for the page to be loaded
    Then I should see "Work Updated"
    When I click the "section:first-of-type span.mdi-delete" element
    And I wait for the page to be loaded
    And I click the ".p-confirmdialog-accept-button button" element
    And I wait for the page to be loaded
    Then I should not see "Work Updated"
    And I should not see an error
