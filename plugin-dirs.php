<?php
! defined( 'ABSPATH' ) && exit();
/*
Plugin Name: Additional Plugin Directories
Plugin URI:  http://github.com/chrisguitarugy
Description: A framework for adding additional plugin directories to WordPress
Version:     0.1
Author:      Christopher Davis
Author URI:  http://christopherdavis.me
License:     GPL2
*/


define( 'CD_APD_PATH', plugin_dir_path( __FILE__ ) );
define( 'CD_APD_URL', plugin_dir_url( __FILE__ ) );

require_once( CD_APD_PATH . 'inc/core.php' );

if( is_admin() )
{
	require_once( CD_APD_PATH . 'inc/admin.php' );
}
