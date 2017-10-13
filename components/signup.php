<?php
/**
 * Manage BuddyPress Signups.
 *
 * @since 1.3.0
 */
class BPCLI_Signup extends BPCLI_Component {

	/**
	 * Add a signup.
	 *
	 * ## OPTIONS
	 *
	 * [--user_login=<user_login>]
	 * : User login for the signup. If none is provided, a random one will be used.
	 *
	 * [--user_email=<user_email>]
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
	 *
	 * @synopsis [--user-login=<user-login>] [--user-email=<user-email>] [--meta=<meta>] [--silent=<silent>]
	 *
	 * @since 1.3.0
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
			$r['user_email'] = is_email( $this->get_random_login() . '@domain.com' );
		}

		// Sanitize email (random or not).
		$r['user_email'] = sanitize_email( $r['user_email'] );

		$id = BP_Signup::add( $r );

		if ( $r['silent'] ) {
			return;
		}

		if ( $id ) {
			WP_CLI::success( sprintf( 'Successfully added new user signup (id #%d)', $id ) );
		} else {
			WP_CLI::error( 'Could not add a user signup.' );
		}
	}

	public function generate() {}
	public function get() {}
	public function activate() {}
	public function delete() {}
	public function list_() {}
	public function resend() {}
}

WP_CLI::add_command( 'bp signup', 'BPCLI_Signup' );