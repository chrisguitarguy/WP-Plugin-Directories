<?php
! defined( 'ABSPATH' ) AND exit();



if ( ! class_exists( 'CD_APD_Core' ) )
{

/**
 * Core
 * 
 * @author     Christopher Davis, Franz Josef Kaiser
 * @license    MIT
 * @copyright  © Christopher Davis, Franz Josef Kaiser 2011-2012
 * 
 * @package    WordPress
 * @subpackage Additional Plugin Directories: Core
 */
class CD_APD_Core
{
	/**
	 * 
	 */
	public function __construct()
	{
		add_action( 'plugins_loaded', array( $this, 'load_plugins' ), 99 );
	}


	/**
	 * Loads additional plugins from custom directories. 
	 * To add a directory, you must do so in a plugin (hooked into `plugins_loaded` with a low priority).
	 * 
	 * @since  0.1
	 * @return void
	 */
	public function load_plugins()
	{
		global $wp_plugin_directories;
	
		empty( $wp_plugin_directories ) AND $wp_plugin_directories = array();
	
		foreach ( array_keys( $wp_plugin_directories ) as $key )
		{
			$active = get_option( "active_plugins_{$key}", array() );
			foreach( $active as $a )
			{
				if ( file_exists( "{$wp_plugin_directories[ $key ]['dir']}/{$a}" ) )
				{
					include_once( "{$wp_plugin_directories[ $key ]['dir']}/{$a}" );
				}
			}
		}
	}


	/**
	 * Get the valid plugins from the custom directory
	 * 
	 * @since  0.1
	 * @param  $dir_key The `key` of our custom plugin directory
	 * @return array A list of the plugins
	 */
	public function get_plugins_from_cache( $dir_key ) 
	{
		global $wp_plugin_directories;

		// invalid dir key? bail
		if ( ! isset( $wp_plugin_directories[ $dir_key ] ) )
			return array();

		$plugin_root = $wp_plugin_directories[ $dir_key ]['dir'];

		if ( ! $cache_plugins = wp_cache_get( 'plugins', 'plugins') )
			$cache_plugins = array();

		if ( isset( $cache_plugins[ $dir_key ] ) )
			return $cache_plugins[ $dir_key ];

		$wp_plugins = array();

		$plugins_dir = @ opendir( $plugin_root );
		$plugin_files = array();
		if ( $plugins_dir )
		{
			while ( false !== ( $file = readdir( $plugins_dir ) ) )
			{
				if ( '.' === substr( $file, 0, 1 ) )
					continue;

				if ( is_dir( "{$plugin_root}/{$file}" ) )
				{
					$plugins_subdir = @ opendir( "{$plugin_root}/{$file}" );
					if ( $plugins_subdir )
					{
						while ( ( $subfile = readdir( $plugins_subdir ) ) !== false )
						{
							if ( '.' === substr( $subfile, 0, 1 ) )
								continue;

							if ( '.php' === substr( $subfile, -4 ) )
								$plugin_files[] = "{$file}/{$subfile}";
						}
						closedir( $plugins_subdir );
					}
				}
				else
				{
					'.php' === substr( $file, -4 ) AND $plugin_files[] = $file;
				}
			}
			closedir( $plugins_dir );
		}

		if ( empty( $plugin_files ) )
			return $wp_plugins;

		foreach ( $plugin_files as $plugin_file )
		{
			if ( ! is_readable( "{$plugin_root}/{$plugin_file}" ) )
				continue;

			// Do not apply markup/translate as it'll be cached.
			$plugin_data = get_plugin_data( "{$plugin_root}/{$plugin_file}", false, false );

			if ( empty ( $plugin_data['Name'] ) )
				continue;

			$wp_plugins[ trim( $plugin_file ) ] = $plugin_data;
		}

		uasort( $wp_plugins, '_sort_uname_callback' );

		// Setup cache, if we ain't already got one.
		// If we got one, we already returned the cached plugins.
		$cache_plugins[ $dir_key ] = $wp_plugins;
		wp_cache_set( 'plugins', $cache_plugins, 'plugins' );

		return $wp_plugins;
	}


	/**
	 * Custom plugin activation function.
	 * 
	 * @since  0.1
	 * @return void
	 */
	public function activate_plugin( $plugin, $context, $silent = false ) 
	{
		$plugin = trim( $plugin );

		$redirect = add_query_arg( 'plugin_status', $context, admin_url( 'plugins.php' ) );
		$redirect = apply_filters( 'custom_plugin_redirect', $redirect );

		$current = get_option( "active_plugins_{$context}", array() );

		$valid = $this->validate_plugin( $plugin, $context );

		if ( is_wp_error( $valid ) )
			return $valid;

		if ( ! in_array( $plugin, $current ) )
		{
			if ( ! empty( $redirect ) )
			{
				// we'll override this later if the plugin can be included without fatal error
				wp_redirect( add_query_arg( 
					 '_error_nonce'
					,wp_create_nonce( "plugin-activation-error_{$plugin}" )
					,$redirect 
				) );
			}

			ob_start();
			include_once( $valid );

			if ( ! $silent )
			{
				do_action( 'custom_activate_plugin', $plugin, $context );
				do_action( "custom_activate_{$plugin}", $context );
			}

			$current[] = $plugin;
			sort( $current );
			update_option( "active_plugins_{$context}", $current );

			if ( ! $silent )
			{
				do_action( 'custom_activated_plugin', $plugin, $context );
			}

			if ( ob_get_length() > 0 )
			{
				$output = ob_get_clean();
				return new WP_Error( 'unexpected_output', __( 'The plugin generated unexpected output.' ), $output );
			}
			ob_end_clean();
		}

		return true;
	}


	/**
	 * Deactivate custom plugins
	 * 
	 * @since  0.1
	 * @return void
	 */
	public function deactivate_plugins( $plugins, $context, $silent = false )
	{
		$current = get_option( "active_plugins_{$context}", array() );

		foreach ( (array) $plugins as $plugin ) 
		{
			$plugin = trim( $plugin );
			if ( ! in_array( $plugin, $current ) ) continue;

			if ( ! $silent )
				do_action( 'custom_deactivate_plugin', $plugin, $context );

			$key = array_search( $plugin, $current );
			if ( false !== $key )
			{
				array_splice( $current, $key, 1 );
			}

			if ( ! $silent )
			{
				do_action( "custom_deactivate_{$plugin}", $context );
				do_action( 'custom_deactivated_plugin', $plugin, $context );
			}
		}

		update_option( "active_plugins_{$context}", $current );
	}


	/**
	 * Checks to see whether the plugin and is valid and can be activated.
	 * 
	 * @uses validate_file To make sure the plugin name is okay.
	 * @param  string $plugin
	 * @return array $context
	 * @return WP_Error|string WP_Error object on failure, the plugin to include on success.
	 */
	public function validate_plugin( $plugin, $context ) 
	{
		$rv = true;
		if ( validate_file( $plugin ) )
		{
			$rv = new WP_Error( 'plugin_invalid', __( 'Invalid plugin path.' ) );
		}

		global $wp_plugin_directories;

		if ( ! isset( $wp_plugin_directories[ $context ] ) )
		{
			$rv = new WP_Error( 'invalid_context', __( 'The context for this plugin does not exist' ) );
		}
	
		$dir = $wp_plugin_directories[ $context ]['dir'];
		if ( ! file_exists( "{$dir}/{$plugin}" ) )
		{
			$rv = new WP_Error( 'plugin_not_found', __( 'Plugin file does not exist.' ) );
		}

		$installed_plugins = $this->get_plugins_from_cache( $context );
		if ( ! isset( $installed_plugins[ $plugin ] ) )
		{
			$rv = new WP_Error( 'no_plugin_header', __('The plugin does not have a valid header.') );
		}

		$rv = "{$dir}/{$plugin}";

		return $rv;
	}
} // END Class CD_APD_Core

} // endif;
