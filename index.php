<?php
! defined( 'ABSPATH' ) AND exit();
/*
Plugin Name:  Additional Plugin Directories
Plugin URI:   http://github.com/chrisguitarguy
Description:  A framework to allow adding additional plugin directories to WordPress
Version:      0.7.1
Author:       Christopher Davis
Contributors: Franz Josef Kaiser, Julien Chaumond
Author URI:   http://christopherdavis.me
License:      GPL2
*/


define( 'CD_APD_PATH', plugin_dir_path( __FILE__ ) );
define( 'CD_APD_URL', plugin_dir_url( __FILE__ ) );

require_once( CD_APD_PATH.'inc/api.php' );
require_once( CD_APD_PATH.'inc/core.php' );

if ( is_admin() )
{
	require_once( CD_APD_PATH.'inc/admin.php' );
}