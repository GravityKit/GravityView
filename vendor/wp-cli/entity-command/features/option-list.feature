Feature: List WordPress options

  Scenario: Using the `--transients` flag
    Given a WP install
    And I run `wp transient set wp_transient_flag wp_transient_flag`

    When I run `wp option list --no-transients`
    Then STDOUT should not contain:
      """
      wp_transient_flag
      """
    And STDOUT should not contain:
      """
      _transient
      """
    And STDOUT should contain:
      """
      siteurl
      """

    When I run `wp option list --transients`
    Then STDOUT should contain:
      """
      wp_transient_flag
      """
    And STDOUT should contain:
      """
      _transient
      """
    And STDOUT should not contain:
      """
      siteurl
      """

  Scenario: List option with exclude pattern
    Given a WP install

    When I run `wp option add sample_test_field_one sample_test_field_value_one`
    And I run `wp option add sample_test_field_two sample_test_field_value_two`
    And I run `wp option list --search="sample_test_field_*" --format=csv`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_one,sample_test_field_value_one
      sample_test_field_two,sample_test_field_value_two
      """

    When I run `wp option list --search="sample_test_field_*" --exclude="*field_one" --format=csv`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_two,sample_test_field_value_two
      """

    When I run `wp option list`
    Then STDOUT should contain:
      """
      sample_test_field_one
      """

    When I run `wp option list --exclude="sample_test_field_one"`
    Then STDOUT should not contain:
      """
      sample_test_field_one
      """

  Scenario: List option with sorting option
    Given a WP install
    And I run `wp option add sample_test_field_one sample_test_field_value_one`
    And I run `wp option add sample_test_field_two sample_test_field_value_two`

    When I run `wp option list --search="sample_test_field_*" --format=csv --orderby=option_id --order=asc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_one,sample_test_field_value_one
      sample_test_field_two,sample_test_field_value_two
      """

    When I run `wp option list --search="sample_test_field_*" --format=csv --orderby=option_id --order=desc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_two,sample_test_field_value_two
      sample_test_field_one,sample_test_field_value_one
      """

    When I run `wp option list --search="sample_test_field_*" --format=csv --orderby=option_name --order=asc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_one,sample_test_field_value_one
      sample_test_field_two,sample_test_field_value_two
      """

    When I run `wp option list --search="sample_test_field_*" --format=csv --orderby=option_name --order=desc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_two,sample_test_field_value_two
      sample_test_field_one,sample_test_field_value_one
      """

    When I run `wp option list --search="sample_test_field_*" --format=csv --orderby=option_value --order=asc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_one,sample_test_field_value_one
      sample_test_field_two,sample_test_field_value_two
      """

    When I run `wp option list --search="sample_test_field_*" --format=csv --orderby=option_value --order=desc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_two,sample_test_field_value_two
      sample_test_field_one,sample_test_field_value_one
      """

  Scenario: Default list option without transient
    Given a WP install
    And I run `wp transient set wp_transient_flag wp_transient_flag`

    When I run `wp option list`
    Then STDOUT should not contain:
      """
      wp_transient_flag
      """
    And STDOUT should not contain:
      """
      _transient
      """
    And STDOUT should contain:
      """
      siteurl
      """

  Scenario: Using the `--unserialize` flag
    Given a WP install

    When I run `wp option add --format=json sample_test_field_one '{"value": 1}'`
    And I run `wp option list --search="sample_test_field_*" --format=yaml --unserialize`
    Then STDOUT should be:
      """
      ---
      - 
        option_name: sample_test_field_one
        option_value:
          value: 1
      """
