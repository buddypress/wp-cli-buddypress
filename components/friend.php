<?php
/**
 * Manage BuddyPress Friends.
 *
 * @since 1.6.0
 */
class BPCLI_Friend extends BPCLI_Component {
}

WP_CLI::add_command( 'bp friend', 'BPCLI_Friend', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'friends' ) ) {
			WP_CLI::error( 'The Friends component is not active.' );
		}
	},
) );
