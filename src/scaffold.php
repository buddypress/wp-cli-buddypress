<?php

namespace Buddypress\CLI\Command;

use WP_CLI;
use Scaffold_Command;

/**
 * Scaffold BuddyPress unit tests.
 *
 * ## EXAMPLE
 *
 *     # Scaffold BuddyPress specific tests.
 *     $ wp bp scaffold tests sample-plugin
 *     Success: Created BuddyPress test files.
 *
 * @since 2.0
 */
class Scaffold extends Scaffold_Command {

	/**
	 * Default dependency check for a BuddyPress CLI command.
	 */
	public static function check_dependencies() {
		if ( ! class_exists( 'Buddypress' ) ) {
			WP_CLI::error( 'The BuddyPress plugin is not active.' );
		}
	}

	/**
	 * Plugin scaffold command.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : The slug of the BuddyPress plugin.
	 *
	 * [--force]
	 * : Whether to overwrite files.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp scaffold tests sample-test
	 *     Success: Created BuddyPress test files.
	 *
	 *     $ wp bp scaffold tests another-ssample-test
	 *     Success: Created BuddyPress test files.
	 *
	 * @subcommand tests
	 */
	public function plugin( $args, $assoc_args ) {
		$wp_filesystem = $this->init_wp_filesystem();
		$target_dir    = WP_PLUGIN_DIR . "/{$args[0]}";

		if ( ! is_dir( $target_dir ) ) {
			WP_CLI::error( "Invalid plugin slug specified. No such target directory '{$target_dir}'." );
		}

		$error_msg = $this->check_target_directory( $target_dir );
		if ( ! empty( $error_msg ) ) {
			WP_CLI::error( "Invalid plugin slug specified. {$error_msg}" );
		}

		$to_copy = array(
			'install-bp-tests.sh'      => "{$target_dir}/bin",
			'bootstrap-buddypress.php' => "{$target_dir}/tests",
		);

		foreach ( $to_copy as $file => $dir ) {
			$file_name = "$dir/$file";

			$prompt = WP_CLI\Utils\get_flag_value( $assoc_args, 'force' );

			// Prompt it.
			$should_write_file = $this->prompt_if_files_will_be_overwritten( $file_name, $prompt );

			if ( false === $should_write_file ) {
				continue;
			}

			$files_written[] = $file_name;

			$wp_filesystem->copy( self::get_template_path( $file ), $file_name, true );

			if ( 'install-bp-tests.sh' === $file ) {
				if ( ! $wp_filesystem->chmod( "$dir/$file", 0755 ) ) {
					WP_CLI::warning( "Couldn't mark 'install-bp-tests.sh' as executable." );
				}
			}
		}

		$this->log_whether_files_written(
			$files_written,
			'All BuddyPress test files were skipped.',
			'Created BuddyPress test files.'
		);
	}

	/**
	 * Checks that the `$target_dir` is a child directory of the WP themes or plugins directory, depending on `$type`.
	 *
	 * @param string $type       "theme" or "plugin"
	 * @param string $target_dir The theme/plugin directory to check.
	 *
	 * @return null|string Returns null on success, error message on error.
	 */
	public function check_target_directory( $target_dir ) {
		$parent_dir = dirname( self::canonicalize_path( str_replace( '\\', '/', $target_dir ) ) );

		if ( str_replace( '\\', '/', WP_PLUGIN_DIR ) !== $parent_dir ) {
			return sprintf( 'The target directory \'%1$s\' is not in \'%2$s\'.', $target_dir, WP_PLUGIN_DIR );
		}

		// Success.
		return null;
	}

	/**
	 * Fix path.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	public static function canonicalize_path( $path ) {
		if ( '' === $path || '/' === $path ) {
			return $path;
		}

		if ( '.' === substr( $path, -1 ) ) {
			$path .= '/';
		}

		$output = array();

		foreach ( explode( '/', $path ) as $segment ) {
			if ( '..' === $segment ) {
				array_pop( $output );
			} elseif ( '.' !== $segment ) {
				$output[] = $segment;
			}
		}

		return implode( '/', $output );
	}

	/**
	 * Gets the template path based on installation type.
	 *
	 * @return string Template path.
	 */
	public static function get_template_path( $template ) {
		$command_root  = WP_CLI\Utils\phar_safe_path( dirname( __DIR__ ) );
		$template_path = "{$command_root}/src/templates/{$template}";

		if ( ! file_exists( $template_path ) ) {
			WP_CLI::error( "Couldn't find {$template}" );
		}

		return $template_path;
	}
}
