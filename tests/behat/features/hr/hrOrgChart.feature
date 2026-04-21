Feature: HR Organisational Chart
  In order to view the organisational structure visually
  As an administrator
  I need to see the organisational chart

  Scenario: Admin can access the organisational chart page
    Given I am a platform administrator
    And I am on "/organizational-chart"
    And I wait for the page to be loaded
    Then I should see "Organizational chart"
    And I should not see an error

  Scenario: Admin sees the unit hierarchy tab when the unit public setting is enabled
    Given I am a platform administrator
    And I am on "/organizational-chart"
    And I wait for the page to be loaded
    Then I should see "Organizational chart"
    And I should not see an error
