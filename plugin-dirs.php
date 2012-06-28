<?php
! defined( 'ABSPATH' ) AND exit();
/*
Plugin Name:  Additional Plugin Directories
Plugin URI:   http://github.com/chrisguitarguy
Description:  A framework to allow adding additional plugin directories to WordPress
Version:      0.8
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
	}
} // END Class CD_APD_Bootstrap

} // endif;