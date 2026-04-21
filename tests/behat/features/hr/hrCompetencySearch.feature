Feature: HR Competency Profile Search
  In order to find staff with specific competencies
  As an administrator
  I need to search and compare competency profiles

  Scenario: Admin can access the competency search page
    Given I am a platform administrator
    And I am on "/hr/competency-search"
    And I wait for the page to be loaded
    Then I should see "Competency search"
    And I should see "Search by skill"
    And I should see "Search by function profile"
    And I should see "Compare two staff members"
    And I should see "User vs. function profile"
    And I should not see an error

  Scenario: Admin searches by skill mode
    Given I am a platform administrator
    And I am on "/hr/competency-search"
    And I wait for the page to be loaded
    When I press "a) Search by skill"
    And I wait for the page to be loaded
    And I press "Search"
    And I wait for the page to be loaded
    Then I should not see an error

  Scenario: Admin switches to compare users mode
    Given I am a platform administrator
    And I am on "/hr/competency-search"
    And I wait for the page to be loaded
    When I press "c) Compare two staff members"
    And I wait for the page to be loaded
    Then I should see "User A"
    And I should see "User B"
    And I should not see an error

  Scenario: Admin switches to user vs function profile mode
    Given I am a platform administrator
    And I am on "/hr/competency-search"
    And I wait for the page to be loaded
    When I press "d) User vs. function profile"
    And I wait for the page to be loaded
    Then I should see "Staff member"
    And I should see "Function-unit association"
    And I should not see an error

  Scenario: Non-admin cannot access the competency search page
    Given I am an HR user
    And I am on "/hr/competency-search"
    And I wait for the page to be loaded
    Then I should not see "Competency search"
