<?php
! defined( 'ABSPATH' ) AND exit();



/**
 * The central API
 * 
 * @author     Christopher Davis, Franz Josef Kaiser
 * @license    GPL
 * @copyright  © Christopher Davis, Franz Josef Kaiser 2011-2012
 * 
 * @since      0.3
 * @package    Additional Plugin directories
 * @subpackage The central API
 */


/**
 * Registers a new plugin directory.
 * 
 * @since  0.1
 * @uses   _get_new_plugin_directory_root()
 * @param  array  $args             An Array of arguments: 'dir' = Name of the directory, 'label' = What you read above the list table, 'case' = Where the dir resides.  
 * @param  string $deprecated_dir   (deprecated arg) The new plugin directory. Either a full path or a folder name within wp-content.
 * @param  string $deprecated_label (deprecated arg) The nice name of the plugin directory. Presented in the list table.
 * @return bool                     TRUE on success, FALSE in case the $key/$label is already in use.
 */
function register_plugin_directory( $args, $deprecated_dir = '', $deprecated_label = '' )
{
	// The call was too late (or too early in case of a MU-Plugin)
	if ( 'plugins_loaded' !== current_filter() )
	{
		_doing_it_wrong( 
			 __FUNCTION__
			,__( 'Registering a new plugin directory should be done during the `plugins_loaded` hook on priority `0`.', 'cd_apd_textdomain' )
			,'0.1'
		);
	}

	// Deprecating single arguments
	if ( ! is_array( $args ) )
	{
		$info = debug_backtrace();
		// The key now gets built from the label: Converted to lowercase alphanumeric string.
		_deprecated_argument( 
			 __FUNCTION__
			,'0.3'
			,sprintf( 
				 __( "%sYou need to specify the arguments &ndash; when registering new plugin directories &ndash; as associative array.%s%s", 'cd_apd_textdomain' )
				,'<br /><blockquote><strong>'
				,'</strong></blockquote>'
				,"The call was from within: <code>{$info[ 0 ]['file']}</code> in the function: <code>{$info[ 1 ]['function']}()</code>.<br />"
			)
		);

		// Fix for back compat
		$args = array(
			 'dir'   => $deprecated_dir
			,'label' => $deprecated_label
		);
	}

	// Setup defaults
	$args = wp_parse_args( $args, array( 'root' => 'content' ) );

	global $wp_plugin_directories;

	empty( $wp_plugin_directories ) AND $wp_plugin_directories = array();

	$new_dir = _get_new_plugin_directory_root( $args['root'] ).$args['dir'];

	if ( ! file_exists( $args['dir'] ) AND file_exists( $new_dir ) )
	{
		$args['dir'] = $new_dir;
	}

	// Build $key from $label
	$key = strtolower( preg_replace( "/[^a-zA-Z0-9\s]/", "", $args['label'] ) );

	// Return FALSE in case we already got the key
	if ( isset( $wp_plugin_directories[ $key ] ) )
		return false;

	// Assign the directory
	$wp_plugin_directories[ $key ] = array(
		 'dir'   => $args['dir']
		,'label' => $args['label']
		,'root'  => $args['root']
	);

	return true;
}


/**
 * Retrieves the root path for the new plugin directory.
 * 
 * @internal Callback function for register_plugin_directory()
 * 
 * @since    0.3
 * @param    string $case Valid: 'content', 'plugins', 'muplugins', 'root'.
 * @return   string $root The root path based on the WP filesystem constants.
 */
function _get_new_plugin_directory_root( $root )
{
	switch ( $root )
	{
		case 'plugins' :
			$root = WP_PLUGIN_DIR;
			break;

		case 'muplugins' :
			$root = WPMU_PLUGIN_DIR;
			break;

		// Experimental Edge Case: 
		// Assuming that the WP_CONTENT_DIR is a direct child of the root directory
		// and directory separators are "/" above that. 
		// Maybe needs enchancements later on. Wait for feedback in Issues.
		case 'root' :
			$root = explode( DIRECTORY_SEPARATOR, WP_CONTENT_DIR );
			$root = explode( '/', array_pop( $root ) );
			$root = array_pop( $root );
			break;

		case 'content' :
			$root = WP_CONTENT_DIR;
			break;

		default :
			$root = apply_filters( "adp_root_{$root}", WP_CONTENT_DIR );
			break;
	}

	return trailingslashit( $root );
}
