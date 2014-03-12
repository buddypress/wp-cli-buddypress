<?php

/**
 * Manage xprofile data.
 *
 * @since 1.2.0
 */
class BPCLI_XProfile extends BPCLI_Component {
	/**
	 * Create an xprofile group.
	 *
	 * ## OPTIONS
	 *
	 * --name=<name>
	 * : The name for this field group.
	 *
	 * [--description=<description>]
	 * : The description for this field group.
	 *
	 * [--can-delete=<can-delete>]
	 * : Whether the group can be deleted. Default: true.
	 *
	 * @since 1.2.0
	 */
	public function create_group( $args, $assoc_args ) {
		$r = wp_parse_args( $assoc_args, array(
			'name'        => '',
			'description' => '',
			'can_delete'  => true,
		) );

		$group = xprofile_insert_field_group( $r );

		if ( ! $group ) {
			WP_CLI::error( 'Could not create field group.' );
		} else {
			$group = new BP_XProfile_Group( $group );
			$success = sprintf(
				'Created XProfile field group "%s" (id %d)',
				$group->name,
				$group->id
			);
			WP_CLI::success( $success );
		}
	}
}

WP_CLI::add_command( 'bp xprofile', 'BPCLI_XProfile', array(
	'before_invoke' => function() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			WP_CLI::error( 'The XProfile component is not active.' );
		}
} ) );

