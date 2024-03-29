<?php

namespace Buddypress\CLI\Command;

use WP_CLI\CommandWithMeta;

/**
 * Adds, updates, deletes, and lists group custom fields.
 *
 * ## EXAMPLES
 *
 *     # Set group meta
 *     $ wp bp group meta set 123 description "Mary is a Group user."
 *     Success: Updated custom field 'description'.
 *
 *     # Get group meta
 *     $ wp bp group meta get 123 description
 *     Mary is a Group user.
 *
 *     # Update group meta
 *     $ wp bp group meta update 123 description "Mary is an awesome Group user."
 *     Success: Updated custom field 'description'.
 *
 *     # List group meta.
 *     $ wp bp group meta list 123
 *
 *     # Delete group meta
 *     $ wp bp group meta delete 123 description
 *     Success: Deleted custom field.
 *
 * @since 2.0.0
 */
class Group_Meta extends CommandWithMeta {
	protected $meta_type = 'group';

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
		return groups_add_groupmeta( $object_id, $meta_key, $meta_value );
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
		return groups_update_groupmeta( $object_id, $meta_key, $meta_value, $prev_value );
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
		return groups_get_groupmeta( $object_id, $meta_key, $single );
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
		return groups_delete_groupmeta( $object_id, $meta_key, $meta_value );
	}

	/**
	 * Check that the group ID exists.
	 *
	 * @param int $object_id Object ID.
	 * @return int
	 */
	protected function check_object_id( $object_id ) {
		$fetcher = new Group_Fetcher();
		$group   = $fetcher->get_check( $object_id );

		return $group->id;
	}
}
