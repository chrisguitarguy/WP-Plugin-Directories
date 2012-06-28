<?php
! defined( 'ABSPATH' ) AND exit();
/*
Plugin Name:  Additional Plugin Directories: Example Plugin
Plugin URI:   http://github.com/chrisguitarguy
Description:  Example Plugin to show how to register additional plugin directories
Version:      0.1
Author:       Franz Josef Kaiser
Author URI:   http://unserkaiser.com
License:      MIT
*/


/**
 * Registers a new plugin directory
 * 
 * @example
 * $args Array (Valid args for `root`) 'content', 'plugins', 'muplugins', 'root'
 * The new directories must be subdirectories of the following WP file system constants:
 * 'content':   (default) WP_CONTENT_DIR
 * 'plugins':   WP_PLUGIN_DIR
 * 'muplugins': WPMU_PLUGIN_DIR
 * 'root':      one level below WP_CONTENT_DIR
 * 
 * @return void
 */
function cd_apd_register_additional_plugin_directories()
{
	// Better abort - if we don't do this, we'll create an error on deactivation of the main plugin.
	if ( ! function_exists( 'register_plugin_directory' ) )
		return;

	// Call the public API function once for every directory you want to add.
	register_plugin_directory( array( 
		 'dir'   => 'example_plugin_directory'
		,'label' => 'Example Label for the list table'
		,'root'  => 'plugins'
	) );
}
// Needs to be added on the `plugins_loaded` hook with a priority of `0`.
add_action( 'plugins_loaded', 'cd_apd_register_additional_plugin_directories', 0 );
