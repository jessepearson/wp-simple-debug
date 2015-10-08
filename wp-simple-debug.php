<?php
/*
Plugin Name: WP Simple Debug
Plugin URI: https://github.com/jessepearson/wp-simple-debug
Description: Simple WordPress debugging plugin.
Author: Jesse Pearson
Author URI: https://jessepearson.net/
Text Domain: wp-simple-debug
Domain Path: /languages/
Version: 0.0.1
*/

/**
 * Turn debugging on if it's off
 *
 * @since 	0.0.1
 */
if( ! WP_DEBUG )
	wpsd_set_wp_debug_true(); 

/**
 * Function that does the logging
 *
 * @param 	mixed 	: $data - The data to log. Can be String, Object, Array, etc.
 * @param 	string 	: $note - If passing a variable to $data and you'd like to pass the variable name as text, or other note. 
 * @param 	bool 	: $file - Output the file that's calling the function
 * @param 	bool 	: $line - Output the line number that's calling the function
 * @param 	bool 	: $full_backtrace - Output the full backgtrace on how the function was called. Will die afterwards due to can create excessive output.
 * @return 	null
 * @since 	0.0.1
 */
function wpsd_log( $data, $note = '', $file = true, $line = true, $full_backtrace = false ) {

	// get our backtrace data
	$backtrace = debug_backtrace();

	// if we have a full backtrance
	if( $full_backtrace ) {

		// output the backtrace data and die
		error_log( print_r( $backtrace, true ) );
		die( 'killed after full backtrace' );
	}

	// if the file path should be output, output it 
	if( $file )
		error_log( 'file:'. $backtrace[0][ 'file' ] );

	// if the line number should be output, output it
	if( $line )
		error_log( 'line:'. $backtrace[0][ 'line' ] );

	// if a note is specified, output the note
	if( $note !== '' || ! is_string( $note ) )
		error_log( $note .':' );

	// check for an array or object
	if( is_array( $data ) || is_object( $data ) ) {

		// log it
		error_log( print_r( $data, true ) );
	} else {

		// should be a string, log it
		error_log( $data );
	}
}

/**
 * Function that checks to make sure we can write to a file
 *
 * @param 	string 	: $path - Path to the file we want to test
 * @return 	bool
 * @since 	0.0.1
 * @author 	legolas558 d0t users dot sf dot net at http://www.php.net/is_writable
 */
function is_writeable_ACLSafe( $path ) {

	// PHP's is_writable does not work with Win32 NTFS
	// recursively return a temporary file path
	if( $path{ strlen( $path ) -1 } == '/' ) {
		return is_writeable_ACLSafe( $path . uniqid( mt_rand() ) .'.tmp' );
	} elseif( is_dir( $path ) ) {
		return is_writeable_ACLSafe( $path .'/'. uniqid( mt_rand() ) .'.tmp' );
	}

	// check tmp file for read/write capabilities
	$rm = file_exists( $path );
	$f 	= @fopen( $path, 'a' );
	if( $f === false ) return false;
	fclose( $f );
	if( !$rm ) unlink( $path) ;
	return true;
}

/**
 * Function that will get the lines from the config file
 *
 * @return 	string|bool 	: The contents of the wp-config.php file. False on error.
 * @since 	0.0.1
 */
function wpsd_get_config_file() {

	// get the config file
	$config_file = ( file_exists( ABSPATH . 'wp-config.php' ) ) ? ABSPATH . 'wp-config.php' : dirname( ABSPATH ) . '/wp-config.php';

	// if the file doesn't exist, throw error and exit
	if( @is_file( $config_file ) == false ) {
		add_action( 'admin_notices', 'wpsd_error_no_config_file' );
		return false;
	}
	
	// if not writable, throw error and exit
	if( ! is_writeable_ACLSafe( $config_file ) ) {
		add_action( 'admin_notices', 'wpsd_error_config_not_writable' );
		return false;
	}

	// return the lines
	return file_get_contents( $config_file );
}

/**
 * Function that writes new lines to the config file
 *
 * @param 	string 	: $new_lines - The new content for the file.
 * @return 	bool 	: true on success, false on error.
 * @since 	0.0.1
 */
function wpsd_write_config_file( $new_lines ) {

	// get the config file
	$config_file = ( file_exists( ABSPATH . 'wp-config.php' ) ) ? ABSPATH . 'wp-config.php' : dirname( ABSPATH ) . '/wp-config.php';

	// if the file doesn't exist, throw error and exit
	if( @is_file( $config_file ) == false ) {
		add_action( 'admin_notices', 'wpsd_error_no_config_file' );
		return false;
	}
	
	// if not writable, throw error and exit
	if( ! is_writeable_ACLSafe( $config_file ) ) {
		add_action( 'admin_notices', 'wpsd_error_config_not_writable' );
		return false;
	}

	// return the lines
	$fd = fopen( $config_file, 'w' );
	fputs( $fd, $new_lines );
	fclose( $fd );

	return true;
}

/**
 * Function that sets the debug flag to true
 *
 * @since 	0.0.1
 */
function wpsd_set_wp_debug_true() {

	// get the config file
	if( ! ( $lines = wpsd_get_config_file() ) )
		return;

	// are our settings there for some reason?
	if( preg_match( '/Begin WP Simple Debug/', $lines ) )
		$lines = wpsd_remove_config_settings_section( $lines );

	// is WP_DEBUG_LOG set?
	if( preg_match_all( '/define\(.*WP_DEBUG_LOG.*\).*;/iU', $lines, $matches ) ) {

		// replace the lines
		foreach( $matches as $match ) {
			$lines = str_replace( $match[0], '// '. $match[0] .' // Line modified by WP Simple Debug', $lines );
		}
	}

	// is WP_DEBUG_DISPLAY set?
	if( preg_match_all( '/define\(.*WP_DEBUG_DISPLAY.*\).*;/iU', $lines, $matches ) ) {

		// replace the lines
		foreach( $matches as $match ) {
			$lines = str_replace( $match[0], '// '. $match[0] .' // Line modified by WP Simple Debug', $lines );
		}
	}

	// lets add our settings
	if( preg_match_all( '/define\(.*WP_DEBUG.*false.*\).*;/iU', $lines, $matches ) ) {

		// replace the lines
		foreach( $matches as $match ) {
			$lines = str_replace( $match[0], '// '. $match[0] .' // Line modified by WP Simple Debug', $lines );
		}

		// our settings
		$settings = "/**** Begin WP Simple Debug ****/\n";
		$settings .= "define( 'WP_DEBUG', true );\n";
		$settings .= "define( 'WP_DEBUG_LOG', true );\n";
		$settings .= "define( 'WP_DEBUG_DISPLAY', false );\n";
		$settings .= "/**** End WP Simple Debug ****/\n";
		$settings .= "\n";
		$settings .= "/* That's all, stop editing! Happy blogging. */";

		// put the settings in
		$lines = str_replace( "/* That's all, stop editing! Happy blogging. */", $settings, $lines );
	}	

	// write to the file
	if( wpsd_write_config_file( $lines ) ) {

		// display success
		add_action( 'admin_notices', 'wpsd_notice_update_success' );
	} else {

		// or display error
		add_action( 'admin_notices', 'wpsd_error_config_not_writable' );
	}
}

/**
 * Function that undoes what we've done.
 *
 * @since 	0.0.1
 */
function wpsd_deactivate() {

	// get the config file
	if( ! ( $lines = wpsd_get_config_file() ) )
		return;

	// remove our settings
	if( preg_match( '/Begin WP Simple Debug/', $lines ) )
		$lines = wpsd_remove_config_settings_section( $lines );

	// if we have modified lines
	if( preg_match_all( '/\/\/ (.+) \/\/ Line modified by WP Simple Debug/iU', $lines, $matches ) ) {

		// if we do not have the same count here, something is wrong
		if( count( $matches[0] ) !== count( $matches[1] ) ) {

			// add the error and exit
			add_action( 'admin_notices', 'wpsd_error_cannot_revert' );
			return;
		}

		// go through each match and replace as needed
		foreach( $matches[0] as $k => $match ) {
			$lines = str_replace( $match, $matches[ 1 ][ $k ], $lines );
		}
	}

	/**
	 * make sure debugging is actually off
	 */

	// split the lines up, set flag
	$split 		= preg_split( '/[\n\r]+/', $lines );
	$its_off 	= false;

	// go through each one
	foreach( $split as $k => $line ) {

		// do we have a true statement?
		if( preg_match( '/^\s*define\(.*WP_DEBUG.*true.*\).*;/iU', $line, $matches ) ) {

			// make it false
			$lines = str_replace( $line, "define( 'WP_DEBUG', false );", $lines );

			// it's off
			$its_off = true;
			break;
		}

		// do we have a false statement?
		if( preg_match( '/^\s*define\(.*WP_DEBUG.*false.*\).*;/iU', $line, $matches ) ) {

			// it's off
			$its_off = true;
			break;
		}
	}

	// if it's not off, throw error and exit
	if( ! $its_off ) {

		// add the error and exit
		add_action( 'admin_notices', 'wpsd_error_cannot_revert' );
		return;
	}

	// write to the file
	if( wpsd_write_config_file( $lines ) ) {

		// display success
		add_action( 'admin_notices', 'wpsd_notice_update_success' );
	} else {

		// or display error
		add_action( 'admin_notices', 'wpsd_error_config_not_writable' );
	}
}
register_deactivation_hook( __FILE__, 'wpsd_deactivate' );
register_uninstall_hook( __FILE__, 'wpsd_deactivate' );

/**
 * Function that will remove our settings bloc;
 *
 * @param 	string 	: $lines - The content of the wp-config file.
 * @return 	string 	: The content with the block of settings removed
 * @since 	0.0.1
 */
function wpsd_remove_config_settings_section( $lines ) {

	// our regex
	$search = '|\/\*\*\*\* Begin WP Simple Debug.*End WP Simple Debug \*\*\*\*\/[\n\r]+|mis';

	return preg_replace( $search, '', $lines );
}

/**
 * Function that notifies user wp-config has been updated
 *
 * @since 	0.0.1
 */
function wpsd_notice_update_success() {
	echo '
	<div class="updated">
		<p>
			<strong>WP Simple Debug:</strong> wp-config.php was successfully updated.
		</p>
	</div>';
}

/**
 * Function that notifies user wp-config cannot be reverted
 *
 * @since 	0.0.1
 */
function wpsd_error_cannot_revert() {
	echo '
	<div class="error">
		<p>
			<strong>WP Simple Debug:</strong> Not able to remove settings from wp-config.php file.
		</p>
		<p>
			Please make sure the below lines are removed from your wp-config.php file:<br/>
			/**** Begin WP Simple Debug ****/<br/>
			define( \'WP_DEBUG\', true );<br/>
			define( \'WP_DEBUG_LOG\', true );<br/>
			define( \'WP_DEBUG_DISPLAY\', false );<br/>
			/**** End WP Simple Debug ****/<br/>
		</p>
		<p>
			You will also need to make sure this line is added after the debug section:<br/>
			define( \'WP_DEBUG\', false );<br/>
		</p>
	</div>';
}

/**
 * Function that notifies user wp-config cannot be found
 *
 * @since 	0.0.1
 */
function wpsd_error_no_config_file() {
	echo '
	<div class="error">
		<p>
			<strong>WP Simple Debug:</strong> Not able to locate wp-config.php file.
		</p>
		<p>
			Please make sure the below settings are added to your wp-config.php file, or else debugging will not work:<br/>
			define( \'WP_DEBUG\', true );<br />
			define( \'WP_DEBUG_LOG\', true );<br />
			define( \'WP_DEBUG_DISPLAY\', false );<br />
		</p>
	</div>';
}

/**
 * Function that notifies user wp-config cannot be updated
 *
 * @since 	0.0.1
 */
function wpsd_error_config_not_writable() {
	echo '
	<div class="error">
		<p>
			<strong>WP Simple Debug:</strong> It appears wp-config.php is not writable.
		</p>
		<p>
			Please make sure the below settings are added to your wp-config.php file, or else debugging will not work:<br/>
			define( \'WP_DEBUG\', true );<br />
			define( \'WP_DEBUG_LOG\', true );<br />
			define( \'WP_DEBUG_DISPLAY\', false );<br />
		</p>
	</div>';
}