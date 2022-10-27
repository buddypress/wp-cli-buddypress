<?php

namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Messages.
 *
 *  ## EXAMPLES
 *
 *     $ wp bp message create --from=user1 --to=user2 --subject="Message Title" --content="We are ready"
 *     Success: Message successfully created.
 *
 *     $ wp bp message delete-thread 564 5465465 456456 --user-id=user_logon --yes
 *     Success: Thread successfully deleted.
 *
 * @since 1.6.0
 */
class Messages extends BuddyPressCommand {

	/**
	 * Dependency check for this CLI command.
	 */
	public static function check_dependencies() {
		parent::check_dependencies();

		if ( ! bp_is_active( 'messages' ) ) {
			WP_CLI::error( 'The Message component is not active.' );
		}
	}

	/**
	 * Object fields.
	 *
	 * @var array
	 */
	protected $obj_fields = array(
		'id',
		'subject',
		'message',
		'thread_id',
		'sender_id',
		'date_sent',
	);

	/**
	 * Add a message.
	 *
	 * ## OPTIONS
	 *
	 * --from=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--to=<user>]
	 * : Identifier for the recipient. To is not required when thread id is set.
	 *  Accepts either a user_login or a numeric ID.
	 *
	 * --subject=<subject>
	 * : Subject of the message.
	 *
	 * --content=<content>
	 * : Content of the message.
	 *
	 * [--thread-id=<thread-id>]
	 * : Thread ID.
	 *
	 * [--date-sent=<date-sent>]
	 * : MySQL-formatted date.
	 *
	 * [--silent]
	 * : Whether to silent the message creation.
	 *
	 * [--porcelain]
	 * : Return the thread id of the message.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp message create --from=user1 --to=user2 --subject="Message Title" --content="We are ready"
	 *     Success: Message successfully created.
	 *
	 *     $ wp bp message create --from=545 --to=313 --subject="Another Message Title" --content="Message OK"
	 *     Success: Message successfully created.
	 *
	 * @alias add
	 */
	public function create( $args, $assoc_args ) {
		$r = wp_parse_args(
			$assoc_args,
			array(
				'to'        => '',
				'thread-id' => false,
				'date-sent' => bp_core_current_time(),
			)
		);

		$user = $this->get_user_id_from_identifier( $assoc_args['from'] );

		// To is not required when thread id is set.
		if ( ! empty( $r['to'] ) ) {
			$recipient = $this->get_user_id_from_identifier( $r['to'] );
		}

		// Existing thread recipients will be assumed.
		$recipient = ! empty( $r['thread-id'] ) ? array() : array( $recipient->ID );

		$thread_id = messages_new_message(
			array(
				'sender_id'  => $user->ID,
				'thread_id'  => $r['thread-id'],
				'recipients' => $recipient,
				'subject'    => $assoc_args['subject'],
				'content'    => $assoc_args['content'],
				'date_sent'  => $r['date-sent'],
			)
		);

		// Silent it before it errors.
		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'silent' ) ) {
			return;
		}

		if ( ! is_numeric( $thread_id ) ) {
			WP_CLI::error( 'Could not add a message.' );
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::log( $thread_id );
		} else {
			WP_CLI::success( 'Message successfully created.' );
		}
	}

	/**
	 * Delete thread(s) for a given user.
	 *
	 * ## OPTIONS
	 *
	 * <thread-id>...
	 * : Thread ID(s).
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp message delete-thread 500 687867 --user-id=40
	 *     Success: Thread successfully deleted.
	 *
	 *     $ wp bp message delete-thread 564 5465465 456456 --user-id=user_logon --yes
	 *     Success: Thread successfully deleted.
	 *
	 * @alias delete-thread
	 * @alias remove-thread
	 */
	public function delete_thread( $args, $assoc_args ) {
		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		WP_CLI::confirm( 'Are you sure you want to delete this thread(s)?', $assoc_args );

		parent::_delete( $args, $assoc_args, function( $thread_id ) use ( $user ) {
			if ( messages_delete_thread( $thread_id, $user->ID ) ) {
				return array( 'success', 'Thread successfully deleted.' );
			} else {
				return array( 'error', 'Could not delete the thread.' );
			}
		});
	}

	/**
	 * Get a message.
	 *
	 * ## OPTIONS
	 *
	 * <message-id>
	 * : Identifier for the message.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - haml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp message get 5465
	 *     $ wp bp message see 5454
	 *
	 * @alias see
	 */
	public function get( $args, $assoc_args ) {
		$message     = new \BP_Messages_Message( $args[0] );
		$message_arr = get_object_vars( $message );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $message_arr );
		}

		$this->get_formatter( $assoc_args )->display_item( $message_arr );
	}

	/**
	 * Get a list of messages for a specific user.
	 *
	 * ## OPTIONS
	 *
	 * --user-id=<user>
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--<field>=<value>]
	 * : One or more parameters to pass. See \BP_Messages_Box_Template()
	 *
	 * [--fields=<fields>]
	 * : Fields to display.
	 *
	 * [--count=<number>]
	 * : How many messages to list.
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--box=<box>]
	 * : Box of the message.
	 * ---
	 * default: sentbox
	 * options:
	 *   - sentbox
	 *   - inbox
	 *   - notices
	 * ---
	 *
	 * [--type=<type>]
	 * : Type of the message.
	 * ---
	 * default: all
	 * options:
	 *   - unread
	 *   - read
	 *   - all
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
	 *   - haml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp message list --user-id=544 --format=count
	 *     10
	 *
	 *     $ wp bp message list --user-id=user_login --count=3 --format=ids
	 *     5454 45454 4545 465465
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		$formatter = $this->get_formatter( $assoc_args );

		$r = wp_parse_args(
			$assoc_args,
			array(
				'search' => '',
			)
		);

		$user = $this->get_user_id_from_identifier( $assoc_args['user-id'] );

		$inbox = new \BP_Messages_Box_Template(
			array(
				'user_id'      => $user->ID,
				'box'          => $r['box'],
				'type'         => $r['type'],
				'max'          => $r['count'],
				'search_terms' => $r['search'],
			)
		);

		if ( ! $inbox->has_threads() ) {
			WP_CLI::error( 'No messages found.' );
		}

		$messages = $inbox->threads[0]->messages;

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', wp_list_pluck( $messages, 'id' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			$formatter->display_items( $messages );
		}
	}

	/**
	 * Generate random messages.
	 *
	 * ## OPTIONS
	 *
	 * [--from=<user>]
	 * : Identifier for the user. Accepts either a user_login or a numeric ID.
	 *
	 * [--to=<user>]
	 * : Identifier for the recipient. To is not required when thread id is set.
	 *  Accepts either a user_login or a numeric ID.
	 *
	 * [--thread-id=<thread-id>]
	 * : Thread ID to generate messages against.
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--count=<number>]
	 * : How many messages to generate.
	 * ---
	 * default: 20
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp message generate --from=1 --to=2 --thread-id=6465 --count=30
	 *     $ wp bp message generate --thread-id=6465 --count=10
	 *     $ wp bp message generate --count=100
	 */
	public function generate( $args, $assoc_args ) {
		$notify = WP_CLI\Utils\make_progress_bar( 'Generating messages', $assoc_args['count'] );

		for ( $i = 0; $i < $assoc_args['count']; $i++ ) {
			$this->create(
				array(),
				array(
					'from'      => empty( $assoc_args['from'] ) ? $this->get_random_user_id() : $assoc_args['from'],
					'to'        => empty( $assoc_args['to'] ) ? $this->get_random_user_id() : $assoc_args['to'],
					'subject'   => sprintf( 'Message Subject - #%d', $i ),
					'content'   => $this->generate_random_text(),
					'thread-id' => empty( $assoc_args['thread-id'] ) ? false : $assoc_args['thread-id'],
					'silent',
				)
			);

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Star a message.
	 *
	 * ## OPTIONS
	 *
	 * <message-id>
	 * : Message ID to star.
	 *
	 * --user-id=<user>
	 * : User that is starring the message. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp message star 3543 --user-id=user_login
	 *     Success: Message was successfully starred.
	 */
	public function star( $args, $assoc_args ) {
		$user    = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$user_id = $user->ID;
		$msg_id  = (int) $args[0];

		if ( bp_messages_is_message_starred( $msg_id, $user_id ) ) {
			WP_CLI::error( 'The message is already starred.' );
		}

		$star_args = array(
			'action'     => 'star',
			'message_id' => $msg_id,
			'user_id'    => $user_id,
		);

		if ( bp_messages_star_set_action( $star_args ) ) {
			WP_CLI::success( 'Message was successfully starred.' );
		} else {
			WP_CLI::error( 'Message was not starred.' );
		}
	}

	/**
	 * Unstar a message.
	 *
	 * ## OPTIONS
	 *
	 * <message-id>
	 * : Message ID to unstar.
	 *
	 * --user-id=<user>
	 * : User that is unstarring the message. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp message unstar 212 --user-id=another_user_login
	 *     Success: Message was successfully unstarred.
	 */
	public function unstar( $args, $assoc_args ) {
		$user    = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$user_id = $user->ID;
		$msg_id  = (int) $args[0];

		// Check if the message is starred first.
		if ( ! bp_messages_is_message_starred( $msg_id, $user_id ) ) {
			WP_CLI::error( 'You need to star a message first before unstarring it.' );
		}

		$star_args = array(
			'action'     => 'unstar',
			'message_id' => $msg_id,
			'user_id'    => $user_id,
		);

		if ( bp_messages_star_set_action( $star_args ) ) {
			WP_CLI::success( 'Message was successfully unstarred.' );
		} else {
			WP_CLI::error( 'Message was not unstarred.' );
		}
	}

	/**
	 * Star a thread.
	 *
	 * ## OPTIONS
	 *
	 * <thread-id>
	 * : Thread ID to star.
	 *
	 * --user-id=<user>
	 * : User that is starring the thread. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp message star-thread 212 --user-id=another_user_login
	 *     Success: Thread was successfully starred.
	 *
	 * @alias star-thread
	 */
	public function star_thread( $args, $assoc_args ) {
		$user      = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$thread_id = (int) $args[0];

		// Check if it is a valid thread.
		if ( ! messages_is_valid_thread( $thread_id ) ) {
			WP_CLI::error( 'This is not a valid thread ID.' );
		}

		// Check if the user has access to this thread.
		$id = messages_check_thread_access( $thread_id, $user->ID );
		if ( ! is_numeric( $id ) ) {
			WP_CLI::error( 'User has no access to this thread.' );
		}

		$star_args = array(
			'action'    => 'star',
			'thread_id' => $thread_id,
			'user_id'   => $user->ID,
			'bulk'      => true,
		);

		if ( bp_messages_star_set_action( $star_args ) ) {
			WP_CLI::success( 'Thread was successfully starred.' );
		} else {
			WP_CLI::error( 'Something wrong while trying to star the thread.' );
		}
	}

	/**
	 * Unstar a thread.
	 *
	 * ## OPTIONS
	 *
	 * <thread-id>
	 * : Thread ID to unstar.
	 *
	 * --user-id=<user>
	 * : User that is unstarring the thread. Accepts either a user_login or a numeric ID.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp message unstar-thread 212 --user-id=another_user_login
	 *     Success: Thread was successfully unstarred.
	 *
	 * @alias unstar-thread
	 */
	public function unstar_thread( $args, $assoc_args ) {
		$user      = $this->get_user_id_from_identifier( $assoc_args['user-id'] );
		$thread_id = (int) $args[0];

		// Check if it is a valid thread.
		if ( ! messages_is_valid_thread( $thread_id ) ) {
			WP_CLI::error( 'This is not a valid thread ID.' );
		}

		// Check if the user has access to this thread.
		$id = messages_check_thread_access( $thread_id, $user->ID );
		if ( ! is_numeric( $id ) ) {
			WP_CLI::error( 'User has no access to this thread.' );
		}

		$star_args = array(
			'action'    => 'unstar',
			'thread_id' => $thread_id,
			'user_id'   => $user->ID,
			'bulk'      => true,
		);

		if ( bp_messages_star_set_action( $star_args ) ) {
			WP_CLI::success( 'Thread was successfully unstarred.' );
		} else {
			WP_CLI::error( 'Something wrong while trying to unstar the thread.' );
		}
	}

	/**
	 * Send a notice.
	 *
	 * ## OPTIONS
	 *
	 * --subject=<subject>
	 * : Subject of the notice/message.
	 *
	 * --content=<content>
	 * : Content of the notice.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp message send-notice --subject="Important notice" --content="We need to improve"
	 *     Success: Notice was successfully sent.
	 *
	 * @alias send-notice
	 */
	public function send_notice( $args, $assoc_args ) {
		$notice            = new \BP_Messages_Notice();
		$notice->subject   = $assoc_args['subject'];
		$notice->message   = $assoc_args['content'];
		$notice->date_sent = bp_core_current_time();
		$notice->is_active = 1;

		// Send it.
		if ( $notice->save() ) {
			WP_CLI::success( 'Notice was successfully sent.' );
		} else {
			WP_CLI::error( 'Notice was not sent.' );
		}
	}
}
