<?php

use WP_CLI\CommandWithDBObject;
use WP_CLI\Entity\Utils as EntityUtils;
use WP_CLI\Fetchers\Site as SiteFetcher;
use WP_CLI\Fetchers\User as UserFetcher;
use WP_CLI\Formatter;
use WP_CLI\Iterators\CSV as CsvIterator;
use WP_CLI\Utils;

/**
 * Manages users, along with their roles, capabilities, and meta.
 *
 * See references for [Roles and Capabilities](https://codex.wordpress.org/Roles_and_Capabilities) and [WP User class](https://codex.wordpress.org/Class_Reference/WP_User).
 *
 * ## EXAMPLES
 *
 *     # List user IDs
 *     $ wp user list --field=ID
 *     1
 *
 *     # Create a new user.
 *     $ wp user create bob bob@example.com --role=author
 *     Success: Created user 3.
 *     Password: k9**&I4vNH(&
 *
 *     # Update an existing user.
 *     $ wp user update 123 --display_name=Mary --user_pass=marypass
 *     Success: Updated user 123.
 *
 *     # Delete user 123 and reassign posts to user 567
 *     $ wp user delete 123 --reassign=567
 *     Success: Removed user 123 from http://example.com
 *
 * @package wp-cli
 */
class User_Command extends CommandWithDBObject {

	protected $obj_type   = 'user';
	protected $obj_fields = [
		'ID',
		'user_login',
		'display_name',
		'user_email',
		'user_registered',
		'roles',
	];

	private $cap_fields = [
		'name',
	];

	public function __construct() {
		$this->fetcher     = new UserFetcher();
		$this->sitefetcher = new SiteFetcher();
	}

	/**
	 * Lists users.
	 *
	 * Display WordPress users based on all arguments supported by
	 * [WP_User_Query()](https://developer.wordpress.org/reference/classes/wp_user_query/prepare_query/).
	 *
	 * ## OPTIONS
	 *
	 * [--role=<role>]
	 * : Only display users with a certain role.
	 *
	 * [--<field>=<value>]
	 * : Control output by one or more arguments of WP_User_Query().
	 *
	 * [--network]
	 * : List all users in the network for multisite.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each user.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each user:
	 *
	 * * ID
	 * * user_login
	 * * display_name
	 * * user_email
	 * * user_registered
	 * * roles
	 *
	 * These fields are optionally available:
	 *
	 * * user_pass
	 * * user_nicename
	 * * user_url
	 * * user_activation_key
	 * * user_status
	 * * spam
	 * * deleted
	 * * caps
	 * * cap_key
	 * * allcaps
	 * * filter
	 * * url
	 *
	 * ## EXAMPLES
	 *
	 *     # List user IDs
	 *     $ wp user list --field=ID
	 *     1
	 *
	 *     # List users with administrator role
	 *     $ wp user list --role=administrator --format=csv
	 *     ID,user_login,display_name,user_email,user_registered,roles
	 *     1,supervisor,supervisor,supervisor@gmail.com,"2016-06-03 04:37:00",administrator
	 *
	 *     # List users with only given fields
	 *     $ wp user list --fields=display_name,user_email --format=json
	 *     [{"display_name":"supervisor","user_email":"supervisor@gmail.com"}]
	 *
	 *     # List users ordered by the 'last_activity' meta value.
	 *     $ wp user list --meta_key=last_activity --orderby=meta_value_num
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		if ( Utils\get_flag_value( $assoc_args, 'network' ) ) {
			if ( ! is_multisite() ) {
				WP_CLI::error( 'This is not a multisite installation.' );
			}
			$assoc_args['blog_id'] = 0;
			if ( isset( $assoc_args['fields'] ) ) {
				$fields               = explode( ',', $assoc_args['fields'] );
				$assoc_args['fields'] = array_diff( $fields, [ 'roles' ] );
			} else {
				$assoc_args['fields'] = array_diff( $this->obj_fields, [ 'roles' ] );
			}
		}

		$formatter = $this->get_formatter( $assoc_args );

		if ( in_array( $formatter->format, [ 'ids', 'count' ], true ) ) {
			$assoc_args['fields'] = 'ids';
		} else {
			$assoc_args['fields'] = 'all_with_meta';
		}

		$assoc_args['count_total'] = false;
		$assoc_args                = self::process_csv_arguments_to_arrays( $assoc_args );
		$users                     = get_users( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', $users );
		} elseif ( 'count' === $formatter->format ) {
			$formatter->display_items( $users );
		} else {
			$iterator = Utils\iterator_map(
				$users,
				function ( $user ) {
					if ( ! is_object( $user ) ) {
						return $user;
					}

					$user->roles = implode( ',', $user->roles );
					$user->url   = get_author_posts_url( $user->ID, $user->user_nicename );
					return $user;
				}
			);

			$formatter->display_items( $iterator );
		}
	}

	/**
	 * Gets details about a user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, user email, or user login.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole user, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the user's fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get user
	 *     $ wp user get 12 --field=login
	 *     supervisor
	 *
	 *     # Get user and export to JSON file
	 *     $ wp user get bob --format=json > bob.json
	 */
	public function get( $args, $assoc_args ) {
		$user               = $this->fetcher->get_check( $args[0] );
		$user_data          = $user->to_array();
		$user_data['roles'] = implode( ', ', $user->roles );

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $user_data );
	}

	/**
	 * Deletes one or more users from the current site.
	 *
	 * On multisite, `wp user delete` only removes the user from the current
	 * site. Include `--network` to also remove the user from the database, but
	 * make sure to reassign their posts prior to deleting the user.
	 *
	 * ## OPTIONS
	 *
	 * <user>...
	 * : The user login, user email, or user ID of the user(s) to delete.
	 *
	 * [--network]
	 * : On multisite, delete the user from the entire network.
	 *
	 * [--reassign=<user-id>]
	 * : User ID to reassign the posts to.
	 *
	 * [--yes]
	 * : Answer yes to any confirmation prompts.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete user 123 and reassign posts to user 567
	 *     $ wp user delete 123 --reassign=567
	 *     Success: Removed user 123 from http://example.com
	 *
	 *     # Delete all contributors and reassign their posts to user 2
	 *     $ wp user delete $(wp user list --role=contributor --field=ID) --reassign=2
	 *     Success: Removed user 813 from http://example.com
	 *     Success: Removed user 578 from http://example.com
	 */
	public function delete( $args, $assoc_args ) {
		$network  = Utils\get_flag_value( $assoc_args, 'network' ) && is_multisite();
		$reassign = Utils\get_flag_value( $assoc_args, 'reassign' );

		if ( $network && $reassign ) {
			WP_CLI::error( 'Reassigning content to a different user is not supported on multisite.' );
		}

		if ( ! $reassign ) {
			WP_CLI::confirm( '--reassign parameter not passed. All associated posts will be deleted. Proceed?', $assoc_args );
		}

		$users = $this->fetcher->get_many( $args );

		parent::_delete(
			$users,
			$assoc_args,
			function ( $user ) use ( $network, $reassign ) {
				$user_id = $user->ID;

				if ( $network ) {
					$result  = wpmu_delete_user( $user_id );
					$message = "Deleted user {$user_id}.";
				} else {
					$result  = wp_delete_user( $user_id, $reassign );
					$message = "Removed user {$user_id} from " . home_url() . '.';
				}

				if ( ! $result ) {
					$message = "Failed deleting user {$user_id}.";

					if ( is_multisite() && is_super_admin( $user_id ) ) {
						$message .= ' The user is a super admin.';
					}

					return [ 'error', $message ];
				}

				return [ 'success', $message ];
			}
		);
	}

	/**
	 * Creates a new user.
	 *
	 * ## OPTIONS
	 *
	 * <user-login>
	 * : The login of the user to create.
	 *
	 * <user-email>
	 * : The email address of the user to create.
	 *
	 * [--role=<role>]
	 * : The role of the user to create. Default: default role. Possible values
	 * include 'administrator', 'editor', 'author', 'contributor', 'subscriber'.
	 *
	 * [--user_pass=<password>]
	 * : The user password. Default: randomly generated.
	 *
	 * [--user_registered=<yyyy-mm-dd-hh-ii-ss>]
	 * : The date the user registered. Default: current date.
	 *
	 * [--display_name=<name>]
	 * : The display name.
	 *
	 * [--user_nicename=<nice_name>]
	 * : A string that contains a URL-friendly name for the user. The default is the user's username.
	 *
	 * [--user_url=<url>]
	 * : A string containing the user's URL for the user's web site.
	 *
	 * [--nickname=<nickname>]
	 * : The user's nickname, defaults to the user's username.
	 *
	 * [--first_name=<first_name>]
	 * : The user's first name.
	 *
	 * [--last_name=<last_name>]
	 * : The user's last name.
	 *
	 * [--description=<description>]
	 * : A string containing content about the user.
	 *
	 * [--rich_editing=<rich_editing>]
	 * : A string for whether to enable the rich editor or not. False if not empty.
	 *
	 * [--send-email]
	 * : Send an email to the user with their new account details.
	 *
	 * [--porcelain]
	 * : Output just the new user id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create user
	 *     $ wp user create bob bob@example.com --role=author
	 *     Success: Created user 3.
	 *     Password: k9**&I4vNH(&
	 *
	 *     # Create user without showing password upon success
	 *     $ wp user create ann ann@example.com --porcelain
	 *     4
	 */
	public function create( $args, $assoc_args ) {
		$user = new stdClass();

		list( $user->user_login, $user->user_email ) = $args;

		$assoc_args = wp_slash( $assoc_args );

		if ( username_exists( $user->user_login ) ) {
			WP_CLI::error( "The '{$user->user_login}' username is already registered." );
		}

		if ( ! is_email( $user->user_email ) ) {
			WP_CLI::error( "The '{$user->user_email}' email address is invalid." );
		}

		$user->user_registered = Utils\get_flag_value(
			$assoc_args,
			'user_registered',
			date_format( date_create(), '%F %T' )
		);

		$user->display_name = Utils\get_flag_value( $assoc_args, 'display_name', false );

		$user->first_name = Utils\get_flag_value( $assoc_args, 'first_name', false );

		$user->last_name = Utils\get_flag_value( $assoc_args, 'last_name', false );

		$user->description = Utils\get_flag_value( $assoc_args, 'description', false );

		if ( isset( $assoc_args['user_pass'] ) ) {
			$user->user_pass = $assoc_args['user_pass'];
		} else {
			$user->user_pass = wp_generate_password( 24 );
			$generated_pass  = true;
		}

		$user->role = Utils\get_flag_value( $assoc_args, 'role', get_option( 'default_role' ) );
		self::validate_role( $user->role );

		if ( ! Utils\get_flag_value( $assoc_args, 'send-email' ) ) {
			add_filter( 'send_password_change_email', '__return_false' );
			add_filter( 'send_email_change_email', '__return_false' );
		}

		if ( is_multisite() ) {
			$result = wpmu_validate_user_signup( $user->user_login, $user->user_email );
			if ( is_wp_error( $result['errors'] ) && ! empty( $result['errors']->errors ) ) {
				WP_CLI::error( $result['errors'] );
			}
			$user_id = wpmu_create_user( $user->user_login, $user->user_pass, $user->user_email );
			if ( ! $user_id ) {
				WP_CLI::error( 'Unknown error creating new user.' );
			}
			$user->ID = $user_id;
			$user_id  = wp_update_user( $user );
			if ( is_wp_error( $user_id ) ) {
				WP_CLI::error( $user_id );
			}
		} else {
			$user_id = wp_insert_user( $user );
		}

		if ( ! $user_id || is_wp_error( $user_id ) ) {
			if ( ! $user_id ) {
				$user_id = 'Unknown error creating new user.';
			}
			WP_CLI::error( $user_id );
		} else {
			if ( false === $user->role ) {
				delete_user_option( $user_id, 'capabilities' );
				delete_user_option( $user_id, 'user_level' );
			}
		}

		if ( Utils\get_flag_value( $assoc_args, 'send-email' ) ) {
			self::wp_new_user_notification( $user_id, $user->user_pass );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $user_id );
		} else {
			WP_CLI::success( "Created user {$user_id}." );
			if ( isset( $generated_pass ) ) {
				WP_CLI::line( "Password: {$user->user_pass}" );
			}
		}
	}

	/**
	 * Updates an existing user.
	 *
	 * ## OPTIONS
	 *
	 * <user>...
	 * : The user login, user email or user ID of the user(s) to update.
	 *
	 * [--user_pass=<password>]
	 * : A string that contains the plain text password for the user.
	 *
	 * [--user_nicename=<nice_name>]
	 * : A string that contains a URL-friendly name for the user. The default is the user's username.
	 *
	 * [--user_url=<url>]
	 * : A string containing the user's URL for the user's web site.
	 *
	 * [--user_email=<email>]
	 * : A string containing the user's email address.
	 *
	 * [--display_name=<display_name>]
	 * : A string that will be shown on the site. Defaults to user's username.
	 *
	 * [--nickname=<nickname>]
	 * : The user's nickname, defaults to the user's username.
	 *
	 * [--first_name=<first_name>]
	 * : The user's first name.
	 *
	 * [--last_name=<last_name>]
	 * : The user's last name.
	 *
	 * [--description=<description>]
	 * : A string containing content about the user.
	 *
	 * [--rich_editing=<rich_editing>]
	 * : A string for whether to enable the rich editor or not. False if not empty.
	 *
	 * [--user_registered=<yyyy-mm-dd-hh-ii-ss>]
	 * : The date the user registered.
	 *
	 * [--role=<role>]
	 * : A string used to set the user's role.
	 *
	 * --<field>=<value>
	 * : One or more fields to update. For accepted fields, see wp_update_user().
	 *
	 * [--skip-email]
	 * : Don't send an email notification to the user.
	 *
	 * ## EXAMPLES
	 *
	 *     # Update user
	 *     $ wp user update 123 --display_name=Mary --user_pass=marypass
	 *     Success: Updated user 123.
	 */
	public function update( $args, $assoc_args ) {
		if ( isset( $assoc_args['user_login'] ) ) {
			WP_CLI::warning( "User logins can't be changed." );
			unset( $assoc_args['user_login'] );
		}

		$user_ids = [];
		foreach ( $this->fetcher->get_many( $args ) as $user ) {
			$user_ids[] = $user->ID;
		}

		$skip_email = Utils\get_flag_value( $assoc_args, 'skip-email' );
		if ( $skip_email ) {
			add_filter( 'send_email_change_email', '__return_false' );
			add_filter( 'send_password_change_email', '__return_false' );
		}

		$assoc_args = wp_slash( $assoc_args );
		parent::_update( $user_ids, $assoc_args, 'wp_update_user' );

		if ( $skip_email ) {
			remove_filter( 'send_email_change_email', '__return_false' );
			remove_filter( 'send_password_change_email', '__return_false' );
		}
	}

	/**
	 * Generates some users.
	 *
	 * Creates a specified number of new users with dummy data.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many users to generate?
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--role=<role>]
	 * : The role of the generated users. Default: default role from WP
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: progress
	 * options:
	 *   - progress
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Add meta to every generated users.
	 *     $ wp user generate --format=ids --count=3 | xargs -d ' ' -I % wp user meta add % foo bar
	 *     Success: Added custom field.
	 *     Success: Added custom field.
	 *     Success: Added custom field.
	 */
	public function generate( $args, $assoc_args ) {
		global $blog_id;

		$defaults   = [
			'count' => 100,
			'role'  => get_option( 'default_role' ),
		];
		$assoc_args = array_merge( $defaults, $assoc_args );

		$role = $assoc_args['role'];

		if ( ! empty( $role ) ) {
			self::validate_role( $role );
		}

		$user_count = count_users();
		$total      = $user_count['total_users'];
		$limit      = $assoc_args['count'] + $total;

		$format = Utils\get_flag_value( $assoc_args, 'format', 'progress' );

		$notify = false;
		if ( 'progress' === $format ) {
			$notify = Utils\make_progress_bar( 'Generating users', $assoc_args['count'] );
		}

		for ( $index = $total; $index < $limit; $index++ ) {
			$login = "user_{$blog_id}_{$index}";
			$name  = "User {$index}";

			$user_id = wp_insert_user(
				[
					'user_login'   => $login,
					'user_pass'    => $login,
					'nickname'     => $name,
					'display_name' => $name,
					'role'         => $role,
				]
			);

			if ( false === $role ) {
				delete_user_option( $user_id, 'capabilities' );
				delete_user_option( $user_id, 'user_level' );
			}

			if ( 'progress' === $format ) {
				$notify->tick();
			} elseif ( 'ids' === $format ) {
				echo $user_id;
				if ( $index < $limit - 1 ) {
					echo ' ';
				}
			}
		}

		if ( 'progress' === $format ) {
			$notify->finish();
		}
	}

	/**
	 * Sets the user role.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, user email, or user login.
	 *
	 * [<role>]
	 * : Make the user have the specified role. If not passed, the default role is
	 * used.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp user set-role 12 author
	 *     Success: Added johndoe (12) to http://example.com as author.
	 *
	 * @subcommand set-role
	 */
	public function set_role( $args, $assoc_args ) {
		$user = $this->fetcher->get_check( $args[0] );

		$role = Utils\get_flag_value( $args, 1, get_option( 'default_role' ) );

		self::validate_role( $role );

		// Multisite
		if ( function_exists( 'add_user_to_blog' ) ) {
			add_user_to_blog( get_current_blog_id(), $user->ID, $role );
		} else {
			$user->set_role( $role );
		}

		WP_CLI::success( "Added {$user->user_login} ({$user->ID}) to " . site_url() . " as {$role}." );
	}

	/**
	 * Adds a role for a user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, user email, or user login.
	 *
	 * <role>
	 * : Add the specified role to the user.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp user add-role 12 author
	 *     Success: Added 'author' role for johndoe (12).
	 *
	 * @subcommand add-role
	 */
	public function add_role( $args, $assoc_args ) {
		$user = $this->fetcher->get_check( $args[0] );

		$role = $args[1];

		self::validate_role( $role );

		$user->add_role( $role );

		WP_CLI::success( "Added '{$role}' role for {$user->user_login} ({$user->ID})." );
	}

	/**
	 * Removes a user's role.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, user email, or user login.
	 *
	 * [<role>]
	 * : A specific role to remove.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp user remove-role 12 author
	 *     Success: Removed 'author' role for johndoe (12).
	 *
	 * @subcommand remove-role
	 */
	public function remove_role( $args, $assoc_args ) {
		$user = $this->fetcher->get_check( $args[0] );

		if ( isset( $args[1] ) ) {
			$role = $args[1];

			self::validate_role( $role );

			$user->remove_role( $role );

			WP_CLI::success( "Removed '{$role}' role for {$user->user_login} ({$user->ID})." );
		} else {
			// Multisite
			if ( function_exists( 'remove_user_from_blog' ) ) {
				remove_user_from_blog( $user->ID, get_current_blog_id() );
			} else {
				$user->remove_all_caps();
			}

			WP_CLI::success( "Removed {$user->user_login} ({$user->ID}) from " . site_url() . '.' );
		}
	}

	/**
	 * Adds a capability to a user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, user email, or user login.
	 *
	 * <cap>
	 * : The capability to add.
	 *
	 * ## EXAMPLES
	 *
	 *     # Add a capability for a user
	 *     $ wp user add-cap john create_premium_item
	 *     Success: Added 'create_premium_item' capability for john (16).
	 *
	 *     # Add a capability for a user
	 *     $ wp user add-cap 15 edit_product
	 *     Success: Added 'edit_product' capability for johndoe (15).
	 *
	 * @subcommand add-cap
	 */
	public function add_cap( $args, $assoc_args ) {
		$user = $this->fetcher->get_check( $args[0] );
		if ( $user ) {
			$cap = $args[1];
			$user->add_cap( $cap );

			WP_CLI::success( "Added '{$cap}' capability for {$user->user_login} ({$user->ID})." );
		}
	}

	/**
	 * Removes a user's capability.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, user email, or user login.
	 *
	 * <cap>
	 * : The capability to be removed.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp user remove-cap 11 publish_newsletters
	 *     Success: Removed 'publish_newsletters' cap for supervisor (11).
	 *
	 *     $ wp user remove-cap 11 publish_posts
	 *     Error: The 'publish_posts' cap for supervisor (11) is inherited from a role.
	 *
	 *     $ wp user remove-cap 11 nonexistent_cap
	 *     Error: No such 'nonexistent_cap' cap for supervisor (11).
	 *
	 * @subcommand remove-cap
	 */
	public function remove_cap( $args, $assoc_args ) {
		$user = $this->fetcher->get_check( $args[0] );
		if ( $user ) {
			$cap = $args[1];
			if ( ! isset( $user->caps[ $cap ] ) ) {
				if ( isset( $user->allcaps[ $cap ] ) ) {
					WP_CLI::error( "The '{$cap}' cap for {$user->user_login} ({$user->ID}) is inherited from a role." );
				}
				WP_CLI::error( "No such '{$cap}' cap for {$user->user_login} ({$user->ID})." );
			}
			$user->remove_cap( $cap );

			WP_CLI::success( "Removed '{$cap}' cap for {$user->user_login} ({$user->ID})." );
		}
	}

	/**
	 * Lists all capabilities for a user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : User ID, user email, or login.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: list
	 * options:
	 *   - list
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp user list-caps 21
	 *     edit_product
	 *     create_premium_item
	 *
	 * @subcommand list-caps
	 */
	public function list_caps( $args, $assoc_args ) {
		$user = $this->fetcher->get_check( $args[0] );

		if ( $user ) {
			$user->get_role_caps();

			$user_caps_list = $user->allcaps;

			$active_user_cap_list = [];

			foreach ( $user_caps_list as $cap => $active ) {
				if ( $active ) {
					$active_user_cap_list[] = $cap;
				}
			}

			if ( 'list' === $assoc_args['format'] ) {
				foreach ( $active_user_cap_list as $cap ) {
					WP_CLI::line( $cap );
				}
			} else {
				$output_caps = [];
				foreach ( $active_user_cap_list as $cap ) {
					$output_cap = new stdClass();

					$output_cap->name = $cap;

					$output_caps[] = $output_cap;
				}
				$formatter = new Formatter( $assoc_args, $this->cap_fields );
				$formatter->display_items( $output_caps );
			}
		}
	}

	/**
	 * Imports users from a CSV file.
	 *
	 * If the user already exists (matching the email address or login), then
	 * the user is updated unless the `--skip-update` flag is used.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : The local or remote CSV file of users to import. If '-', then reads from STDIN.
	 *
	 * [--send-email]
	 * : Send an email to new users with their account details.
	 *
	 * [--skip-update]
	 * : Don't update users that already exist.
	 *
	 * ## EXAMPLES
	 *
	 *     # Import users from local CSV file
	 *     $ wp user import-csv /path/to/users.csv
	 *     Success: bobjones created
	 *     Success: newuser1 created
	 *     Success: existinguser created
	 *
	 *     # Import users from remote CSV file
	 *     $ wp user import-csv http://example.com/users.csv
	 *
	 *     Sample users.csv file:
	 *
	 *     user_login,user_email,display_name,role
	 *     bobjones,bobjones@example.com,Bob Jones,contributor
	 *     newuser1,newuser1@example.com,New User,author
	 *     existinguser,existinguser@example.com,Existing User,administrator
	 *
	 * @subcommand import-csv
	 */
	public function import_csv( $args, $assoc_args ) {

		$blog_users = get_users();

		$filename = $args[0];

		if ( 0 === stripos( $filename, 'http://' ) || 0 === stripos( $filename, 'https://' ) ) {
			$response      = wp_remote_head( $filename );
			$response_code = (string) wp_remote_retrieve_response_code( $response );
			if ( in_array( (int) $response_code[0], [ 4, 5 ], true ) ) {
				WP_CLI::error( "Couldn't access remote CSV file (HTTP {$response_code} response)." );
			}
		} elseif ( '-' === $filename ) {
			if ( ! EntityUtils::has_stdin() ) {
				WP_CLI::error( 'Unable to read content from STDIN.' );
			}
		} elseif ( ! file_exists( $filename ) ) {
			WP_CLI::error( "Missing file: {$filename}" );
		}

		// Don't send core's emails during the creation / update process
		add_filter( 'send_password_change_email', '__return_false' );
		add_filter( 'send_email_change_email', '__return_false' );

		if ( '-' === $filename ) {
			$file_object = new NoRewindIterator( new SplFileObject( 'php://stdin' ) );
			$file_object->setFlags( SplFileObject::READ_CSV );
			$csv_data = [];
			$indexes  = [];
			foreach ( $file_object as $line ) {
				if ( empty( $line[0] ) ) {
					continue;
				}

				if ( empty( $indexes ) ) {
					$indexes = $line;
					continue;
				}

				foreach ( $indexes as $n => $key ) {
					$data[ $key ] = $line[ $n ];
				}

				$csv_data[] = $data;
			}
		} else {
			$csv_data = new CsvIterator( $filename );
		}

		foreach ( $csv_data as $new_user ) {
			$defaults = [
				'role'            => get_option( 'default_role' ),
				'user_pass'       => wp_generate_password(),
				'user_registered' => date_format( date_create(), '%F %T' ),
				'display_name'    => false,
			];
			$new_user = array_merge( $defaults, $new_user );

			$secondary_roles = [];
			if ( ! empty( $new_user['roles'] ) ) {
				$roles        = array_map( 'trim', explode( ',', $new_user['roles'] ) );
				$invalid_role = false;
				foreach ( $roles as $role ) {
					if ( null === get_role( $role ) ) {
						WP_CLI::warning( "{$new_user['user_login']} has an invalid role." );
						$invalid_role = true;
						break;
					}
				}
				if ( $invalid_role ) {
					continue;
				}
				$new_user['role'] = array_shift( $roles );
				$secondary_roles  = $roles;
			} elseif ( 'none' === $new_user['role'] ) {
				$new_user['role'] = false;
			} elseif ( null === get_role( $new_user['role'] ) ) {
				WP_CLI::warning( "{$new_user['user_login']} has an invalid role." );
				continue;
			}

			// User already exists and we just need to add them to the site if they aren't already there
			$existing_user = get_user_by( 'email', $new_user['user_email'] );

			if ( ! $existing_user ) {
				$existing_user = get_user_by( 'login', $new_user['user_login'] );
			}

			if ( $existing_user && Utils\get_flag_value( $assoc_args, 'skip-update' ) ) {

				WP_CLI::log( "{$existing_user->user_login} exists and has been skipped." );
				continue;

			}

			if ( $existing_user ) {
				$new_user['ID'] = $existing_user->ID;
				$user_id        = wp_update_user( $new_user );

				if ( ! in_array( $existing_user->user_login, wp_list_pluck( $blog_users, 'user_login' ), true ) && is_multisite() && $new_user['role'] ) {
					add_user_to_blog( get_current_blog_id(), $existing_user->ID, $new_user['role'] );
					WP_CLI::log( "{$existing_user->user_login} added as {$new_user['role']}." );
				}

				// Create the user
			} else {
				unset( $new_user['ID'] ); // Unset else it will just return the ID

				if ( is_multisite() ) {
					$result = wpmu_validate_user_signup( $new_user['user_login'], $new_user['user_email'] );
					if ( is_wp_error( $result['errors'] ) && ! empty( $result['errors']->errors ) ) {
						WP_CLI::warning( $result['errors'] );
						continue;
					}
					$user_id = wpmu_create_user( $new_user['user_login'], $new_user['user_pass'], $new_user['user_email'] );
					if ( ! $user_id ) {
						WP_CLI::warning( 'Unknown error creating new user.' );
						continue;
					}
					$new_user['ID'] = $user_id;
					$user_id        = wp_update_user( $new_user );
					if ( is_wp_error( $user_id ) ) {
						WP_CLI::warning( $user_id );
						continue;
					}
				} else {
					$user_id = wp_insert_user( $new_user );
				}

				if ( Utils\get_flag_value( $assoc_args, 'send-email' ) ) {
					self::wp_new_user_notification( $user_id, $new_user['user_pass'] );
				}
			}

			if ( is_wp_error( $user_id ) ) {
				WP_CLI::warning( $user_id );
				continue;

			}

			if ( false === $new_user['role'] ) {
				delete_user_option( $user_id, 'capabilities' );
				delete_user_option( $user_id, 'user_level' );
			}

			$user = get_user_by( 'id', $user_id );
			foreach ( $secondary_roles as $secondary_role ) {
				$user->add_role( $secondary_role );
			}

			if ( ! empty( $existing_user ) ) {
				WP_CLI::success( $new_user['user_login'] . ' updated.' );
			} else {
				WP_CLI::success( $new_user['user_login'] . ' created.' );
			}
		}
	}

	/**
	 * Resets the password for one or more users.
	 *
	 * ## OPTIONS
	 *
	 * <user>...
	 * : one or more user logins or IDs.
	 *
	 * [--skip-email]
	 * : Don't send an email notification to the affected user(s).
	 *
	 * ## EXAMPLES
	 *
	 *     # Reset the password for two users and send them the change email.
	 *     $ wp user reset-password admin editor
	 *     Reset password for admin.
	 *     Reset password for editor.
	 *     Success: Passwords reset for 2 users.
	 *
	 * @subcommand reset-password
	 */
	public function reset_password( $args, $assoc_args ) {
		$skip_email = Utils\get_flag_value( $assoc_args, 'skip-email' );
		if ( $skip_email ) {
			add_filter( 'send_password_change_email', '__return_false' );
		}
		$fetcher = new UserFetcher();
		$users   = $fetcher->get_many( $args );
		foreach ( $users as $user ) {
			wp_update_user(
				[
					'ID'        => $user->ID,
					'user_pass' => wp_generate_password(),
				]
			);
			WP_CLI::log( "Reset password for {$user->user_login}." );
		}
		if ( $skip_email ) {
			remove_filter( 'send_password_change_email', '__return_false' );
		}

		$reset_user_count = count( $users );
		if ( 1 === $reset_user_count ) {
			WP_CLI::success( "Password reset for {$reset_user_count} user." );
		} elseif ( $reset_user_count > 1 ) {
			WP_CLI::success( "Passwords reset for {$reset_user_count} users." );
		} else {
			WP_CLI::error( 'No user found to reset password.' );
		}
	}

	/**
	 * Checks whether the role is valid
	 *
	 * @param string
	 */
	private static function validate_role( $role ) {

		if ( ! empty( $role ) && null === get_role( $role ) ) {
			WP_CLI::error( "Role doesn't exist: {$role}" );
		}

	}

	/**
	 * Accommodates three different behaviors for wp_new_user_notification()
	 * - 4.3.1 and above: expect second argument to be deprecated
	 * - 4.3: Second argument was repurposed as $notify
	 * - Below 4.3: Send the password in the notification
	 *
	 * @param string $user_id
	 * @param string $password
	 */
	public static function wp_new_user_notification( $user_id, $password ) {
		if ( Utils\wp_version_compare( '4.3.1', '>=' ) ) {
			wp_new_user_notification( $user_id, null, 'both' );
		} elseif ( Utils\wp_version_compare( '4.3', '>=' ) ) {
			// phpcs:ignore WordPress.WP.DeprecatedParameters.Wp_new_user_notificationParam2Found -- Only called in valid conditions.
			wp_new_user_notification( $user_id, 'both' );
		} else {
			// phpcs:ignore WordPress.WP.DeprecatedParameters.Wp_new_user_notificationParam2Found -- Only called in valid conditions.
			wp_new_user_notification( $user_id, $password );
		}
	}

	/**
	 * Marks one or more users as spam.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of users to mark as spam.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp user spam 123
	 *     User 123 marked as spam.
	 *     Success: Spamed 1 of 1 users.
	 */
	public function spam( $args ) {
		$this->update_msuser_status( $args, 'spam', '1' );
	}

	/**
	 * Removes one or more users from spam.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more IDs of users to remove from spam.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp user unspam 123
	 *     User 123 removed from spam.
	 *     Success: Unspamed 1 of 1 users.
	 */
	public function unspam( $args ) {
		$this->update_msuser_status( $args, 'spam', '0' );
	}

	/**
	 * Common command for updating user data.
	 */
	private function update_msuser_status( $user_ids, $pref, $value ) {

		// If site is not multisite, then stop execution.
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		if ( 'spam' === $pref ) {
			$action = (int) $value ? 'marked as spam' : 'removed from spam';
			$verb   = (int) $value ? 'spam' : 'unspam';
		}

		$successes = 0;
		$errors    = 0;
		$users     = $this->fetcher->get_many( $user_ids );
		if ( count( $users ) < count( $user_ids ) ) {
			$errors = count( $user_ids ) - count( $users );
		}

		foreach ( $user_ids as $user_id ) {

			$user = get_userdata( $user_id );

			// If no user found, then show warning.
			if ( empty( $user ) ) {
				WP_CLI::warning( "User {$user_id} doesn't exist." );
				continue;
			}

			// Super admin should not be marked as spam.
			if ( is_super_admin( $user->ID ) ) {
				WP_CLI::warning( "User cannot be modified. The user {$user->ID} is a network administrator." );
				continue;
			}

			// Skip if user is already marked as spam and show warning.
			if ( $value === $user->spam ) {
				WP_CLI::warning( "User {$user_id} already {$action}." );
				continue;
			}

			// Make that user's blog as spam too.
			$blogs = (array) get_blogs_of_user( $user_id, true );
			foreach ( $blogs as $details ) {
				$site = $this->sitefetcher->get_check( $details->site_id );

				// Main blog shouldn't a spam !
				if ( $details->userblog_id !== $site->blog_id ) {
					update_blog_status( $details->userblog_id, $pref, $value );
				}
			}

			if ( Utils\wp_version_compare( '5.3', '<' ) ) {
				// phpcs:ignore WordPress.WP.DeprecatedFunctions.update_user_statusFound -- Fallback for older versions.
				update_user_status( $user_id, $pref, $value );
			} else {
				wp_update_user(
					[
						'ID'  => $user_id,
						$pref => $value,
					]
				);
			}

			WP_CLI::log( "User {$user_id} {$action}." );
			$successes++;
		}

		Utils\report_batch_operation_results( 'user', $verb, count( $user_ids ), $successes, $errors );
	}

	/**
	 * Checks if a user's password is valid or not.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : The user login, user email or user ID of the user to check credentials for.
	 *
	 * <user_pass>
	 * : A string that contains the plain text password for the user.
	 *
	 * [--escape-chars]
	 * : Escape password with `wp_slash()` to mimic the same behavior as `wp-login.php`.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check whether given credentials are valid; exit status 0 if valid, otherwise 1
	 *     $ wp user check-password admin adminpass
	 *     $ echo $?
	 *     1
	 *
	 *     # Bash script for checking whether given credentials are valid or not
	 *     if ! $(wp user check-password admin adminpass); then
	 *      notify-send "Invalid Credentials";
	 *     fi
	 *
	 * @subcommand check-password
	 */
	public function check_password( $args, $assoc_args ) {
		$escape_chars = Utils\get_flag_value( $assoc_args, 'escape-chars', false );

		if ( ! $escape_chars && wp_slash( wp_unslash( $args[1] ) ) !== $args[1] ) {
			WP_CLI::warning( 'Password contains characters that need to be escaped. Please escape them manually or use the `--escape-chars` option.' );
		}

		$user      = $this->fetcher->get_check( $args[0] );
		$user_pass = $escape_chars ? wp_slash( $args[1] ) : $args[1];

		if ( wp_check_password( $user_pass, $user->data->user_pass, $user->ID ) ) {
			WP_CLI::halt( 0 );
		} else {
			WP_CLI::halt( 1 );
		}
	}

}
