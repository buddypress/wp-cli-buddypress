<?php

/**
 * 'bp' command
 *
 * We fake multi-tiered commands by using the first arg as a router. Eg
 *
 *   wp bp group create
 *
 * calls the 'group' method here, and routs it to the BPCLI_Group class for
 * processing
 *
 * The business logic for these subcommands is located in the /components/
 * files, to keep file size manageable (and to consolidate certain validation
 * tasks). The current class contains only the subcommand definitions and
 * their documentation (to comply with wp-cli's auto-documenter).
 *
 * @package bp-cli
 * @since 1.0
 */
class BPCLI_BP_Command extends WP_CLI_Command {

	protected $component = array();

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
	 */
	public function member_generate( $args, $assoc_args ) {
		$c = $this->init_component( 'member' );
		$c->run( __FUNCTION__, $args, $assoc_args );
	}

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

