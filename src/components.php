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
 *     # List required components.
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
	protected $obj_fields = [
		'number',
		'id',
		'status',
		'title',
		'description',
	];

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
	 *     # Activate a component.
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
	 *     # Deactive a component.
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
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # List components and get the count.
	 *     $ wp bp component list --format=count
	 *     10
	 *
	 *     # List components and get the ids.
	 *     $ wp bp component list --format=ids
	 *     core members xprofile settings friends messages activity notifications groups
	 *
	 *     # List components.
	 *     $ wp bp component list
	 *     +--------+---------------+--------+--------------------+---------------------------------------------------------------------------------+
	 *     | number | id            | status | title              | description                                                                     |
	 *     +--------+---------------+--------+--------------------+---------------------------------------------------------------------------------+
	 *     | 1      | core          | active | BuddyPress Core    | It‘s what makes <del>time travel</del> BuddyPress possible!                     |
	 *     | 2      | members       | active | Community Members  | Everything in a BuddyPress community revolves around its members.               |
	 *     | 3      | xprofile      | active | Extended Profiles  | Customize your community with fully editable profile fields that allow your use |
	 *     |        |               |        |                    | rs to describe themselves.                                                      |
	 *     | 4      | settings      | active | Account Settings   | Allow your users to modify their account and notification settings directly fro |
	 *     |        |               |        |                    | m within their profiles.                                                        |
	 *     | 5      | friends       | active | Friend Connections | Let your users make connections so they can track the activity of others and fo |
	 *     |        |               |        |                    | cus on the people they care about the most.                                     |
	 *     | 6      | messages      | active | Private Messaging  | Allow your users to talk to each other directly and in private. Not just limite |
	 *     |        |               |        |                    | d to one-on-one discussions, messages can be sent between any number of members |
	 *     |        |               |        |                    | .                                                                               |
	 *     | 7      | activity      | active | Activity Streams   | Global, personal, and group activity streams with threaded commenting, direct p |
	 *     |        |               |        |                    | osting, favoriting, and @mentions, all with full RSS feed and email notificatio |
	 *     |        |               |        |                    | n support.                                                                      |
	 *     | 8      | notifications | active | Notifications      | Notify members of relevant activity with a toolbar bubble and/or via email, and |
	 *     |        |               |        |                    |  allow them to customize their notification settings.                           |
	 *     | 9      | groups        | active | User Groups        | Groups allow your users to organize themselves into specific public, private or |
	 *     |        |               |        |                    |  hidden sections with separate activity streams and member listings.            |
	 *     | 10     | blogs         | active | Site Tracking      | Record activity for new sites, posts, and comments across your network.         |
	 *     +--------+---------------+--------+--------------------+---------------------------------------------------------------------------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		// Get type.
		$type = $assoc_args['type'];

		// Get components.
		$components = (array) bp_core_get_components( $type );

		// Active components.
		$active_components = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components', [] ) );

		// Core component is always active.
		if ( 'optional' !== $type && isset( $components['core'] ) ) {
			if ( ! isset( $active_components['core'] ) ) {
				$active_components = array_merge( $active_components, [ 'core' => $components['core'] ] );
			}
		}

		// Inactive components.
		$inactive_components = array_diff( array_keys( $components ), array_keys( $active_components ) );

		$current_components = [];
		switch ( $assoc_args['status'] ) {
			case 'all':
				$index = 0;
				foreach ( $components as $component_key => $component ) {

					// Skip if the component is not available.
					if ( ! isset( $components[ $component_key ] ) ) {
						continue;
					}

					++$index;
					$current_components[] = [
						'number'      => $index,
						'id'          => $component_key,
						'status'      => $this->verify_component_status( $component_key ),
						'title'       => esc_html( $component['title'] ),
						'description' => html_entity_decode( $component['description'] ),
					];
				}
				break;

			case 'active':
				$index = 0;
				foreach ( array_keys( $active_components ) as $component_key ) {

					// Skip if the component is not available.
					if ( ! isset( $components[ $component_key ] ) ) {
						continue;
					}

					++$index;
					$current_components[] = [
						'number'      => $index,
						'id'          => $component_key,
						'status'      => $this->verify_component_status( $component_key ),
						'title'       => esc_html( $components[ $component_key ]['title'] ),
						'description' => html_entity_decode( $components[ $component_key ]['description'] ),
					];
				}
				break;

			case 'inactive':
				$index = 0;
				foreach ( $inactive_components as $component_key ) {

					// Skip if the component is not available.
					if ( ! isset( $components[ $component_key ] ) ) {
						continue;
					}

					++$index;
					$current_components[] = [
						'number'      => $index,
						'id'          => $component_key,
						'status'      => $this->verify_component_status( $component_key ),
						'title'       => esc_html( $components[ $component_key ]['title'] ),
						'description' => html_entity_decode( $components[ $component_key ]['description'] ),
					];
				}
				break;
		}

		// Bail early.
		if ( empty( $current_components ) ) {
			WP_CLI::error( 'There is no component available.' );
		}

		$formatter->display_items( 'ids' === $formatter->format ? wp_list_pluck( $current_components, 'id' ) : $current_components );
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
