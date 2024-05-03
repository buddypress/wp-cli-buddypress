<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Signups.
 *
 * ## EXAMPLES
 *
 *     # Add a signup.
 *     $ wp bp signup create --user-login=test_user --user-email=teste@site.com
 *     Success: Successfully added new user signup (ID #345).
 *
 *     # Activate a signup.
 *     $ wp bp signup activate ee48ec319fef3nn4
 *     Success: Signup activated, new user (ID #545).
 *
 * @since 1.5.0
 */
class Signup extends BuddyPressCommand {

	/**
	 * Signup object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = [
		'id',
		'user_name',
		'user_login',
		'user_email',
		'registered',
		'meta',
		'activation_key',
		'count_sent',
	];

	/**
	 * Dependency check for this CLI command.
	 */
	public static function check_dependencies() {
		parent::check_dependencies();

		if ( ! bp_get_signup_allowed() ) {
			WP_CLI::error( 'The BuddyPress signup feature needs to be allowed.' );
		}

		// Fixes a bug in case the signups tables were not properly created.
		require_once buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-schema.php';
		require_once buddypress()->plugin_dir . 'bp-core/bp-core-update.php';

		bp_core_maybe_install_signups();
	}

	/**
	 * Add a signup.
	 *
	 * ## OPTIONS
	 *
	 * [--user-login=<user-login>]
	 * : User login for the signup.
	 *
	 * [--user-email=<user-email>]
	 * : User email for the signup.
	 *
	 * [--activation-key=<activation-key>]
	 * : Activation key for the signup. If none is provided, a random one will be used.
	 *
	 * [--silent]
	 * : Whether to silent the signup creation.
	 *
	 * [--porcelain]
	 * : Output only the new signup id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Add a signup.
	 *     $ wp bp signup create --user-login=test_user --user-email=teste@site.com
	 *     Success: Successfully added new user signup (ID #345).
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = wp_parse_args(
			$assoc_args,
			[
				'user-login'     => '',
				'user-email'     => '',
				'activation-key' => wp_generate_password( 32, false ),
			]
		);

		$signup_args = [ 'meta' => [] ];

		$user_login = $r['user-login'];
		if ( ! empty( $user_login ) ) {
			$user_login = preg_replace( '/\s+/', '', sanitize_user( $user_login, true ) );
		}

		$user_email = $r['user-email'];
		if ( ! empty( $user_email ) ) {
			$user_email = sanitize_email( $user_email );
		}

		$signup_args['user_login']     = $user_login;
		$signup_args['user_email']     = $user_email;
		$signup_args['activation_key'] = $r['activation-key'];

		$signup_id = \BP_Signup::add( $signup_args );

		// Silent it.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! $signup_id ) {
			WP_CLI::error( 'Could not add user signup.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( $signup_id );
		} else {
			WP_CLI::success( sprintf( 'Successfully added new user signup (ID #%d).', $signup_id ) );
		}
	}

	/**
	 * Get a signup.
	 *
	 * ## OPTIONS
	 *
	 * <signup-id>
	 * : Identifier for the signup. Can be a signup ID, an email address, or a user_login.
	 *
	 * [--match-field=<match-field>]
	 * : Field to match the signup-id to. Use if there is ambiguity between, eg, signup ID and user_login.
	 * ---
	 * options:
	 *   - signup_id
	 *   - user_email
	 *   - user_login
	 * ---
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific signup fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get a signup.
	 *     $ wp bp signup get 35 --fields=id,user_login,user_name,count_sent
	 *     +------------+------------+
	 *     | Field      | Value      |
	 *     +------------+------------+
	 *     | id         | 35         |
	 *     | user_login | user897616 |
	 *     | user_name  | Test user  |
	 *     | count_sent | 4          |
	 *     +------------+------------+
	 */
	public function get( $args, $assoc_args ) {
		$signup = $this->get_signup_by_identifier( $args[0], $assoc_args );

		$this->get_formatter( $assoc_args )->display_item( $signup );
	}

	/**
	 * Delete a signup.
	 *
	 * ## OPTIONS
	 *
	 * <signup-id>...
	 * : ID or IDs of signup to delete.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a signup.
	 *     $ wp bp signup delete 520 --yes
	 *     Success: Signup deleted 54565.
	 *
	 *     # Delete multiple signups.
	 *     $ wp bp signup delete 55654 54565 --yes
	 *     Success: Signup deleted 55654.
	 *     Success: Signup deleted 54565.
	 *
	 * @alias remove
	 * @alias trash
	 */
	public function delete( $args, $assoc_args ) {
		$signup_ids = wp_parse_id_list( $args );

		if ( count( $signup_ids ) > 1 ) {
			WP_CLI::confirm( 'Are you sure you want to delete these signups?', $assoc_args );
		} else {
			WP_CLI::confirm( 'Are you sure you want to delete this signup?', $assoc_args );
		}

		parent::_delete(
			$signup_ids,
			$assoc_args,
			function ( $signup_id ) {
				if ( \BP_Signup::delete( [ $signup_id ] ) ) {
					return [ 'success', sprintf( 'Signup deleted %d.', $signup_id ) ];
				}

				return [ 'error', sprintf( 'Could not delete signup %d.', $signup_id ) ];
			}
		);
	}

	/**
	 * Activate a signup.
	 *
	 * ## OPTIONS
	 *
	 * <signup-id>
	 * : Identifier for the signup. Can be a signup ID, an email address, or a user_login.
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate a signup.
	 *     $ wp bp signup activate ee48ec319fef3nn4
	 *     Success: Signup activated, new user (ID #545).
	 */
	public function activate( $args, $assoc_args ) {
		$signup  = $this->get_signup_by_identifier( $args[0], $assoc_args );
		$user_id = bp_core_activate_signup( $signup->activation_key );

		if ( $user_id ) {
			WP_CLI::success( sprintf( 'Signup activated, new user (ID #%d).', $user_id ) );
		} else {
			WP_CLI::error( 'Signup not activated.' );
		}
	}

	/**
	 * Generate random signups.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many signups to generate.
	 * ---
	 * default: 100
	 * ---
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
	 *     # Generate 50 random signups.
	 *     $ wp bp signup generate --count=50
	 *     Generating signups  100% [======================] 0:00 / 0:00
	 *
	 *     # Generate 5 random signups and return their IDs.
	 *     $ wp bp signup generate --count=5 --format=ids
	 *     70 71 72 73 74
	 */
	public function generate( $args, $assoc_args ) {
		// Use the email API to get a valid "from" domain.
		$email_domain = new \BP_Email( '' );
		$email_domain = $email_domain->get_from()->get_address();
		$random_login = wp_generate_password( 12, false ); // Generate random user login.

		$this->generate_callback(
			'Generating signups',
			$assoc_args,
			function ( $assoc_args, $format ) use ( $random_login, $email_domain ) {
				$params = [
					'user-login' => $random_login,
					'user-email' => $random_login . substr( $email_domain, strpos( $email_domain, '@' ) ),
				];

				if ( 'ids' === $format ) {
					$params['porcelain'] = true;
				} else {
					$params['silent'] = true;
				}

				return $this->create( [], $params );
			}
		);
	}

	/**
	 * Resend activation e-mail to a newly registered user.
	 *
	 * ## OPTIONS
	 *
	 * <signup-id>
	 * : Identifier for the signup. Can be a signup ID, an email address, or a user_login.
	 *
	 * ## EXAMPLES
	 *
	 *     # Resend activation e-mail to a newly registered user.
	 *     $ wp bp signup resend test@example.com
	 *     Success: Email sent successfully.
	 *
	 * @alias send
	 */
	public function resend( $args, $assoc_args ) {
		$signup = $this->get_signup_by_identifier( $args[0], $assoc_args );
		$send   = \BP_Signup::resend( [ $signup->signup_id ] );

		// Add feedback message.
		if ( empty( $send['errors'] ) ) {
			WP_CLI::success( 'Email sent successfully.' );
		} else {
			WP_CLI::error( 'This account is already activated.' );
		}
	}

	/**
	 * Get a list of signups.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See \BP_Signup::get()
	 *
	 * [--fields=<fields>]
	 * : Fields to display.
	 *
	 * [--count=<number>]
	 * : How many signups to list.
	 * ---
	 * default: 50
	 * ---
	 *
	 * [--format=<value>]
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
	 * ## EXAMPLES
	 *
	 *     # List signups and get the IDs.
	 *     $ wp bp signup list --format=ids
	 *     70 71 72 73 74
	 *
	 *     # List 100 signups and return the count.
	 *     $ wp bp signup list --count=100 --format=count
	 *     100
	 *
	 *     # List active signups.
	 *     $ wp bp signup list --active=1 --count=10
	 *     50
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		$assoc_args['number'] = $assoc_args['count'];

		if ( in_array( $formatter->format, [ 'ids', 'count' ], true ) ) {
			$assoc_args['fields'] = 'ids';
		}

		$signups = \BP_Signup::get( $assoc_args );

		if ( empty( $signups['signups'] ) ) {
			WP_CLI::error( 'No signups found.' );
		}

		$formatter->display_items( $signups['signups'] );
	}

	/**
	 * Look up a signup by the provided identifier.
	 *
	 * @since 1.5.0
	 *
	 * @return mixed
	 */
	protected function get_signup_by_identifier( $identifier, $assoc_args ) {
		if ( isset( $assoc_args['match-field'] ) ) {
			switch ( $assoc_args['match-field'] ) {
				case 'signup_id':
					$signup_args['include'] = [ $identifier ];
					break;

				case 'user_login':
					$signup_args['user_login'] = $identifier;
					break;

				case 'user_email':
				default:
					$signup_args['usersearch'] = $identifier;
					break;
			}
		} elseif ( is_numeric( $identifier ) ) {
			$signup_args['include'] = [ intval( $identifier ) ];
		} elseif ( is_email( $identifier ) ) {
			$signup_args['usersearch'] = $identifier;
		} else {
			$signup_args['user_login'] = $identifier;
		}

		$signups = \BP_Signup::get( $signup_args );
		$signup  = null;

		if ( ! empty( $signups['signups'] ) ) {
			$signup = reset( $signups['signups'] );
		}

		if ( ! $signup ) {
			WP_CLI::error( 'No signup found by that identifier.' );
		}

		return $signup;
	}
}
