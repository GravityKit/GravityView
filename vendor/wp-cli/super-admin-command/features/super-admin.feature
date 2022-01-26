Feature: Manage super admins associated with a multisite instance

  Scenario: Add, list, and remove super admins.
    Given a WP multisite installation

    When I run `wp user create superadmin superadmin@example.com`
    And I run `wp super-admin list`
    Then STDOUT should be:
      """
      admin
      """

    When I run `wp super-admin add superadmin`
    Then STDOUT should be:
      """
      Success: Granted super-admin capabilities to 1 user.
      """
    And the return code should be 0

    When I run `wp super-admin list`
    Then STDOUT should be:
      """
      admin
      superadmin
      """

    When I try `wp super-admin add superadmin`
    Then STDERR should be:
      """
      Warning: User 'superadmin' already has super-admin capabilities.
      """
    And STDOUT should be:
      """
      Success: Super admins remain unchanged.
      """
    And the return code should be 0

    When I run `wp super-admin list`
    Then STDOUT should be:
      """
      admin
      superadmin
      """

    When I run `wp super-admin list --format=table`
    Then STDOUT should be a table containing rows:
      | user_login |
      | admin      |
      | superadmin |

    When I run `wp super-admin remove admin`
    And I run `wp super-admin list`
    Then STDOUT should be:
      """
      superadmin
      """

    When I run `wp super-admin list --format=json`
    Then STDOUT should be:
      """
      [{"user_login":"superadmin"}]
      """

    When I try `wp super-admin add noadmin`
    Then STDERR should be:
      """
      Warning: Invalid user ID, email or login: 'noadmin'
      Error: Couldn't grant super-admin capabilities to 1 of 1 users.
      """
    And the return code should be 1

    When I try `wp super-admin add admin noadmin`
    Then STDERR should be:
      """
      Warning: Invalid user ID, email or login: 'noadmin'
      Error: Only granted super-admin capabilities to 1 of 2 users.
      """
    And the return code should be 1

    When I try `wp super-admin remove noadmin`
    Then STDERR should be:
      """
      Warning: Invalid user ID, email or login: 'noadmin'
      Error: The given user is not a super admin.
      """
    And the return code should be 1

    When I try `wp super-admin remove admin admin@example.com noadmin superadmin`
    Then STDERR should be:
      """
      Warning: Invalid user ID, email or login: 'noadmin'
      """
    And STDOUT should be:
      """
      Success: Revoked super-admin capabilities from 2 of 3 users. There are no remaining super admins.
      """

    When I run `wp super-admin add superadmin`
    And I try `wp super-admin remove admin superadmin`
    Then STDOUT should be:
      """
      Success: Revoked super-admin capabilities from 1 of 2 users. There are no remaining super admins.
      """
    And STDERR should be empty

    When I run `wp super-admin list`
    Then STDOUT should be empty

    When I try `wp super-admin remove superadmin`
    Then STDERR should be:
      """
      Error: No super admins to revoke super-admin privileges from.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp super-admin add superadmin admin`
    And I run `wp super-admin remove superadmin admin`
    Then STDOUT should be:
      """
      Success: Revoked super-admin capabilities from 2 users. There are no remaining super admins.
      """
    And STDERR should be empty

    When I run `wp super-admin list`
    Then STDOUT should be empty

    When I run `wp user create admin2 admin2@example.com`
    And I run `wp super-admin add superadmin admin admin2`
    And I run `wp super-admin list`
    Then STDOUT should be:
      """
      superadmin
      admin
      admin2
      """

    When I run `wp eval 'global $wpdb; $wpdb->delete( $wpdb->users, array( "user_login" => "admin2" ) );'`
    And I run `wp user list --field=user_login --orderby=user_login`
    Then STDOUT should be:
      """
      admin
      superadmin
      """

    And I run `wp super-admin list`
    Then STDOUT should be:
      """
      superadmin
      admin
      admin2
      """

    When I try `wp super-admin remove admin2`
    Then STDERR should be:
      """
      Warning: Invalid user ID, email or login: 'admin2'
      """
    And STDOUT should be:
      """
      Success: Revoked super-admin capabilities from 1 user.
      """

    When I try `wp super-admin remove 999999`
    Then STDERR should be:
      """
      Warning: Invalid user ID, email or login: '999999'
      Error: No valid user logins given to revoke super-admin privileges from.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp user create notadmin notadmin@example.com`
    And I try `wp super-admin remove notadmin notuser`
    Then STDERR should be:
      """
      Warning: Invalid user ID, email or login: 'notuser'
      Error: None of the given users is a super admin.
      """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: Manage a super admin user_login 'admin'
    Given a WP multisite installation

    When I run `wp user get admin --field=user_login`
    Then STDOUT should contain:
      """
      admin
      """

    When I try `wp super-admin add admin`
    Then STDOUT should be:
      """
      Success: Super admins remain unchanged.
      """
    And STDERR should be:
      """
      Warning: User 'admin' already has super-admin capabilities.
      """

    When I run `wp super-admin remove admin`
    Then STDOUT should be:
      """
      Success: Revoked super-admin capabilities from 1 user. There are no remaining super admins.
      """

    When I run `wp super-admin list`
    Then STDOUT should be empty

    When I run `wp super-admin add admin`
    Then STDOUT should be:
      """
      Success: Granted super-admin capabilities to 1 user.
      """
    And STDERR should be empty

    When I run `wp super-admin list`
    Then STDOUT should be:
      """
      admin
      """

  Scenario: Handle a site with an empty site_admins option without errors
    Given a WP multisite installation

    When I run `wp site option set site_admins ''`
    Then STDOUT should be:
      """
      Success: Updated 'site_admins' site option.
      """
    And STDERR should be empty

    When I run `wp super-admin list`
    Then STDERR should be empty

  Scenario: Hooks should be firing as expected
    Given a WP multisite installation
    And a wp-content/mu-plugins/test-hooks.php file:
      """
      <?php
      add_action( 'grant_super_admin', static function () {
        WP_CLI::log( 'grant_super_admin hook was fired.' );
      });
      add_action( 'granted_super_admin', static function () {
        WP_CLI::log( 'granted_super_admin hook was fired.' );
      });
      add_action( 'revoke_super_admin', static function () {
        WP_CLI::log( 'revoke_super_admin hook was fired.' );
      });
      add_action( 'revoked_super_admin', static function () {
        WP_CLI::log( 'revoked_super_admin hook was fired.' );
      });
      """

    When I run `wp user create superadmin superadmin@example.com`
    And I run `wp super-admin add superadmin`
    Then STDOUT should contain:
      """
      grant_super_admin hook was fired.
      """
    And STDOUT should contain:
      """
      granted_super_admin hook was fired.
      """

    When I try `wp super-admin add superadmin`
    Then STDOUT should contain:
      """
      grant_super_admin hook was fired.
      """
    And STDOUT should not contain:
      """
      granted_super_admin hook was fired.
      """

    When I run `wp super-admin remove admin`
    Then STDOUT should contain:
      """
      revoke_super_admin hook was fired.
      """
    And STDOUT should contain:
      """
      revoked_super_admin hook was fired.
      """

    When I try `wp super-admin add noadmin`
    Then STDOUT should not contain:
      """
      grant_super_admin hook was fired.
      """
    And STDOUT should not contain:
      """
      granted_super_admin hook was fired.
      """

    When I try `wp super-admin add admin noadmin`
    Then STDOUT should contain:
      """
      grant_super_admin hook was fired.
      """
    And STDOUT should not contain:
      """
      granted_super_admin hook was fired.
      """

    When I try `wp super-admin remove noadmin`
    Then STDOUT should not contain:
      """
      revoke_super_admin hook was fired.
      """
    And STDOUT should not contain:
      """
      revoked_super_admin hook was fired.
      """

    When I try `wp super-admin remove admin admin@example.com noadmin superadmin`
    Then STDOUT should contain:
      """
      revoke_super_admin hook was fired.
      """
    And STDOUT should contain:
      """
      revoked_super_admin hook was fired.
      """

    When I try `wp super-admin remove superadmin`
    Then STDOUT should contain:
      """
      revoke_super_admin hook was fired.
      """
    And STDOUT should not contain:
      """
      revoked_super_admin hook was fired.
      """
