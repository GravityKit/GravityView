Feature: Manage Cap

  Background:
    Given a WP install

  Scenario: CRUD for cap
    When I run `wp cap list contributor | sort`
    Then STDOUT should be:
      """
      delete_posts
      edit_posts
      level_0
      level_1
      read
      """

    When I run `wp cap add contributor spectate`
    Then STDOUT should contain:
      """
      Success: Added 1 capability to 'contributor' role.
      """

    When I run `wp cap add contributor behold observe`
    Then STDOUT should contain:
      """
      Success: Added 2 capabilities to 'contributor' role.
      """

    When I run `wp cap add contributor detect --no-grant`
    Then STDOUT should contain:
      """
      Success: Added 1 capability to 'contributor' role as false.
      """

    When I run `wp cap add contributor discover examine --no-grant`
    Then STDOUT should contain:
      """
      Success: Added 2 capabilities to 'contributor' role as false.
      """

    When I run `wp cap list contributor`
    Then STDOUT should contain:
      """
      spectate
      """
    And STDOUT should contain:
      """
      behold
      """
    And STDOUT should contain:
      """
      observe
      """
    Then STDOUT should not contain:
      """
      detect
      """
    Then STDOUT should not contain:
      """
      discover
      """
    Then STDOUT should not contain:
      """
      examine
      """

    When I run `wp cap list contributor --show-grant`
    Then STDOUT should contain:
      """
      spectate,true
      """
    And STDOUT should contain:
      """
      behold,true
      """
    And STDOUT should contain:
      """
      observe,true
      """
    Then STDOUT should contain:
      """
      detect,false
      """
    Then STDOUT should contain:
      """
      discover,false
      """
    Then STDOUT should contain:
      """
      examine,false
      """

    When I run `wp cap remove contributor spectate`
    Then STDOUT should contain:
      """
      Success: Removed 1 capability from 'contributor' role.
      """

    When I run `wp cap remove contributor behold observe`
    Then STDOUT should contain:
      """
      Success: Removed 2 capabilities from 'contributor' role.
      """

    When I run `wp cap remove contributor detect discover examine`
    Then STDOUT should contain:
      """
      Success: Removed 3 capabilities from 'contributor' role.
      """

    When I run `wp cap list contributor`
    Then STDOUT should not contain:
      """
      spectate
      """
    And STDOUT should not contain:
      """
      behold
      """
    And STDOUT should not contain:
      """
      observe
      """

    When I run `wp cap list contributor --show-grant`
    Then STDOUT should not contain:
      """
      spectate,true
      """
    And STDOUT should not contain:
      """
      behold,true
      """
    And STDOUT should not contain:
      """
      observe,true
      """
    Then STDOUT should not contain:
      """
      detect,false
      """
    And STDOUT should not contain:
      """
      discover,false
      """
    And STDOUT should not contain:
      """
      examine,false
      """

    When I try `wp cap add role-not-available spectate`
    Then STDERR should be:
      """
      Error: 'role-not-available' role not found.
      """
