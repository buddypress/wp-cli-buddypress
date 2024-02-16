<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Friends.
 *
 * ## EXAMPLES
 *
 *     $ wp bp friend create user1 another_use
 *     Success: Friendship successfully created.
 *
 *     $ wp bp friend create user1 another_use --force-accept
 *     Success: Friendship successfully created.
 *
 * @since 1.6.0
 */
class Friends extends BuddyPressCommand {

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = [
		'id',
		'initiator_user_id',
		'friend_user_id',
		'is_confirmed',
		'is_limited',
	];

	/**
	 * Dependency check for this CLI command.
	 */
	public static function check_dependencies() {
		parent::check_dependencies();

		if ( ! bp_is_active( 'friends' ) ) {
			WP_CLI::error( 'The Friends component is not active.' );
		}
	}

	/**
	 * Create a new friendship.
	 *
	 * ## OPTIONS
	 *
	 * <initiator>
	 * : ID of the user who is sending the friendship request. Accepts either a user_login or a numeric ID.
	 *
	 * <friend>
	 * : ID of the user whose friendship is being requested. Accepts either a user_login or a numeric ID.
	 *
	 * [--force-accept]
	 * : Whether to force acceptance.
	 *
	 * [--silent]
	 * : Whether to silent the message creation.
	 *
	 * [--porcelain]
	 * : Return only the friendship id.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a new friendship.
	 *     $ wp bp friend create user1 another_use
	 *     Success: Friendship successfully created.
	 *
	 *     # Create a new friendship, forcing acceptance.
	 *     $ wp bp friend create user1 another_use --force-accept
	 *     Success: Friendship successfully created.
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$initiator = $this->get_user_id_from_identifier( $args[0] );
		$friend    = $this->get_user_id_from_identifier( $args[1] );

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		// Check if users are already friends, and bail if they do.
		if ( friends_check_friendship( $initiator->ID, $friend->ID ) ) {
			WP_CLI::error( 'These users are already friends.' );
		}

		$force = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'force-accept' );

		if ( ! friends_add_friend( $initiator->ID, $friend->ID, $force ) ) {
			WP_CLI::error( 'There was a problem while creating the friendship.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( \BP_Friends_Friendship::get_friendship_id( $initiator->ID, $friend->ID ) );
		} elseif ( $force ) {
			WP_CLI::success( 'Friendship successfully created.' );
		} else {
			WP_CLI::success( 'Friendship successfully created but not accepted.' );
		}
	}

	/**
	 * Remove a friendship.
	 *
	 * ## OPTIONS
	 *
	 * <initiator>
	 * : ID of the friendship initiator. Accepts either a user_login or a numeric ID.
	 *
	 * <friend>
	 * : ID of the friend user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLE
	 *
	 *     # Remove a friendship.
	 *     $ wp bp friend remove user_1 user_2
	 *     Success: Friendship successfully removed.
	 *
	 * @alias remove
	 * @alias trash
	 */
	public function delete( $args ) {
		$initiator = $this->get_user_id_from_identifier( $args[0] );
		$friend    = $this->get_user_id_from_identifier( $args[1] );

		// Check if users are already friends, if not, bail.
		if ( ! friends_check_friendship( $initiator->ID, $friend->ID ) ) {
			WP_CLI::error( 'These users are not friends.' );
		}

		if ( friends_remove_friend( $initiator->ID, $friend->ID ) ) {
			WP_CLI::success( 'Friendship successfully removed.' );
		} else {
			WP_CLI::error( 'There was a problem while removing the friendship.' );
		}
	}

	/**
	 * Mark a friendship request as accepted.
	 *
	 * ## OPTIONS
	 *
	 * <friendship>...
	 * : ID(s) of the friendship(s).
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend accept-invitation 2161
	 *     Success: Friendship successfully accepted.
	 *
	 *     $ wp bp friend accept 2161
	 *     Success: Friendship successfully accepted.
	 *
	 * @alias accept-invitation
	 */
	public function accept( $args, $assoc_args ) {
		parent::_update(
			wp_parse_id_list( $args ),
			$assoc_args,
			function ( $friendship_id ) {
				if ( friends_accept_friendship( $friendship_id ) ) {
					return [ 'success', 'Friendship successfully accepted.' ];
				}

				return [ 'error', 'There was a problem accepting the friendship.' ];
			}
		);
	}

	/**
	 * Mark a friendship request as rejected.
	 *
	 * ## OPTIONS
	 *
	 * <friendship>...
	 * : ID(s) of the friendship(s).
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend reject-invitation 2161
	 *     Success: Friendship successfully accepted.
	 *
	 *     $ wp bp friend reject 2161 151 2121
	 *     Success: Friendship successfully accepted.
	 *
	 * @alias reject-invitation
	 */
	public function reject( $args, $assoc_args ) {
		parent::_update(
			wp_parse_id_list( $args ),
			$assoc_args,
			function ( $friendship_id ) {
				if ( friends_reject_friendship( $friendship_id ) ) {
					return [ 'success', 'Friendship successfully rejected.' ];
				}

				return [ 'error', 'There was a problem rejecting the friendship.' ];
			}
		);
	}

	/**
	 * Check whether two users are friends.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : ID of the first user. Accepts either a user_login or a numeric ID.
	 *
	 * <friend>
	 * : ID of the other user. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp friend check 2161 65465
	 *     Success: Yes, they are friends.
	 *
	 *     $ wp bp friend see 2121 65456
	 *     Success: Yes, they are friends.
	 *
	 * @alias see
	 */
	public function check( $args ) {
		$user   = $this->get_user_id_from_identifier( $args[0] );
		$friend = $this->get_user_id_from_identifier( $args[1] );

		if ( friends_check_friendship( $user->ID, $friend->ID ) ) {
			WP_CLI::success( 'Yes, they are friends.' );
		} else {
			WP_CLI::error( 'No, they are not friends.' );
		}
	}

	/**
	 * Get a list of user's friends.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : ID of the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--fields=<fields>]
	 * : Fields to display.
	 *
	 * [--count=<number>]
	 * : How many user's friends to list.
	 * ---
	 * default: 50
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - ids
	 *   - count
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # List a user's friends and get the count.
	 *     $ wp bp friend list 65465 --format=count
	 *     100
	 *
	 *     # List a user's friends and get the IDs.
	 *     $ wp bp friend list 2422 --format=ids
	 *     70 71 72 73 74
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );
		$user      = $this->get_user_id_from_identifier( $args[0] );
		$friends   = \BP_Friends_Friendship::get_friendships(
			$user->ID,
			[
				'page'     => 1,
				'per_page' => $assoc_args['count'],
			]
		);

		if ( empty( $friends ) ) {
			WP_CLI::error( 'This member has no friends.' );
		}

		$formatter->display_items( 'ids' === $formatter->format ? wp_list_pluck( $friends, 'friend_user_id' ) : $friends );
	}

	/**
	 * Generate random friendships.
	 *
	 * ## OPTIONS
	 *
	 * [--initiator=<user>]
	 * : ID of the first user. Accepts either a user_login or a numeric ID.
	 *
	 * [--friend=<user>]
	 * : ID of the second user. Accepts either a user_login or a numeric ID.
	 *
	 * [--count=<number>]
	 * : How many friendships to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: progress
	 * options:
	 *   - progress
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate 50 random friendships.
	 *     $ wp bp friend generate --count=50
	 *     Generating friendships  100% [======================] 0:00 / 0:00
	 *
	 *     # Generate 50 friendships with a specific user.
	 *     $ wp bp friend generate --initiator=121 --count=50
	 *     Generating friendships  100% [======================] 0:00 / 0:00
	 *
	 *     # Generate 5 random friendships and output only the IDs.
	 *     $ wp bp friend generate --count=5 --format=ids
	 *     70 71 72 73 74
	 */
	public function generate( $args, $assoc_args ) {
		$member_id = null;
		$friend_id = null;

		if ( isset( $assoc_args['initiator'] ) ) {
			$user      = $this->get_user_id_from_identifier( $assoc_args['initiator'] );
			$member_id = $user->ID;
		}

		if ( isset( $assoc_args['friend'] ) ) {
			$user_2    = $this->get_user_id_from_identifier( $assoc_args['friend'] );
			$friend_id = $user_2->ID;
		}

		$this->generate_callback(
			'Generating friendships',
			$assoc_args,
			function ( $assoc_args, $format ) use ( $member_id, $friend_id ) {
				if ( ! $member_id ) {
					$member_id = $this->get_random_user_id();
				}

				if ( ! $friend_id ) {
					$friend_id = $this->get_random_user_id();
				}

				$params = [ 'force-accept' => true ];

				if ( 'ids' === $format ) {
					$params['porcelain'] = true;
				} else {
					$params['silent'] = true;
				}

				return $this->create( [ $member_id, $friend_id ], $params );
			}
		);
	}
}
