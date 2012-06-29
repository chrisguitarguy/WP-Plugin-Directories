<?php
! defined( 'ABSPATH' ) AND exit();
/*
Plugin Name:  Additional Plugin Directories
Plugin URI:   http://github.com/chrisguitarguy
Description:  A framework to allow adding additional plugin directories to WordPress
Version:      0.9
Author:       Christopher Davis
Contributors: Franz Josef Kaiser, Julien Chaumond
Author URI:   http://christopherdavis.me
License:      GNU GPL 2
*/



// Avoid loading twice
if ( ! class_exists( 'dmb_bootstrap' ) )
{
	add_action( 'plugins_loaded', array( 'CD_APD_Bootstrap', 'init' ), 5 );

/**
 * Bootstrap for delayed Meta Boxes
 * 
 * @author     Franz Josef Kaiser, Christopher Davis
 * @license    GNU GPL 2
 * @copyright  © Franz Josef Kaiser, Christopher Davis 2011-2012
 * 
 * @package    WordPress
 * @subpackage Additional Plugin Directories: Bootstrap
 */
class CD_APD_Bootstrap
{
	/**
	 * Instance
	 * 
	 * @access protected
	 * @var object
	 */
	static protected $instance;


	/**
	 * The files that need to get included
	 * 
	 * @since     0.8
	 * @access    public
	 * @static
	 * @var array string Class Name w/o prefix (Hint: Naming convention!) Use the value to define if need to hook the class.
	 */
	static public $includes = array(
		 'api'   => false
		,'core'  => false
		,'admin' => true
	);


	/**
	 * Used for update notices
	 * Fetches the readme file from the official plugin repo trunk.
	 * Adds to the "in_plugin_update_message-$file" hook
	 * 
	 * @var (string)
	 */
	public $remote_changelog = 'https://raw.github.com/chrisguitarguy/WP-Plugin-Directories/master/changelog.txt';


	/**
	 * Creates a new static instance
	 * 
	 * @since  0.8
	 * @static
	 * @return void
	 */
	static public function init()
	{
		null === self :: $instance AND self :: $instance = new self;
		return self :: $instance;
	}


	/**
	 * Constructor
	 * 
	 * @since  0.8
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		// Localize
		load_theme_textdomain( 'cd_apd_textdomain', plugin_dir_path( __FILE__ )."lang" );

		// Load at the end of /wp-admin/admin.php
		foreach ( self :: $includes as $inc => $init )
		{
			// Load file: trailingslashed by core
			# Tested: calling plugin_dir_path() directly saves 1/2 time 
			# instead of saving the plugin_dir_path() in a $var and recalling here
			require_once plugin_dir_path( __FILE__ )."inc/{$inc}.php";

			if ( ! $init )
				continue;

			// Build class name
			$class = "CD_APD_".ucwords( $inc );

			class_exists( $class ) AND add_action( 'plugins_loaded', array( $class, 'instance' ) );
		}

		// Better update message
		$folder	= basename( dirname( __FILE__ ) );
		$file	= basename( __FILE__ );
		$hook = "in_plugin_update_message-{$folder}/{$file}";
		add_action( $hook, array( $this, 'update_message' ), 20, 2 );
	}


	/**
	 * Displays an update message for plugin list screens.
	 * Shows only the version updates from the current until the newest version
	 * 
	 * @uses WordPress HTTP API
	 * 
	 * @since  0.9
	 * @param  array  $plugin_data Data of the plugin itself
	 * @param  object $r           Data of the remote request to the repo
	 * @return string The actual Output message
	 */
	public function update_message( $plugin_data, $r )
	{
		if ( 'plugins.php' !== $GLOBALS['pagenow'] )
			return;

		// Get `changelog.txt` from GitHub via WP HTTP API
		$changelog = wp_remote_get( 
			 $this->remote_changelog
			,array(
				// We can't force anyone to alter the `~/.ssh/config` on the server
				'sslverify' => false 
			 ) 
		);

		// Die silently
		if ( is_wp_error( $changelog ) )
			return;

		// Only retrieve what's new since the installed version
		$details = explode( 
			 '*'
			,stristr( 
				 $changelog['body']
				,"*{$plugin_data['Version']}*" 
			) 
		);
		// Build the update note
		$whats_new = '';
		for ( $i = 0; $i < count( $details ); $i++ )
		{
			$whats_new .= ( 0 != $i % 2 ) ? "<strong>{$details[ $i ]}" : "</strong><br />{$details[ $i ]}";
		}

		return printf(
			 "%sThe Update from %s to %s brings you the following new features, bug fixes and additions.%s"
			,'<hr />'
			,"<code>{$plugin_data['Version']}</code>"
			,"<code>{$r->new_version}</code>"
			,"<p style='font-weight:normal;'>{$whats_new}</p>"
		);
	}
} // END Class CD_APD_Bootstrap

} // endif;