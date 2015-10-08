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

// turn debug logging on
// define( 'WP_DEBUG', true );
// define( 'WP_DEBUG_LOG', true );
// define( 'WP_DEBUG_DISPLAY', false );

if( ! WP_DEBUG )
	wpsd_set_wp_debug_true(); 

// if the function doesn't exist, then create it
if( ! function_exists( 'wpsd_log' ) ) {

	/**
	 * Function that does the logging
	 *
	 * @param 	mixed 	: $data - The data to log. Can be String, Object, Array.
	 * @param 	string 	: $name - If passing a variable to $data and you'd like to pass the variable name as text. 
	 * @return 	null
	 * @since 	0.0.1
	 */
	function wpsd_log( $data, $name = '', $file = true, $line = true, $full_backtrace = false ) {

		//
		$backtrace = debug_backtrace();

		//
		if( $full_backtrace ) {
			error_log( print_r( $backtrace, true ) );
			die( 'killed after full backtrace' );
		}
		// 
		if( $file )
			error_log( 'file:'. $backtrace[0][ 'file' ] );

		// 
		if( $line )
			error_log( 'line:'. $backtrace[0][ 'line' ] );

		// if a name is specified, output the name
		if( $name !== '' || ! is_string( $name ) )
			error_log( $name .':' );

		// check for an array or object
		if( is_array( $data ) || is_object( $data ) ) {

			// log it
			error_log( print_r( $data, true ) );
		} else {

			// should be a string, log it
			error_log( $data );
		}
	}
}

// from legolas558 d0t users dot sf dot net at http://www.php.net/is_writable
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
 * Function that 
 *
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
 * Function that 
 *
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
 * Function that 
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
 * Function that 
 *
 * @since 	0.0.1
 */
function wpsd_remove_config_settings_section( $lines ) {

	// our regex
	$search = '|\/\*\*\*\* Begin WP Simple Debug.*End WP Simple Debug \*\*\*\*\/[\n\r]+|mis';

	return preg_replace( $search, '', $lines );
}

/**
 * Function that 
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
 * Function that 
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
 * Function that 
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
 * Function that 
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



// function wpsupercache_uninstall() {
// 	global $wp_cache_config_file, $wp_cache_link, $cache_path;
// 	$files = array( $wp_cache_config_file, $wp_cache_link );
// 	foreach( $files as $file ) {
// 		if ( file_exists( $file ) )
// 			unlink( $file );
// 	}
// 	if ( !function_exists( 'wp_cache_debug' ) )
// 		include_once( 'wp-cache-phase1.php' );
// 	if ( !function_exists( 'prune_super_cache' ) )
// 		include_once( 'wp-cache-phase2.php' );
// 	prune_super_cache( $cache_path, true );
// 	wp_cache_remove_index();
// 	@unlink( $cache_path . '.htaccess' );
// 	@unlink( $cache_path . 'meta' );
// 	@unlink( $cache_path . 'supercache' );
// 	wp_clear_scheduled_hook( 'wp_cache_check_site_hook' );
// 	wp_clear_scheduled_hook( 'wp_cache_gc' );
// 	wp_clear_scheduled_hook( 'wp_cache_gc_watcher' );
// 	wp_cache_disable_plugin();
// 	delete_site_option( 'wp_super_cache_index_detected' );
// }
// register_uninstall_hook( __FILE__, 'wpsupercache_uninstall' );

// function wpsupercache_deactivate() {
// 	global $wp_cache_config_file, $wp_cache_link, $cache_path;
// 	if ( file_exists( $wp_cache_link ) )
// 		unlink( $wp_cache_link );
// 	if ( !function_exists( 'wp_cache_debug' ) )
// 		include_once( 'wp-cache-phase1.php' );
// 	if ( !function_exists( 'prune_super_cache' ) )
// 		include_once( 'wp-cache-phase2.php' );
// 	prune_super_cache( $cache_path, true );
// 	wp_cache_remove_index();
// 	@unlink( $cache_path . '.htaccess' );
// 	@unlink( $cache_path . 'meta' );
// 	@unlink( $cache_path . 'supercache' );
// 	wp_clear_scheduled_hook( 'wp_cache_check_site_hook' );
// 	wp_clear_scheduled_hook( 'wp_cache_gc' );
// 	wp_clear_scheduled_hook( 'wp_cache_gc_watcher' );
// 	wp_cache_replace_line('^ *\$cache_enabled', '$cache_enabled = false;', $wp_cache_config_file);
// 	wp_cache_disable_plugin( false ); // don't delete configuration file
// }
// register_deactivation_hook( __FILE__, 'wpsupercache_deactivate' );

// function wpsupercache_activate() {
// 	wp_schedule_single_event( time() + 10, 'wp_cache_add_site_cache_index' );
// }
// register_activation_hook( __FILE__, 'wpsupercache_activate' );

// function wp_cache_create_advanced_cache() {
// 	global $wp_cache_link, $wp_cache_file;
// 	if ( file_exists( ABSPATH . 'wp-config.php') ) {
// 		$global_config_file = ABSPATH . 'wp-config.php';
// 	} else {
// 		$global_config_file = dirname(ABSPATH) . '/wp-config.php';
// 	}

// 	$line = 'define( \'WPCACHEHOME\', \'' . dirname( __FILE__ ) . '/\' );';
// 	if ( !is_writeable_ACLSafe($global_config_file) || !wp_cache_replace_line('define *\( *\'WPCACHEHOME\'', $line, $global_config_file ) ) {
// 			echo '<div id="message" class="updated fade"><h3>' . __( 'Warning', 'wp-super-cache' ) . "! <em>" . sprintf( __( 'Could not update %s!</em> WPCACHEHOME must be set in config file.', 'wp-super-cache' ), $global_config_file ) . "</h3>";
// 			return false;
// 	}
// 	$ret = true;

// 	$file = file_get_contents( $wp_cache_file );
// 	$fp = @fopen( $wp_cache_link, 'w' );
// 	if( $fp ) {
// 		fputs( $fp, $file );
// 		fclose( $fp );
// 	} else {
// 		$ret = false;
// 	}
// 	return $ret;
// }

/*
function wpsd_replace_line( $find, $replace, $my_file ) {

	// if the file doesn't exist, exit
	if( @is_file( $my_file ) == false )
		return false;
	
	// is it writable?
	if( ! is_writeable_ACLSafe( $my_file ) ) {

		// it is not, display error and return
		echo "Error: file $my_file is not writable.\n";
		return false;
	}

	// 
	$found = false;

	// read in the lines and go through each one
	$lines = file( $my_file );
	foreach( (array) $lines as $line ) {

		// do we have a match? 
	 	if( preg_match( "/$find/", $line ) ) {

	 		// we do, let's go replace it
			$found = true;
			break;
		}
	}

	// if we have a match
	if( $found ) {

		// open the file to write, go through each line
		$fd = fopen($my_file, 'w');
		foreach( (array) $lines as $line ) {

			// if no match
			if( ! preg_match( "/$find/", $line ) ) {

				// just put it back where it was
				fputs( $fd, $line );

			// else we have a match
			} else {

				// so we replace the line
				fputs( $fd, "$replace // Line Modified by WP Simple Debug // $find\n");
			}
		}

		// close and return
		fclose($fd);
		return true;
	}

	// open the file for writing, set var
	$fd 	= fopen( $my_file, 'w' );
	$done 	= false;

	// go through each line
	foreach( (array) $lines as $line ) {

		// 
		if ( $done || !preg_match('/^(if\ \(\ \!\ )?define|\$|\?>/', $line) ) {
			fputs($fd, $line);
		} else {
			fputs($fd, "$replace //Added by WP-Cache Manager\n");
			fputs($fd, $line);
			$done = true;
		}
	}
	fclose($fd);
	return true;
}
*/