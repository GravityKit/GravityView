Feature: Manage sites in a multisite installation

  Scenario: Create a site
    Given a WP multisite install

    When I try `wp site create --slug=first --network_id=1000`
    Then STDERR should contain:
      """
      Network with id 1000 does not exist.
      """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: Create a subdomain site
    Given a WP multisite subdomain install

    When I run `wp site create --slug=first`
    Then STDOUT should not be empty

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                       |
      | 1       | http://example.com/       |
      | 2       | http://first.example.com/ |

    When I run `wp site list --format=ids`
    Then STDOUT should be:
      """
      1 2
      """

    When I run `wp site list --site_id=2 --format=ids`
    Then STDOUT should be empty

    When I run `wp --url=first.example.com option get home`
    Then STDOUT should be:
      """
      http://first.example.com
      """

  Scenario: Delete a site by id
    Given a WP multisite subdirectory install

    When I run `wp site create --slug=first --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SITE_ID}

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                       |
      | 1       | http://example.com/       |
      | 2       | http://example.com/first/ |

    When I run `wp site list --field=url`
    Then STDOUT should be:
      """
      http://example.com/
      http://example.com/first/
      """

    When I try `wp site delete 1`
    Then STDERR should be:
      """
      Error: You cannot delete the root site.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp site delete {SITE_ID} --yes`
    Then STDOUT should be:
      """
      Success: The site at 'http://example.com/first/' was deleted.
      """

    When I try the previous command again
    Then the return code should be 1

  Scenario: Filter site list
    Given a WP multisite install

    When I run `wp site create --slug=first --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SITE_ID}

    When I run `wp site list --fields=blog_id,url`
    Then STDOUT should be a table containing rows:
      | blog_id | url                       |
      | 1       | http://example.com/       |
      | 2       | http://example.com/first/ |

    When I run `wp site list --field=url --blog_id=2`
    Then STDOUT should be:
      """
      http://example.com/first/
      """

  Scenario: Delete a site by slug
    Given a WP multisite install

    When I run `wp site create --slug=first`
    Then STDOUT should be:
      """
      Success: Site 2 created: http://example.com/first/
      """

    When I run `wp site delete --slug=first --yes`
    Then STDOUT should be:
      """
      Success: The site at 'http://example.com/first/' was deleted.
      """

    When I try the previous command again
    Then the return code should be 1

  Scenario: Get site info
    Given a WP multisite install

    When I run `wp site create --slug=first --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SITE_ID}

    When I run `wp site url {SITE_ID}`
    Then STDOUT should be:
      """
      http://example.com/first/
      """

    When I run `wp site create --slug=second --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SECOND_ID}

    When I run `wp site url {SECOND_ID} {SITE_ID}`
    Then STDOUT should be:
      """
      http://example.com/second/
      http://example.com/first/
      """

  Scenario: Archive/unarchive a site
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}
    And I run `wp site create --slug=second --porcelain`
    And save STDOUT as {SECOND_SITE}

    When I run `wp site archive {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} archived.
      """

    When I run `wp site list --fields=blog_id,archived`
    Then STDOUT should be a table containing rows:
      | blog_id      | archived |
      | {FIRST_SITE} | 1        |

    When I try `wp site archive {FIRST_SITE} {SECOND_SITE}`
    Then STDERR should be:
      """
      Warning: Site {FIRST_SITE} already archived.
      """
    And STDOUT should be:
      """
      Success: Site {SECOND_SITE} archived.
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,archived`
    Then STDOUT should be a table containing rows:
      | blog_id      | archived |
      | {FIRST_SITE} | 1        |

    When I run `wp site unarchive {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} unarchived.
      """

    When I run `wp site list --fields=blog_id,archived`
    Then STDOUT should be a table containing rows:
      | blog_id      | archived |
      | {FIRST_SITE} | 0        |

    When I try `wp site archive 1`
    Then STDERR should be:
      """
      Warning: You are not allowed to change the main site.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Activate/deactivate a site
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}
    And I run `wp site create --slug=second --porcelain`
    And save STDOUT as {SECOND_SITE}

    When I run `wp site deactivate {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} deactivated.
      """

    When I run `wp site list --fields=blog_id,deleted`
    Then STDOUT should be a table containing rows:
      | blog_id      | deleted |
      | {FIRST_SITE} | 1       |

    When I try `wp site deactivate {FIRST_SITE} {SECOND_SITE}`
    Then STDERR should be:
      """
      Warning: Site {FIRST_SITE} already deactivated.
      """
    And STDOUT should be:
      """
      Success: Site {SECOND_SITE} deactivated.
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,deleted`
    Then STDOUT should be a table containing rows:
      | blog_id      | deleted |
      | {FIRST_SITE} | 1       |

    When I run `wp site activate {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} activated.
      """

    When I run `wp site list --fields=blog_id,deleted`
    Then STDOUT should be a table containing rows:
      | blog_id      | deleted |
      | {FIRST_SITE} | 0       |

    When I try `wp site deactivate 1`
    Then STDERR should be:
      """
      Warning: You are not allowed to change the main site.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Mark/remove a site from spam
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}
    And I run `wp site create --slug=second --porcelain`
    And save STDOUT as {SECOND_SITE}

    When I run `wp site spam {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} marked as spam.
      """

    When I run `wp site list --fields=blog_id,spam`
    Then STDOUT should be a table containing rows:
      | blog_id      | spam |
      | {FIRST_SITE} | 1    |

    When I try `wp site spam {FIRST_SITE} {SECOND_SITE}`
    Then STDERR should be:
      """
      Warning: Site {FIRST_SITE} already marked as spam.
      """
    And STDOUT should be:
      """
      Success: Site {SECOND_SITE} marked as spam.
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,spam`
    Then STDOUT should be a table containing rows:
      | blog_id      | spam |
      | {FIRST_SITE} | 1    |

    When I run `wp site unspam {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} removed from spam.
      """

    When I run `wp site list --fields=blog_id,spam`
    Then STDOUT should be a table containing rows:
      | blog_id      | spam |
      | {FIRST_SITE} | 0    |

    When I try `wp site spam 1`
    Then STDERR should be:
      """
      Warning: You are not allowed to change the main site.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Mark/remove a site as mature
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}
    And I run `wp site create --slug=second --porcelain`
    And save STDOUT as {SECOND_SITE}

    When I run `wp site mature {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} marked as mature.
      """

    When I run `wp site list --fields=blog_id,mature`
    Then STDOUT should be a table containing rows:
      | blog_id      | mature |
      | {FIRST_SITE} | 1    |

    When I try `wp site mature {FIRST_SITE} {SECOND_SITE}`
    Then STDERR should be:
      """
      Warning: Site {FIRST_SITE} already marked as mature.
      """
    And STDOUT should be:
      """
      Success: Site {SECOND_SITE} marked as mature.
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,mature`
    Then STDOUT should be a table containing rows:
      | blog_id      | mature |
      | {FIRST_SITE} | 1    |

    When I run `wp site unmature {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} marked as unmature.
      """

    When I run `wp site list --fields=blog_id,mature`
    Then STDOUT should be a table containing rows:
      | blog_id      | mature |
      | {FIRST_SITE} | 0    |

    When I try `wp site unmature 1`
    Then STDERR should be:
      """
      Warning: You are not allowed to change the main site.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Set/Unset a site as public
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}
    And I run `wp site create --slug=second --porcelain`
    And save STDOUT as {SECOND_SITE}

    When I run `wp site private {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} marked as private.
      """

    When I run `wp site list --fields=blog_id,public`
    Then STDOUT should be a table containing rows:
      | blog_id      | public |
      | {FIRST_SITE} | 0    |

    When I try `wp site private {FIRST_SITE} {SECOND_SITE}`
    Then STDERR should be:
      """
      Warning: Site {FIRST_SITE} already marked as private.
      """
    And STDOUT should be:
      """
      Success: Site {SECOND_SITE} marked as private.
      """
    And the return code should be 0

    When I run `wp site list --fields=blog_id,public`
    Then STDOUT should be a table containing rows:
      | blog_id      | public |
      | {FIRST_SITE} | 0    |

    When I run `wp site public {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} marked as public.
      """

    When I run `wp site list --fields=blog_id,public`
    Then STDOUT should be a table containing rows:
      | blog_id      | public |
      | {FIRST_SITE} | 1    |

    When I try `wp site private 1`
    Then STDERR should be:
      """
      Warning: You are not allowed to change the main site.
      """
    And STDOUT should be empty
    And the return code should be 0

  Scenario: Permit CLI operations against archived and suspended sites
    Given a WP multisite install
    And I run `wp site create --slug=first --porcelain`
    And save STDOUT as {FIRST_SITE}

    When I run `wp site archive {FIRST_SITE}`
    Then STDOUT should be:
      """
      Success: Site {FIRST_SITE} archived.
      """

    When I run `wp --url=example.com/first option get home`
    Then STDOUT should be:
      """
      http://example.com/first
      """

  Scenario: Create site with title containing slash
    Given a WP multisite install
    And I run `wp site create --slug=mysite --title="My\Site"`
    Then STDOUT should not be empty

    When I run `wp option get blogname --url=example.com/mysite`
    Then STDOUT should be:
      """
      My\Site
      """
