Feature: Manage widgets in WordPress sidebar

  Background:
    Given a WP install
    And I try `wp theme delete twentytwelve --force`
    And I run `wp theme install twentytwelve --activate`
    And I try `wp widget reset --all`
    And I try `wp widget delete wp_inactive_widgets $(wp widget list wp_inactive_widgets --format=ids)`
    And I run `wp widget add text sidebar-1 --title="Text 1"`
    And I run `wp widget add archives sidebar-1 --title="Archives"`
    And I run `wp widget add calendar sidebar-1 --title="Calendar"`
    And I run `wp widget add search sidebar-1 --title="Quick Search"`
    And I run `wp widget add text sidebar-1 --title="Text 2"`
    And I run `wp widget add search sidebar-2 --title="Quick Search"`
    And I run `wp widget add text sidebar-3 --title="Text"`


  Scenario: Widget CRUD
    When I run `wp widget list sidebar-1 --fields=name,id,position`
    Then STDOUT should be a table containing rows:
      | name     | id         | position |
      | text     | text-1     | 1        |
      | archives | archives-1 | 2        |
      | calendar | calendar-1 | 3        |
      | search   | search-1   | 4        |
      | text     | text-2     | 5        |

    When I run `wp widget move search-1 --position=2`
    Then STDOUT should not be empty

    When I run `wp widget list sidebar-1 --fields=name,id,position`
    Then STDOUT should be a table containing rows:
      | name     | id         | position |
      | text     | text-1     | 1        |
      | search   | search-1   | 2        |
      | archives | archives-1 | 3        |
      | calendar | calendar-1 | 4        |
      | text     | text-2     | 5        |

    When I run `wp widget move text-1 --sidebar-id=wp_inactive_widgets`
    Then STDOUT should not be empty

    When I run `wp widget deactivate calendar-1`
    Then STDOUT should be:
      """
      Success: Deactivated 1 of 1 widgets.
      """
    And STDERR should be empty
    And the return code should be 0

    When I run `wp widget list sidebar-1 --fields=name,id,position`
    Then STDOUT should be a table containing rows:
      | name     | id         | position |
      | search   | search-1   | 1        |
      | archives | archives-1 | 2        |
      | text     | text-2     | 3        |

    When I run `wp widget list wp_inactive_widgets --fields=name,id,position`
    Then STDOUT should be a table containing rows:
      | name     | id         | position |
      | text     | text-1     | 1        |
      | calendar | calendar-1 | 2        |

    When I run `wp widget delete archives-1 text-1`
    Then STDOUT should be:
      """
      Success: Deleted 2 of 2 widgets.
      """
    And STDERR should be empty
    And the return code should be 0

    When I run `wp widget list sidebar-1 --fields=name,id,position`
    Then STDOUT should be a table containing rows:
      | name     | id         | position |
      | search   | search-1   | 1        |
      | text     | text-2     | 2        |

    When I run `wp widget add archives sidebar-1 2 --title="Archives"`
    Then STDOUT should not be empty

    When I run `wp widget list sidebar-1 --fields=name,id,position`
    Then STDOUT should be a table containing rows:
      | name     | id         | position |
      | search   | search-1   | 1        |
      | archives | archives-1 | 2        |
      | text     | text-2     | 3        |

    When I run `wp widget list sidebar-1 --format=ids`
    Then STDOUT should be:
      """
      search-1 archives-1 text-2
      """

    When I run `wp widget list sidebar-1 --fields=name,position,options`
    Then STDOUT should be a table containing rows:
      | name     | position | options                                     |
      | archives | 2        | {"title":"Archives","count":0,"dropdown":0} |

    When I run `wp widget update archives-1 --title="New Archives"`
    Then STDOUT should not be empty

    When I run `wp widget list sidebar-1 --fields=name,position,options`
    Then STDOUT should be a table containing rows:
      | name     | position | options                                         |
      | archives | 2        | {"title":"New Archives","count":0,"dropdown":0} |

  Scenario: Validate sidebar widgets
    When I try `wp widget update calendar-999`
    Then STDERR should be:
      """
      Error: Widget doesn't exist.
      """
    And the return code should be 1

    When I try `wp widget move calendar-999`
    Then STDERR should be:
      """
      Error: Widget doesn't exist.
      """
    And the return code should be 1

  Scenario: Return code is 0 when all widgets exist, deactivation
    When I run `wp widget deactivate text-1`
    Then STDOUT should be:
      """
      Success: Deactivated 1 of 1 widgets.
      """
    And STDERR should be empty
    And the return code should be 0

    When I run `wp widget deactivate archives-1 calendar-1`
    Then STDOUT should be:
      """
      Success: Deactivated 2 of 2 widgets.
      """
    And STDERR should be empty
    And the return code should be 0

  Scenario: Return code is 0 when all widgets exist, deletion
    When I run `wp widget delete text-1`
    Then STDOUT should be:
      """
      Success: Deleted 1 of 1 widgets.
      """
    And STDERR should be empty
    And the return code should be 0

    When I run `wp widget delete archives-1 calendar-1`
    Then STDOUT should be:
      """
      Success: Deleted 2 of 2 widgets.
      """
    And STDERR should be empty
    And the return code should be 0

  Scenario: Return code is 1 when 1 or more widgets doesn't exist, deactivation
    When I try `wp widget deactivate calendar-999`
    Then STDERR should be:
      """
      Warning: Widget 'calendar-999' doesn't exist.
      Error: No widgets deactivated.
      """
    And the return code should be 1

    When I try `wp widget deactivate text-1 calendar-999`
    Then STDERR should be:
      """
      Warning: Widget 'calendar-999' doesn't exist.
      Error: Only deactivated 1 of 2 widgets.
      """
    And the return code should be 1

  Scenario: Return code is 1 when 1 or more widgets doesn't exist, deletion
    When I try `wp widget delete calendar-999`
    Then STDERR should be:
      """
      Warning: Widget 'calendar-999' doesn't exist.
      Error: No widgets deleted.
      """
    And the return code should be 1

    When I try `wp widget delete text-1 calendar-999`
    Then STDERR should be:
      """
      Warning: Widget 'calendar-999' doesn't exist.
      Error: Only deleted 1 of 2 widgets.
      """
    And the return code should be 1
