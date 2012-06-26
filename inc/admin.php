<?php
! defined( 'ABSPATH' ) && exit();


class CD_APD_Admin
{
	/**
	 * The container for all of our custom plugins
	 */
	protected $plugins = array();

	/**
	 * What custom actions are we allowed to handle here?
	 */
	protected $actions = array();

	/**
	 * The original count of the plugins
	 */
	protected $all_count = 0;

	/**
	 * constructor
	 * 
	 * @since 0.1
	 */
	public function __construct()
	{
		add_action( 'load-plugins.php', array( $this, 'init' ) );
		add_action( 'plugins_loaded', array( $this, 'setup_actions' ), 1 );
	}


	/**
	 * Sets up which actions we can handle with this plugin. We'll use this
	 * to catch activations and deactivations as the normal way won't work
	 * 
	 * @since 0.1
	 */
	public function setup_actions()
	{
		$tmp = array(
			'custom_activate',
			'custom_deactivate'
		);
		$this->actions = apply_filters( 'custom_plugin_actions', $tmp );
	}


	/**
	 * Makes the magic happen.  Loads all the other hooks to modify the
	 * plugin list table
	 * 
	 * @since 0.1
	 */
	public function init()
	{
		global $wp_plugin_directories;

		$screen = get_current_screen();

		$this->get_plugins();

		$this->handle_actions();

		add_filter( 'views_' . $screen->id, array( &$this, 'views' ) );

		// check to see if we're using one of our custom directories
		if ( $this->get_plugin_status() )
		{
			add_filter( 'views_' . $screen->id, array( &$this, 'views_again' ) );
			add_filter( 'all_plugins', array( &$this, 'filter_plugins' ) );
			// TODO: support bulk actions
			add_filter( 'bulk_actions-' . $screen->id, '__return_empty_array' );
			add_filter( 'plugin_action_links', array( &$this, 'action_links' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( &$this, 'scripts' ) );
		}
	}


	/**
	 * Adds our custom plugin directories to the list of plugin types
	 *
	 * @since 0.1
	 */
	public function views( $views )
	{
		global $wp_plugin_directories;

		// bail if we don't have any extra dirs
		if ( empty( $wp_plugin_directories ) ) return $views;

		// Add our directories to the action links
		foreach ( $wp_plugin_directories as $key => $info )
		{
			if ( ! count( $this->plugins[$key] ) ) continue;
			$class = $this->get_plugin_status() == $key ? ' class="current" ' : '';
			$views[$key] = sprintf( 
				'<a href="%s"' . $class . '>%s <span class="count">(%d)</span></a>',
				add_query_arg( 'plugin_status', $key, 'plugins.php' ),
				esc_html( $info['label'] ),
				count( $this->plugins[$key] )
			);
		}
		return $views;
	}


	/**
	 * Unset inactive plugin link as it doesn't really work for this view
	 */
	public function views_again( $views )
	{
		if ( isset( $views['inactive'] ) ) unset( $views['inactive'] );
		return $views;
	}


	/**
	 * Filters the plugins list to include all the plugins in our custom directory
	 */
	public function filter_plugins( $plugins )
	{
		if ( $key = $this->get_plugin_status() )
		{
			$this->all_count = count( $plugins );
			$plugins = $this->plugins[$key];
		}
		return $plugins;
	}


	/**
	 * Correct some action links so we can actually "activate" plugins
	 */
	public function action_links( $links, $plugin_file )
	{
		$context = $this->get_plugin_status();

		// let's just start over
		$links = array();
		$links['activate'] = sprintf(
			'<a href="%s" title="Activate this plugin">%s</a>',
			wp_nonce_url( 'plugins.php?action=custom_activate&amp;plugin=' . $plugin_file . '&amp;plugin_status=' . esc_attr( $context ), 'custom_activate-' . $plugin_file ),
			__( 'Activate' )
		);

		$active = get_option( 'active_plugins_' . $context, array() );
		if ( in_array( $plugin_file, $active ) )
		{
			$links['deactivate'] = sprintf(
				'<a href="%s" title="Deactivate this plugin" class="cd-apd-deactivate">%s</a>',
				wp_nonce_url( 'plugins.php?action=custom_deactivate&amp;plugin=' . $plugin_file . '&amp;plugin_status=' . esc_attr( $context ), 'custom_deactivate-' . $plugin_file ),
				__( 'Deactivate' )
			);
		}
		return $links;
	}


	/**
	 * Enqueues on JS file for fun hacks
	 * 
	 * @since 0.1
	 * @uses wp_enqueue_script
	 */
	public function scripts()
	{
		wp_enqueue_script(
			'cd-apd-js',
			CD_APD_URL . 'js/apd.js',
			array( 'jquery' ),
			null
		);
		wp_localize_script(
			'cd-apd-js',
			'cd_apd',
			array(
				'count' => esc_js( $this->all_count )
			)
		);
	}


	/**
	 * Fetch all the custom plugins we have!
	 * 
	 * @since 0.1
	 * @uses cd_adp_get_plugins To fetch all the custom plugins
	 */
	public function get_plugins()
	{
		global $wp_plugin_directories;
		if ( empty( $wp_plugin_directories ) ) $wp_plugin_directories = array();
		foreach ( array_keys( $wp_plugin_directories ) as $key )
		{
			$this->plugins[$key] = cd_apd_get_plugins( $key );
		}
	}


	/**
	 * Handle activations and deactivations as the standard way will
	 * fail with "plugin file does not exist
	 *
	 * @since 0.1
	 */
	public function handle_actions()
	{
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

		// not allowed to handle this action? bail.
		if ( ! in_array( $action, $this->actions ) ) return;

		// Get the plugin we're going to activate
		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : false;
		if ( ! $plugin ) return;

		$context = $this->get_plugin_status();

		switch( $action )
		{
			case 'custom_activate':
				if ( ! current_user_can('activate_plugins') )
					wp_die( __('You do not have sufficient permissions to manage plugins for this site.') );

				check_admin_referer( 'custom_activate-' . $plugin );

				$result = cd_apd_activate_plugin( $plugin, $context );
				if ( is_wp_error( $result ) ) 
				{
					if ( 'unexpected_output' == $result->get_error_code() ) 
					{
						$redirect = add_query_arg( 'plugin_status', $context, self_admin_url( 'plugins.php' ) );
						wp_redirect( add_query_arg( '_error_nonce', wp_create_nonce( 'plugin-activation-error_' . $plugin ), $redirect ) ) ;
						exit();
					}
					else 
					{
						wp_die( $result );
					}
				}

				wp_redirect( add_query_arg( array( 'plugin_status' => $context, 'activate' => 'true' ), self_admin_url( 'plugins.php' ) ) );
				exit();
				break;

			case 'custom_deactivate':
				if ( ! current_user_can( 'activate_plugins' ) )
					wp_die( __('You do not have sufficient permissions to deactivate plugins for this site.') );

				check_admin_referer('custom_deactivate-' . $plugin);
				cd_apd_deactivate_plugins( $plugin, $context );
				if ( headers_sent() )
					echo "<meta http-equiv='refresh' content='" . esc_attr( "0;url=plugins.php?deactivate=true&plugin_status=$status&paged=$page&s=$s" ) . "' />";
				else
					wp_redirect( self_admin_url("plugins.php?deactivate=true&plugin_status=$context") );
				exit();
				break;

			default:
				do_action( 'custom_plugin_dir_' . $action );
				break;
		}

	}


	/**
	 * Utility function to get the current `plugin_status` key returns 
	 * false if our key isn't in the the custom directories
	 * 
	 * @since 0.1
	 * @return bool|string False on failure, the `$wp_plugin_directories` key on success
	 */
	public function get_plugin_status()
	{
		global $wp_plugin_directories;
		$rv = false;
		if ( isset( $_GET['plugin_status'] ) && in_array( $_GET['plugin_status'], array_keys( $wp_plugin_directories ) ) )
		{
			$rv = $_GET['plugin_status'];
		}
		return $rv;
	}
} // end class

new CD_APD_Admin();
