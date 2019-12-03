<?php
/** Step 2 (from text above). */

if ( is_admin() ){ // admin actions
  add_action( 'admin_menu', 'my_plugin_menu' );
  add_action( 'admin_init', 'register_mysettings' );
} 
else {
  // non-admin enqueues, actions, and filters
}
/*
	create and store in database our set of options  
*/
function register_mysettings() { // whitelist options
  register_setting( 'myoption-group', 'new_option_name' );
  register_setting( 'myoption-group', 'some_other_option' );
  register_setting( 'myoption-group', 'option_etc' );
}

/** Step 1. */
function my_plugin_menu() {
	//add_menu_page('My Cool Plugin Settings', 'Cool Settings', 'administrator', __FILE__, 'my_cool_plugin_settings_page', get_stylesheet_directory_uri('stylesheet_directory')."/images/media-button-other.gif");
	add_options_page( 'limitPagesNumber Options', 'limitPagesNumber', 'manage_options', __FILE__, 'my_plugin_options');
}

/** Step 3. */
function my_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	include "simurghe_options.php";
}
?>