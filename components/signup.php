<?php
/**
 * Manage BuddyPress Signups.
 *
 * @since 1.5.0
 */
class BPCLI_Signup extends BPCLI_Component {

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
	 * [--meta=<meta>]
	 * : User meta for the signup.
	 *
	 * [--silent=<silent>]
	 * : Silent the signup creation. Default: false.
	 *
	 * ## EXAMPLES
	 *
	 *   wp bp signup add --user-login=test_user --user-email=teste@site.com
	 *   wp bp signup add --user-login=test_user --user-email=teste@site.com --silent=1
	 *
	 * @synopsis [--user-login=<user-login>] [--user-email=<user-email>] [--meta=<meta>] [--silent=<silent>]
	 *
	 * @since 1.5.0
	 */
	public function add( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'user_login'     => '',
			'user_email'     => '',
			'activation_key' => wp_generate_password( 32, false ),
			'meta'           => '',
			'silent'         => false,
		) );

		// Add a random user login if none is provided.
		if ( empty( $r['user_login'] ) ) {
			$r['user_login'] = $this->get_random_login();
		}

		// Sanitize login (random or not).
		$r['user_login'] = preg_replace( '/\s+/', '', sanitize_user( $r['user_login'], true ) );

		// Add a random email if none is provided.
		if ( empty( $r['user_email'] ) ) {
			$r['user_email'] = is_email( $this->get_random_login() . '@site.com' );
		}

		// Sanitize email (random or not).
		$r['user_email'] = sanitize_email( $r['user_email'] );

		$id = BP_Signup::add( $r );

		if ( $r['silent'] ) {
			return;
		}

		if ( $id ) {
			WP_CLI::success( sprintf( 'Successfully added new user signup (ID #%d)', $id ) );
		} else {
			WP_CLI::error( 'Could not add a user signup.' );
		}
	}

	/**
	 * Delete a signup.
	 *
	 * ## OPTIONS
	 *
	 * <signup-id>
	 * : Identifier for the signup.
	 *
	 * ## EXAMPLE
	 *
	 *  wp bp signup delete 520
	 *
	 * @synopsis <signup-id>
	 *
	 * @since 1.5.0
	 */
	public function delete( $args, $assoc_args ) {
		$signup_id = isset( $args[0] ) ? $args[0] : '';

		// Bail early.
		if ( empty( $signup_id ) ) {
			WP_CLI::error( 'Please specify a signup ID.' );
		}

		if ( ! is_numeric( $signup_id ) ) {
			WP_CLI::error( 'Invalid signup ID.' );
		}

		if ( BP_Signup::delete( $signup_id ) ) {
			WP_CLI::success( 'Signup deleted.' );
		} else {
			WP_CLI::error( 'Could not delete signup.' );
		}
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
	 *  wp bp signup activate ee48ec319fef3nn4
	 *
	 * @synopsis <activation-key>
	 *
	 * @since 1.5.0
	 */
	public function activate( $args, $assoc_args ) {
		$key = isset( $args[0] ) ? $args[0] : '';

		// Bail early.
		if ( empty( $key ) ) {
			WP_CLI::error( 'Please specify an activation key.' );
		}

		$id = bp_core_activate_signup( $key );

		if ( $id ) {
			WP_CLI::success( sprintf( 'Signup activated, new user (ID #%d)', $id ) );
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
	 * : How many signups to generate. Default: 100
	 *
	 * ## EXAMPLE
	 *
	 *  wp bp signup generate --count=50
	 *
	 * @synopsis [--count=<number>]
	 *
	 * @since 1.5.0
	 */
	public function generate( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'count' => 100,
		) );

		$notify = \WP_CLI\Utils\make_progress_bar( 'Generating signups', $r['count'] );

		for ( $i = 0; $i < $r['count']; $i++ ) {
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
	 * [--user-id=<user-id>]
	 * : User ID to send the e-mail.
	 *
	 * [--user-email=<user-email>]
	 * : E-mail to send the activation.
	 *
	 * [--key=<key>]
	 * : Activation key for the e-mail.
	 *
	 * ## EXAMPLE
	 *
	 *   wp bp signup resend --user-id=20 --user-email=teste@site.com --key=ee48ec319fef3nn4
	 *
	 * @synopsis [--user-id=<user-id>] [--user-email=<user-email>] [--key=<key>]
	 *
	 * @since 1.5.0
	 */
	public function resend( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'user-id'    => '',
			'user-email' => '',
			'key'        => '',
		) );

		// Bail if no user id.
		if ( empty( $r['user-id'] ) ) {
			WP_CLI::error( 'Please specify a user ID.' );
		}

		// Bail if no email.
		if ( empty( $r['user-email'] ) ) {
			WP_CLI::error( 'Please specify a user email.' );
		}

		// Bail if no key.
		if ( empty( $r['key'] ) ) {
			WP_CLI::error( 'Please specify an activation key.' );
		}

		bp_core_signup_send_validation_email( $r['user-id'], $r['user-email'], $r['key'] );

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
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *   wp bp signup list --format=ids
	 *   wp bp signup list --usersearch=user_login
	 *   wp bp signup list --number=100 --format=count
	 *   wp bp signup list --number=5 --activation_key=ee48ec319fef3nn4
	 *
	 * @synopsis [--field=<value>] [--format=<format>]
	 *
	 * @since 1.5.0
	 */
	public function list_( $_, $assoc_args ) {
		$formatter  = $this->get_formatter( $assoc_args );
		$signups    = BP_Signup::get( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', wp_list_pluck( $signups['signups'], 'signup_id' ) ); // WPCS: XSS ok.
		} elseif ( 'count' === $formatter->format ) {
			$formatter->display_items( $signups['total'] );
		} else {
			$formatter->display_items( $signups );
		}
	}
}

WP_CLI::add_command( 'bp signup', 'BPCLI_Signup' );
