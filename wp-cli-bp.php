<?php

// Bail if WP-CLI is not present
if ( !defined( 'WP_CLI' ) ) return;

/**
 * BuddyPress commands
 *
 * @since 1.0
 *
 * The business logic for these subcommands is located in the /components/
 * files, to keep file size manageable (and to consolidate certain validation
 * tasks). The current class contains only the subcommand definitions and
 * their documentation (to comply with wp-cli's auto-documenter).
 */
class BPCLI_BP_Command extends WP_CLI_Command {

	protected $component = array();

	/** Core *************************************************************/

	/**
	 * Activate a component.
	 *
	 * ## OPTIONS
	 *
	 * <component>
	 * : Name of the component to activate.
	 *
	 * ## EXAMPLES
	 *
	 *	wp bp activate groups
	 *
	 * @synopsis <component>
	 *
	 * @since 1.1
	 */
	public function activate( $args, $assoc_args ) {
		$c = $this->init_component( 'core' );
		$c->run( __FUNCTION__, $args, $assoc_args );
	}

	/**
	 * Deactivate a component.
	 *
	 * ## OPTIONS
	 *
	 * <component>
	 * : Name of the component to deactivate.
	 *
	 * ## EXAMPLES
	 *
	 *	wp bp deactivate groups
	 *
	 * @synopsis <component>
	 *
	 * @since 1.1
	 */
	public function deactivate( $args, $assoc_args ) {
		$c = $this->init_component( 'core' );
		$c->run( __FUNCTION__, $args, $assoc_args );
	}

	/** Groups ***********************************************************/

	/**
	 * Create a group.
	 *
	 * ## OPTIONS
	 *
	 * --name=<name>
	 * : Name of the group.
	 *
	 * [--slug=<slug>]
	 * : URL-safe slug for the group. If not provided, one will be generated automatically.
	 *
	 * [--description=<description>]
	 * : Group description. Default: 'Description for group "[name]"'
	 *
	 * [--creator-id=<creator-id>]
	 * : ID of the group creator. Default: 1.
	 *
	 * [--slug=<slug>]
	 * : URL-safe slug for the group.
	 *
	 * [--status=<status>]
	 * : Group status (public, private, hidden). Default: public.
	 *
	 * [--enable-forum=<enable-forum>]
	 * : Whether to enable legacy bbPress forums. Default: 0.
	 *
	 * [--date-created=<date-created>]
	 * : MySQL-formatted date. Default: current date.
	 *
	 * ## EXAMPLES
	 *
	 *        wp bp group_create --name="Totally Cool Group"
	 *        wp bp group_create --name="Sports" --description="People who love sports" --creator-id=54 --status=private
	 *
	 * @synopsis --name=<name> [--slug=<slug>] [--description=<description>] [--creator-id=<creator-id>] [--status=<status>] [--enable-forum=<enable-forum>] [--date-created=<date-created>]
	 *
	 * @since 1.0
	 */
	public function group_create( $args, $assoc_args ) {
		$c = $this->init_component( 'group' );
		$c->run( __FUNCTION__, $args, $assoc_args );
	}

	/**
	 * Add a member to a group.
	 *
	 * ## OPTIONS
	 *
	 * --group-id=<group>
	 * : Identifier for the group. Accepts either a slug or a numeric ID.
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--role=<role>]
	 * : Group role for the new member (member, mod, admin). Default: member.
	 *
	 * ## EXAMPLES
	 *
	 *        wp bp group_add_member --group-id=3 --user-id=10
	 *        wp bp group_add_member --group-id=foo --user-id=admin role=mod
	 *
	 * @synopsis --group-id=<group> --user-id=<user> [--role=<role>]
	 *
	 * @since 1.0
	 */
	public function group_add_member( $args, $assoc_args ) {
		$c = $this->init_component( 'group' );
		$c->run( __FUNCTION__, $args, $assoc_args );
	}

	/** Members **********************************************************/

	/**
	 * Generate members. See documentation for `wp_user_generate`.
	 *
	 * This is a kludge workaround for setting last activity. Should fix.
	 *
	 * @since 1.0
	 */
	public function member_generate( $args, $assoc_args ) {
		$c = $this->init_component( 'member' );
		$c->run( __FUNCTION__, $args, $assoc_args );
	}

	/** Activity *********************************************************/

	/**
	 * Create an activity item.
	 *
	 * ## OPTIONS
	 *
	 * [--component=<component>]
	 * : The component for the activity item (groups, activity, etc). If
	 * none is provided, a component will be randomly selected from the
	 * active components.
	 *
	 * [--type=<type>]
	 * : Activity type (activity_update, group_created, etc). If none is
	 * provided, a type will be randomly chose from those natively
	 * associated with your <component>.
	 *
	 * [--action=<action>]
	 * : Action text (eg "Joe created a new group Foo"). If none is
	 * provided, one will be generated automatically based on other params.
	 *
	 * [--content=<content>]
	 * : Activity content text. If none is provided, default text will be
	 * generated.
	 *
	 * [--primary-link=<primary-link>]
	 * : URL of the item, as used in RSS feeds. If none is provided, a URL
	 * will be generated based on passed parameters.
	 *
	 * [--user-id=<user-id>]
	 * : ID of the user associated with the new item. If none is provided,
	 * a user will be randomly selected.
	 *
	 * [--item-id=<item-id>]
	 * : ID of the associated item. If none is provided, one will be
	 * generated automatically, if your activity type requires it.
	 *
	 * [--secondary-item-id=<secondary-item-id>]
	 * : ID of the secondary associated item. If none is provided, one will
	 * be generated automatically, if your activity type requires it.
	 *
	 * [--date-recorded=<date-recorded>]
	 * : GMT timestamp, in Y-m-d h:i:s format. Defaults to current time.
	 *
	 * [--hide-sitewide=<hide-sitewide>]
	 * : Whether to hide in sitewide streams. Default: 0.
	 *
	 * [--is-spam=<is-spam>]
	 * : Whether the item should be marked as spam. Default: 0.
	 *
	 * @synopsis [--component=<component>] [--type=<type>] [--action=<action>] [--content=<content>] [--primary-link=<primary-link>] [--user-id=<user-id>] [--item-id=<item-id>] [--secondary-item-id=<secondary-item-id>] [--date-recorded=<date-recorded>] [--hide-sitewide=<hide-sitewide>] [--is-spam=<is-spam>]
	 *
	 * @since 1.1
	 */
	public function activity_create( $args, $assoc_args ) {
		$c = $this->init_component( 'activity' );
		$c->run( __FUNCTION__, $args, $assoc_args );
	}

	/** Utility **********************************************************/

	/**
	 * Initialize the component library.
	 *
	 * Loads the component library file, if necessary, and returns the
	 * component object.
	 *
	 * @since 1.0
	 *
	 * @param string $component
	 */
	protected function init_component( $component ) {
		if ( isset( $this->components[ $component ] ) ) {
			return $this->components[ $component ];
		}

		if ( ! class_exists( 'BPCLI_Component' ) ) {
			require_once( __DIR__ . '/component.php' );
		}

		$cn = 'BPCLI_' . ucwords( $component );
		$fn = __DIR__ . '/components/' . $component . '.php';
		if ( file_exists( $fn ) ) {
			require_once( $fn );

			if ( class_exists( $cn ) ) {
				$this->components[ $component ] = new $cn;
			}
		}

		return $this->components[ $component ];
	}
}
WP_CLI::add_command( 'bp', 'BPCLI_BP_Command' );

