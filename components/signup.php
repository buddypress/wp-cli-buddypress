<?php
/**
 * Manage BuddyPress Signups.
 *
 * @since 1.5.0
 */
class BPCLI_Signup extends BPCLI_Component {

	/**
	 * XProfile object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'id',
		'user_login',
		'user_name',
		'meta',
		'activation_key',
		'registered',
	);

	/**
	 * Add a signup.
	 *
	 * ## OPTIONS
	 *
	 * [--user-login=<user-login>]
	 * : User login for the signup. If none is provided, a random one will be used.
	 *
	 * [--user-email=<user-email>]
	 * : User email for the signup. If none is provided, a random one will be used.
	 *
	 * [--activation-key=<activation-key>]
	 * : Activation key for the signup.
	 *
	 * [--silent=<silent>]
	 * : Silent signup creation.
	 * ---
	 * Default: false
	 * ---
	 *
	 * [--porcelain]
	 * : Output only the new signup id.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp signup add --user-login=test_user --user-email=teste@site.com
	 *     Success: Successfully added new user signup (ID #345).
	 */
	public function add( $args, $assoc_args ) {
		$signup_args = array(
			'meta' => '',
		);

		// Add a random user login if none is provided.
		$signup_args['user_login'] = ( isset( $assoc_args['user-login'] ) )
			? $assoc_args['user-login']
			: $this->get_random_login();

		// Sanitize login (random or not).
		$signup_args['user_login'] = preg_replace( '/\s+/', '', sanitize_user( $signup_args['user_login'], true ) );

		// Add a random email if none is provided.
		$signup_args['user_email'] = ( isset( $assoc_args['user-email'] ) )
			? $assoc_args['user-email']
			: $this->get_random_login() . '@example.com';

		// Sanitize email (random or not).
		$signup_args['user_email'] = sanitize_email( $signup_args['user_email'] );

		$signup_args['activation_key'] = ( isset( $assoc_args['activation-key'] ) )
			? $assoc_args['activation-key']
			: wp_generate_password( 32, false );

		$id = BP_Signup::add( $signup_args );

		if ( ! $id ) {
			WP_CLI::error( 'Could not add user signup' );
		}

		if ( $assoc_args['silent'] ) {
			return;
		}

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $id );
		} else {
			WP_CLI::success( sprintf( 'Successfully added new user signup (ID #%d).', $id ) );
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
	 * [--field=<field>]
	 * : Field to match the signup-id to. Use if there is ambiguity between, eg, signup ID and user_login.
	 * ---
	 * options:
	 *   - id
	 *   - email
	 *   - login
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
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp signup get 123
	 *     $ wp bp signup get foo@example.com
	 *     $ wp bp signup get 123 --field=id
	 */
	public function get( $args, $assoc_args ) {
		$id = $args[0];

		$signup_args = array(
			'number' => 1,
		);

		if ( isset( $assoc_args['field'] ) ) {
			switch ( $assoc_args['field'] ) {
				case 'id' :
					$signup_args['include'] = array( $id );
				break;

				case 'email' :
					$signup_args['usersearch'] = $id;
				break;

				case 'login' :
					$signup_args['user_login'] = $id;
				break;
			}
		} else {
			if ( is_numeric( $id ) ) {
				$signup_args['include'] = array( $id );
			} elseif ( is_email( $id ) ) {
				$signup_args['usersearch'] = $id;
			} else {
				$signup_args['user_login'] = $id;
			}
		}

		$signups = BP_Signup::get( $signup_args );
		$formatter = $this->get_formatter( $assoc_args );

		if ( ! empty( $signups['signups'] ) ) {
			$formatter->display_item( $signups['signups'][0] );
		} else {
			WP_CLI::error( 'No signup found by that identifier.' );
		}
	}

	/**
	 * Delete a signup.
	 *
	 * ## OPTIONS
	 *
	 * <signup-id>...
	 * : ID or IDs of signup.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp signup delete 520
	 *     Success: Signup deleted.
	 *
	 *     $ wp bp signup delete 55654 54564 --yes
	 *     Success: Signup deleted.
	 */
	public function delete( $args, $assoc_args ) {
		$signup_id = $args[0];

		WP_CLI::confirm( 'Are you sure you want to delete this signup?', $assoc_args );

		parent::_delete( array( $signup_id ), $assoc_args, function( $signup_id ) {
			if ( BP_Signup::delete( array( $signup_id ) ) ) {
				return array( 'success', 'Signup deleted.' );
			} else {
				return array( 'error', 'Could not delete signup.' );
			}
		} );
	}

	/**
	 * Activate a signup.
	 *
	 * ## OPTIONS
	 *
	 * <activation-key>
	 * : Identifier for the activation key.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp signup activate ee48ec319fef3nn4
	 *     Success: Signup activated, new user (ID #545).
	 */
	public function activate( $args, $assoc_args ) {
		$id = bp_core_activate_signup( $args[0] );

		if ( is_string( $id ) ) {
			WP_CLI::success( sprintf( 'Signup activated, new user (ID #%d).', $id ) );
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
	 * ## EXAMPLE
	 *
	 *     $ wp bp signup generate --count=50
	 */
	public function generate( $args, $assoc_args ) {
		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating signups', $assoc_args['count'] );

		for ( $i = 0; $i < $assoc_args['count']; $i++ ) {
			$this->add( array(), array(
				'silent' => true,
			) );

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Resend activation e-mail to a newly registered user.
	 *
	 * ## OPTIONS
	 *
	 * [<user>]
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * <email>
	 * : E-mail to send the activation.
	 *
	 * <key>
	 * : Activation key.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp signup resend 20 teste@site.com ee48ec319fef3nn4
	 *     Success: Email sent successfully.
	 *
	 *     $ wp bp signup send another_teste@site.com ee48ec319fefwtr3nn4
	 *     Success: Email sent successfully.
	 *
	 * @alias send
	 */
	public function resend( $args, $assoc_args ) {
		$user_id = '';

		if ( ! defined( 'BP_SIGNUPS_SKIP_USER_CREATION' ) && ! BP_SIGNUPS_SKIP_USER_CREATION ) {
			$user = $this->get_user_id_from_identifier( $args[0] );

			if ( ! $user ) {
				WP_CLI::error( 'No user found by that username or id' );
				return;
			}

			$user_id = $user->ID;
		}

		$email = $args[1];
		if ( ! is_email( $email ) ) {
			WP_CLI::error( 'Invalid email added.' );
		}

		// Send email.
		bp_core_signup_send_validation_email( $user_id, sanitize_email( $email ), $args[2] );

		WP_CLI::success( 'Email sent successfully.' );
	}

	/**
	 * Get a list of signups.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See BP_Signup::get()
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - ids
	 *   - count
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp signup list --format=ids
	 *     $ wp bp signup list --number=100 --format=count
	 *     $ wp bp signup list --number=5 --activation_key=ee48ec319fef3nn4
	 *
	 * @subcommand list
	 */
	public function _list( $_, $assoc_args ) {
		$formatter  = $this->get_formatter( $assoc_args );
		$signups    = BP_Signup::get( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', wp_list_pluck( $signups['signups'], 'signup_id' ) ); // WPCS: XSS ok.
		} elseif ( 'count' === $formatter->format ) {
			WP_CLI::line( $signups['total'] );
		} else {
			$formatter->display_items( $signups['signups'] );
		}
	}
}

WP_CLI::add_command( 'bp signup', 'BPCLI_Signup' );
