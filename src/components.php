<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Components.
 *
 * ## EXAMPLES
 *
 *     # Activate a component.
 *     $ wp bp component activate groups
 *     Success: The Groups component has been activated.
 *
 *     # Deactive a component.
 *     $ wp bp component deactivate groups
 *     Success: The Groups component has been deactivated.
 *
 *     # List components.
 *     $ wp bp component list --type=required
 *     +--------+---------+--------+------------------------+--------------------------------------------+
 *     | number | id      | status | title                  | description                                |
 *     +--------+---------+--------+------------------------------------------+--------------------------+
 *     | 1      | core    | Active | BuddyPress Core        | It's what makes <del>time travel</del>     |
 *     |        |         |        |                        | BuddyPress possible!                       |
 *     | 2      | members | Active | Community Members      | Everything in a BuddyPress community       |
 *     |        |         |        |                        | revolves around its members.               |
 *     +--------+---------+--------+------------------------------------------+--------------------------+
 *
 * @since 1.6.0
 */
class Components extends BuddyPressCommand {

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'number',
		'id',
		'status',
		'title',
		'description',
	);

	/**
	 * Activate a component.
	 *
	 * ## OPTIONS
	 *
	 * <component>
	 * : Name of the component to activate.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp component activate groups
	 *     Success: The Groups component has been activated.
	 */
	public function activate( $args ) {
		$component = $args[0];

		if ( ! $this->component_exists( $component ) ) {
			WP_CLI::error( sprintf( '%s is not a valid component.', ucfirst( $component ) ) );
		}

		if ( bp_is_active( $component ) ) {
			WP_CLI::error( sprintf( 'The %s component is already active.', ucfirst( $component ) ) );
		}

		$active_components =& buddypress()->active_components;

		// Set for the rest of the page load.
		$active_components[ $component ] = 1;

		// Save in the db.
		bp_update_option( 'bp-active-components', $active_components );

		// Ensure that dbDelta() is defined.
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Run the setup, in case tables have to be created.
		require_once buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-schema.php';
		bp_core_install( $active_components );
		bp_core_add_page_mappings( $active_components );

		WP_CLI::success( sprintf( 'The %s component has been activated.', ucfirst( $component ) ) );
	}

	/**
	 * Deactivate a component.
	 *
	 * ## OPTIONS
	 *
	 * <component>
	 * : Name of the component to deactivate.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp component deactivate groups
	 *     Success: The Groups component has been deactivated.
	 */
	public function deactivate( $args ) {
		$component = $args[0];

		if ( ! $this->component_exists( $component ) ) {
			WP_CLI::error( sprintf( '%s is not a valid component.', ucfirst( $component ) ) );
		}

		if ( ! bp_is_active( $component ) ) {
			WP_CLI::error( sprintf( 'The %s component is not active.', ucfirst( $component ) ) );
		}

		if ( array_key_exists( $component, bp_core_get_components( 'required' ) ) ) {
			WP_CLI::error( 'You cannot deactivate a required component.' );
		}

		$active_components =& buddypress()->active_components;

		// Set for the rest of the page load.
		unset( $active_components[ $component ] );

		// Save in the db.
		bp_update_option( 'bp-active-components', $active_components );

		WP_CLI::success( sprintf( 'The %s component has been deactivated.', ucfirst( $component ) ) );
	}

	/**
	 * Get a list of components.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Type of the component (all, optional, retired, required).
	 * ---
	 * default: all
	 * options:
	 *   - all
	 *   - optional
	 *   - retired
	 *   - required
	 * ---
	 *
	 * [--status=<status>]
	 * : Status of the component (all, active, inactive).
	 * ---
	 * default: all
	 * options:
	 *   - all
	 *   - active
	 *   - inactive
	 * ---
	 *
	 * [--fields=<fields>]
	 * : Fields to display (id, title, description).
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - count
	 *   - csv
	 *   - haml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp component list --format=count
	 *     10
	 *
	 *     $ wp bp component list --status=inactive --format=count
	 *     4
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$formatter = $this->get_formatter( $assoc_args );

		// Get type.
		$type = $assoc_args['type'];

		// Get components.
		$components = (array) bp_core_get_components( $type );

		// Active components.
		$active_components = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components', array() ) );

		// Core component is always active.
		if ( 'optional' !== $type && isset( $components['core'] ) ) {
			if ( ! isset( $active_components['core'] ) ) {
				$active_components = array_merge( $active_components, [ 'core' => $components['core'] ] );
			}
		}

		// Inactive components.
		$inactive_components = array_diff( array_keys( $components ), array_keys( $active_components ) );

		$current_components = array();
		switch ( $assoc_args['status'] ) {
			case 'all':
				$index = 0;
				foreach ( $components as $component_key => $component ) {
					$index++;
					$current_components[] = array(
						'number'      => $index,
						'id'          => $component_key,
						'status'      => $this->verify_component_status( $component_key ),
						'title'       => esc_html( $component['title'] ),
						'description' => html_entity_decode( $component['description'] ),
					);
				}
				break;

			case 'active':
				$index = 0;
				foreach ( array_keys( $active_components ) as $component_key ) {
					$index++;
					$current_components[] = array(
						'number'      => $index,
						'id'          => $component_key,
						'status'      => $this->verify_component_status( $component_key ),
						'title'       => esc_html( $components[ $component_key ]['title'] ),
						'description' => html_entity_decode( $components[ $component_key ]['description'] ),
					);
				}
				break;

			case 'inactive':
				$index = 0;
				foreach ( $inactive_components as $component_key ) {
					$index++;
					$current_components[] = array(
						'number'      => $index,
						'id'          => $component_key,
						'status'      => $this->verify_component_status( $component_key ),
						'title'       => esc_html( $components[ $component_key ]['title'] ),
						'description' => html_entity_decode( $components[ $component_key ]['description'] ),
					);
				}
				break;
		}

		// Bail early.
		if ( empty( $current_components ) ) {
			WP_CLI::error( 'There is no component available.' );
		}

		$formatter->display_items( $current_components );
	}

	/**
	 * Does the component exist?
	 *
	 * @param string $component_key Component key.
	 * @return bool
	 */
	protected function component_exists( $component_key ) {
		return in_array(
			$component_key,
			array_keys( bp_core_get_components() ),
			true
		);
	}

	/**
	 * Verify Component Status.
	 *
	 * @since 1.7.0
	 *
	 * @param string $component_key Component key.
	 * @return string
	 */
	protected function verify_component_status( $component_key ) {
		$active = 'active';

		if ( 'core' === $component_key ) {
			return $active;
		}

		return bp_is_active( $component_key ) ? $active : 'inactive';
	}
}
