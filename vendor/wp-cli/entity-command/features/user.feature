Feature: Manage WordPress users

  Scenario: User CRUD operations
    Given a WP install

    When I try `wp user get bogus-user`
    Then the return code should be 1
    And STDOUT should be empty

    When I run `wp user create testuser2 testuser2@example.com --first_name=test --last_name=user --role=author --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID}

    When I run `wp user get {USER_ID}`
    Then STDOUT should be a table containing rows:
      | Field        | Value      |
      | ID           | {USER_ID}  |
      | roles        | author     |

    When I run `wp user meta get {USER_ID} first_name`
    Then STDOUT should be:
      """
      test
      """

    When I run `wp user list --fields=user_login,roles`
    Then STDOUT should be a table containing rows:
      | user_login        | roles      |
      | testuser2         | author     |

    When I run `wp user meta get {USER_ID} last_name`
    Then STDOUT should be:
      """
      user
      """

    When I run `wp user delete {USER_ID} --yes`
    Then STDOUT should not be empty

    When I try `wp user create testuser2 testuser2@example.com --role=wrongrole --porcelain`
    Then the return code should be 1
    Then STDOUT should be empty

    When I run `wp user create testuser testuser@example.com --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {USER_ID}

    When I try the previous command again
    Then the return code should be 1

    When I run `wp user update {USER_ID} --display_name=Foo`
    And I run `wp user get {USER_ID}`
    Then STDOUT should be a table containing rows:
      | Field        | Value     |
      | ID           | {USER_ID} |
      | display_name | Foo       |

    When I run `wp user get testuser@example.com`
    Then STDOUT should be a table containing rows:
      | Field        | Value     |
      | ID           | {USER_ID} |
      | display_name | Foo       |

    When I run `wp user delete {USER_ID} --yes`
    Then STDOUT should not be empty

    When I run `wp user create testuser3 testuser3@example.com --user_pass=testuser3pass`
    Then STDOUT should not contain:
       """
       Password:
       """

    # Check with valid password.
    When I run `wp user check-password testuser3 testuser3pass`
    Then the return code should be 0

    # Check with invalid password.
    When I try `wp user check-password testuser3 invalidpass`
    Then the return code should be 1

    When I try `wp user check-password invaliduser randomstring`
    Then STDERR should contain:
      """
      Invalid user ID, email or login: 'invaliduser'
      """
    And the return code should be 1

    When I run `wp user create testuser3b testuser3b@example.com --user_pass="test\"user3b's\pass\!"`
    Then STDOUT should not contain:
       """
       Password:
       """

    # Check password without the `--escape-chars` option.
    When I try `wp user check-password testuser3b "test\"user3b's\pass\!"`
    Then STDERR should be:
      """
      Warning: Password contains characters that need to be escaped. Please escape them manually or use the `--escape-chars` option.
      """
    And the return code should be 1

    # Check password with the `--escape-chars` option.
    When I try `wp user check-password testuser3b "test\"user3b's\pass\!" --escape-chars`
    Then the return code should be 0

    # Check password with manually escaped characters.
    When I try `wp user check-password testuser3b "test\\\"user3b\'s\\\pass\\\!"`
    Then the return code should be 0

  Scenario: Reassigning user posts
    Given a WP multisite install

    When I run `wp user create bobjones bob@example.com --role=author --porcelain`
    And save STDOUT as {BOB_ID}

    And I run `wp user create sally sally@example.com --role=editor --porcelain`
    And save STDOUT as {SALLY_ID}

    When I run `wp post generate --count=3 --post_author=bobjones`
    And I run `wp post list --author={BOB_ID} --format=count`
    Then STDOUT should be:
      """
      3
      """

    When I run `wp user delete bobjones --reassign={SALLY_ID}`
    And I run `wp post list --author={SALLY_ID} --format=count`
    Then STDOUT should be:
      """
      3
      """

  Scenario: Deleting user from the whole network
    Given a WP multisite install

    When I run `wp user create bobjones bob@example.com --role=author --porcelain`
    And save STDOUT as {BOB_ID}

    When I run `wp user get bobjones`
    Then STDOUT should not be empty

    When I run `wp user delete bobjones --network --yes`
    Then STDOUT should not be empty

    When I try `wp user get bobjones`
    Then STDERR should not be empty
    And the return code should be 1

  @require-wp-4.0
  Scenario: Trying to delete super admin
    Given a WP multisite install

    When I run `wp user create bobjones bob@example.com --role=author --porcelain`
    And save STDOUT as {BOB_ID}

    When I run `wp super-admin add {BOB_ID}`
    And I try `wp user delete bobjones --network --yes`
    Then STDERR should be:
      """
      Warning: Failed deleting user {BOB_ID}. The user is a super admin.
      """
    And the return code should be 1

  Scenario: Create new users on multisite
    Given a WP multisite install

    When I try `wp user create bob-jones bobjones@example.com`
    Then STDERR should contain:
      """
      lowercase letters (a-z) and numbers
      """
    And the return code should be 1

    When I run `wp user create bobjones bobjones@example.com --display_name="Bob Jones"`
    Then STDOUT should not be empty

    When I run `wp user get bobjones --field=display_name`
    Then STDOUT should be:
      """
      Bob Jones
      """

  Scenario: Managing user roles
    Given a WP install

    When I run `wp user add-role 1 editor`
    Then STDOUT should not be empty
    And I run `wp user get 1 --field=roles`
    Then STDOUT should be:
      """
      administrator, editor
      """

    When I try `wp user add-role 1 edit`
    Then STDERR should contain:
      """
      Role doesn't exist
      """
    And the return code should be 1

    When I try `wp user set-role 1 edit`
    Then STDERR should contain:
      """
      Role doesn't exist
      """
    And the return code should be 1

    When I try `wp user remove-role 1 edit`
    Then STDERR should contain:
      """
      Role doesn't exist
      """
    And the return code should be 1

    When I run `wp user set-role 1 author`
    Then STDOUT should not be empty
    And I run `wp user get 1`
    Then STDOUT should be a table containing rows:
      | Field | Value  |
      | roles | author |

    When I run `wp user remove-role 1 editor`
    Then STDOUT should not be empty
    And I run `wp user get 1`
    Then STDOUT should be a table containing rows:
      | Field | Value  |
      | roles | author |

    When I run `wp user remove-role 1`
    Then STDOUT should not be empty
    And I run `wp user get 1`
    Then STDOUT should be a table containing rows:
      | Field | Value |
      | roles |       |

  Scenario: Managing user capabilities
    Given a WP install

    When I run `wp user add-cap 1 edit_vip_product`
    Then STDOUT should be:
      """
      Success: Added 'edit_vip_product' capability for admin (1).
      """

    And I run `wp user list-caps 1 | tail -n 1`
    Then STDOUT should be:
      """
      edit_vip_product
      """

    And I run `wp user remove-cap 1 edit_vip_product`
    Then STDOUT should be:
      """
      Success: Removed 'edit_vip_product' cap for admin (1).
      """

    And I try the previous command again
    Then the return code should be 1
    And STDERR should be:
      """
      Error: No such 'edit_vip_product' cap for admin (1).
      """
    And STDOUT should be empty

    When I run `wp user list-caps 1`
    Then STDOUT should not contain:
      """
      edit_vip_product
      """
    And STDOUT should contain:
      """
      publish_posts
      """

    When I try `wp user remove-cap 1 publish_posts`
    Then the return code should be 1
    And STDERR should be:
      """
      Error: The 'publish_posts' cap for admin (1) is inherited from a role.
      """
    And STDOUT should be empty

    And I run `wp user list-caps 1`
    Then STDOUT should contain:
      """
      publish_posts
      """

  Scenario: Show password when creating a user
    Given a WP install

    When I run `wp user create testrandompass testrandompass@example.com`
    Then STDOUT should contain:
       """
       Password:
       """

    When I run `wp user create testsuppliedpass testsuppliedpass@example.com --user_pass=suppliedpass`
    Then STDOUT should not contain:
       """
       Password:
       """

  Scenario: List network users
    Given a WP multisite install

    When I run `wp user create testsubscriber testsubscriber@example.com`
    Then STDOUT should contain:
      """
      Success: Created user
      """

    When I run `wp user list --field=user_login`
    Then STDOUT should contain:
      """
      testsubscriber
      """

    When I run `wp user delete testsubscriber --yes`
    Then STDOUT should contain:
      """
      Success: Removed user
      """

    When I run `wp user list --field=user_login`
    Then STDOUT should not contain:
      """
      testsubscriber
      """

    When I run `wp user list --field=user_login --network`
    Then STDOUT should contain:
      """
      testsubscriber
      """

  Scenario: Listing user capabilities
    Given a WP install

    When I run `wp user create bob bob@gmail.com --role=contributor`
    And I run `wp user list-caps bob`
    Then STDOUT should be:
      """
      edit_posts
      read
      level_1
      level_0
      delete_posts
      contributor
      """

    And I run `wp user list-caps bob --format=json`
    Then STDOUT should be:
      """
      [{"name":"edit_posts"},{"name":"read"},{"name":"level_1"},{"name":"level_0"},{"name":"delete_posts"},{"name":"contributor"}]
      """

    And I run `wp user list-caps bob --format=count`
    Then STDOUT should be:
      """
      6
      """

  Scenario: Make sure WordPress receives the slashed data it expects
    Given a WP install

    When I run `wp user create slasheduser slasheduser@example.com --display_name='My\User' --porcelain`
    Then save STDOUT as {USER_ID}

    When I run `wp user get {USER_ID} --field=display_name`
    Then STDOUT should be:
      """
      My\User
      """

    When I run `wp user update {USER_ID} --display_name='My\New\User'`
    Then STDOUT should not be empty

    When I run `wp user get {USER_ID} --field=display_name`
    Then STDOUT should be:
      """
      My\New\User
      """

  Scenario: Don't send user creation emails by default
    Given a WP multisite install

    When I run `wp user create testuser2 testuser2@example.com`
    Then an email should not be sent

    When I run `wp user create testuser3 testuser3@example.com --send-email`
    Then an email should be sent

  Scenario: List URLs of one or more users
    Given a WP install
    And I run `wp user create bob bob@gmail.com --role=contributor`

    When I run `wp user list --include=1,2 --field=url`
    Then STDOUT should be:
      """
      https://example.com/?author=1
      https://example.com/?author=2
      """

  Scenario: Get user with email as login
    Given a WP install
    And I run `wp user create testuser4@example.com testemail4@example.com`

    When I run `wp user get testemail4@example.com --field=user_login`
    Then STDOUT should be:
      """
      testuser4@example.com
      """

    When I run `wp user get testuser4@example.com --field=user_login`
    Then STDOUT should be:
      """
      testuser4@example.com
      """

  Scenario: Mark/remove a user from spam
    Given a WP multisite install
    And I run `wp user create bumblebee bbee@example.com --role=author --porcelain`
    And save STDOUT as {BBEE_ID}
    And I run `wp user create oprime oprime@example.com --role=author --porcelain`
    And save STDOUT as {OP_ID}
    And I run `wp user get bumblebee`
    Then STDOUT should not be empty
    And I run `wp user get oprime`
    Then STDOUT should not be empty

    When I run `wp user spam {BBEE_ID}`
    Then STDOUT should be:
      """
      User {BBEE_ID} marked as spam.
      Success: Spammed 1 of 1 users.
      """

    When I try the previous command again
    Then STDERR should be:
      """
      Warning: User {BBEE_ID} already marked as spam.
      """
    And STDOUT should be:
      """
      Success: User already spammed.
      """
    And the return code should be 0

    When I try `wp user spam {OP_ID} 9999`
    Then STDOUT should be:
      """
      User {OP_ID} marked as spam.
      """
    And STDERR should be:
      """
      Warning: Invalid user ID, email or login: '9999'
      Warning: User 9999 doesn't exist.
      Error: Only spammed 1 of 2 users.
      """
    And the return code should be 1

  @require-wp-4.3
  Scenario: Sending emails on update
    Given a WP install

    When I run `wp user get 1 --field=user_email`
    Then save STDOUT as {ORIGINAL_EMAIL}

    When I run `wp user update 1 --user_email=different.mail@example.com`
    Then STDOUT should contain:
      """
      Success: Updated user 1.
      """
    And an email should be sent

    When I run `wp user update 1 --user_email={ORIGINAL_EMAIL} --skip-email`
    Then STDOUT should contain:
      """
      Success: Updated user 1.
      """
    And an email should not be sent

    When I run `wp user get 1 --field=user_pass`
    Then save STDOUT as {ORIGINAL_PASSWORD}

    When I run `wp user update 1 --user_pass=different_password`
    Then STDOUT should contain:
      """
      Success: Updated user 1.
      """
    And an email should be sent

    When I run `wp user update 1 --user_pass={ORIGINAL_PASSWORD} --skip-email`
    Then STDOUT should contain:
      """
      Success: Updated user 1.
      """
    And an email should not be sent
