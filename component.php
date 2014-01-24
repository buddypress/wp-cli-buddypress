<?php

/**
 * Base component class.
 *
 * @since 1.0
 */
class BPCLI_Component {
	final public function run( $method, $args, $assoc_args ) {
		call_user_func_array( array( $this, $method ), array( $args, $assoc_args ) );
	}
}
