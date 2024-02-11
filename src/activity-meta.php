<?php

namespace Buddypress\CLI\Command;

use WP_CLI\CommandWithMeta;

/**
 * Adds, updates, deletes, and lists activity custom fields.
 *
 * ## EXAMPLES
 *
 *     # Set activity meta
 *     $ wp bp activity meta set 123 description "Mary is a activity user."
 *     Success: Updated custom field 'description'.
 *
 *     # Get activity meta
 *     $ wp bp activity meta get 123 description
 *     Mary is a Activity user.
 *
 *     # Update activity meta
 *     $ wp bp activity meta update 123 description "Mary is an awesome activity user."
 *     Success: Updated custom field 'description'.
 *
 *     # Delete activity meta
 *     $ wp bp activity meta delete 123 description
 *     Success: Deleted custom field.
 *
 * @since 2.0.0
 */
class Activity_Meta extends CommandWithMeta {

	/**
	 * Type of the meta.
	 *
	 * @var string
	 */
	protected $meta_type = 'activity';

	/**
	 * Wrapper method for add_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id  ID of the object the metadata is for.
	 * @param string $meta_key   Metadata key to use.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param bool   $unique     Optional, default is false. Whether the
	 *                           specified metadata key should be unique for the
	 *                           object. If true, and the object already has a
	 *                           value for the specified metadata key, no change
	 *                           will be made.
	 *
	 * @return int|false The meta ID on success, false on failure.
	 */
	protected function add_metadata( $object_id, $meta_key, $meta_value, $unique = false ) {
		return bp_activity_add_meta( $object_id, $meta_key, $meta_value );
	}

	/**
	 * Wrapper method for update_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id  ID of the object the metadata is for.
	 * @param string $meta_key   Metadata key to use.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param mixed  $prev_value Optional. If specified, only update existing
	 *                           metadata entries with the specified value.
	 *                           Otherwise, update all entries.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful
	 *                  update, false on failure.
	 */
	protected function update_metadata( $object_id, $meta_key, $meta_value, $prev_value = '' ) {
		return bp_activity_update_meta( $object_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Wrapper method for get_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id ID of the object the metadata is for.
	 * @param string $meta_key  Optional. Metadata key. If not specified,
	 *                          retrieve all metadata for the specified object.
	 * @param bool   $single    Optional, default is false. If true, return only
	 *                          the first value of the specified meta_key. This
	 *                          parameter has no effect if meta_key is not
	 *                          specified.
	 *
	 * @return mixed Single metadata value, or array of values.
	 */
	protected function get_metadata( $object_id, $meta_key = '', $single = true ) {
		return bp_activity_get_meta( $object_id, $meta_key, $single );
	}

	/**
	 * Wrapper method for delete_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id  ID of the object metadata is for
	 * @param string $meta_key   Metadata key
	 * @param mixed $meta_value  Optional. Metadata value. Must be serializable
	 *                           if non-scalar. If specified, only delete
	 *                           metadata entries with this value. Otherwise,
	 *                           delete all entries with the specified meta_key.
	 *                           Pass `null, `false`, or an empty string to skip
	 *                           this check. For backward compatibility, it is
	 *                           not possible to pass an empty string to delete
	 *                           those entries with an empty string for a value.
	 *
	 * @return bool True on successful delete, false on failure.
	 */
	protected function delete_metadata( $object_id, $meta_key, $meta_value = '' ) {
		return bp_activity_delete_meta( $object_id, $meta_key, $meta_value );
	}

	/**
	 * Check that the activity ID exists.
	 *
	 * @param int $object_id Object ID.
	 * @return int
	 */
	protected function check_object_id( $object_id ) {
		$fetcher  = new Activity_Fetcher();
		$activity = $fetcher->get_check( $object_id );

		return $activity->id;
	}
}
