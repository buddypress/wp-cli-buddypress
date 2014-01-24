<?php

class BPCLI_Core extends BPCLI_Component {
	/**
	 * Activate a component.
	 *
	 * @since 1.1
	 */
	public function activate( $args, $assoc_args ) {
		$c = $args[0];

		if ( bp_is_active( $c ) ) {
			WP_CLI::warning( sprintf( 'The %s component is already active.', ucfirst( $c ) ) );
			return;
		}

		$acs =& buddypress()->active_components;

		// Set for the rest of the page load
		$acs[ $c ] = 1;

		// Save in the db
		bp_update_option( 'bp-active-components', $acs );

		WP_CLI::success( sprintf( 'The %s component has been activated.', ucfirst( $c ) ) );
	}

	/**
	 * Deactivate a component.
	 *
	 * @since 1.1
	 */
	public function deactivate( $args, $assoc_args ) {
		$c = $args[0];

		if ( ! bp_is_active( $c ) ) {
			WP_CLI::warning( sprintf( 'The %s component is not active.', ucfirst( $c ) ) );
			return;
		}

		$acs =& buddypress()->active_components;

		// Set for the rest of the page load
		unset( $acs[ $c ] );

		// Save in the db
		bp_update_option( 'bp-active-components', $acs );

		WP_CLI::success( sprintf( 'The %s component has been deactivated.', ucfirst( $c ) ) );
	}
}
