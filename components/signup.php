<?php
/**
 * Manage BuddyPress signups.
 *
 * @since 1.3.0
 */
class BPCLI_Signup extends BPCLI_Component {

	public function add() {}
	public function generate() {}
	public function get() {}
	public function activate() {}
	public function delete() {}
	public function list_() {}
	public function resend() {}
}

WP_CLI::add_command( 'bp signup', 'BPCLI_Signup' );
