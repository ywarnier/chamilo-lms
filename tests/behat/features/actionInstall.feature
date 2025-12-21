@administration
Feature: Install portal

  Scenario: Installation process
    Given I am on "/main/install/index.php"
    And I wait for the page to be loaded when ready
    Then I should see "Step 1 - Installation Language"
    When I press "Next"
    Then I should see "Step 2 - Requirements"
    When I press "New installation"
    And I wait for the page to be loaded
    Then I should see "Step 3 - License"
    When I check "accept_licence"
    And I press "license-next"
    Then I should see "Step 4 - Database settings"
    When I fill in the following:
      | dbUsernameForm | root |
      | dbPassForm | root |
      | dbNameForm | master |
    And I press "Check database connection"
    And I wait for the page to be loaded when ready
    And I press "step4"
    Then I should see "Step 5 - Configuration settings"
    When I fill in the following:
      | passForm | admin |
      | emailForm | admin@example.com |
      | mailerDsn | null://null  |
      | mailerFromEmail | noreply@example.com |
      | mailerFromName  | Chamilo Behat install |
    And I press "step5"
    Then I should see "Step 6 - Last check before install"
    When I wait for the page to be loaded when ready
    And I press "button_step6"
    And I wait one minute for the page to be loaded
    Then I should see "Step 7"
    And I should see "Go to your newly created portal"

