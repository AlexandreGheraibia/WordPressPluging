<?php
/**
* Plugin Name: limit pages published
* Plugin URI: http://www.mywebsite.com/my-first-plugin
* Description: The very first plugin that I have ever created.
* For limit the page number of a user by his role
* Version: 1.0
* Requires at least: 5.3
* Requires PHP:      7.3.12
* Author: Gheraibia Alexandre
* Author URI: http://www.mywebsite.com
*/
//include 'simurghe_options.php';
include 'simurghe_menu.php';

/*source:https://codex.wordpress.org/Writing_a_Plugin
Security reason
blocking direct access to the plug-in
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
 
 class BoundsPages {
  /*
	Apparently it's a good practice to pass by an init function.
	For avoid the coupling between wordpress and the custom class
  */
  public static function init(){
	   $self = new self();
	   add_action( 'edit_post', array($self, 'page_published_limit'));
  }
  
  public function display($args){
	  error_log($args);
  }
  
  public function page_published_limit($postID)  {
		//global $post;
		if(!empty($postID)){
		//$author=get_post($postID); 	
		$authorID = get_current_user_id();//$author->post_author; // Post author ID.
		/*to do
			how retrieve my set option
			how get the user role
		*/
		$max_posts = 3; // change this or set it as an option that you can retrieve.
		$count = count_user_posts($authorID,'page',false ); // get author post count
		if ( $count > $max_posts ) {
			//count too high, let's set it to draft.
			//$post = array('post_status'   => 'draft');
			//wp_update_post( $post );
		
		}
		$this->display($count);
	  }
  }
}

/*
source:https://carlalexander.ca/designing-class-wordpress-hooks/
Why use that hook and not another? It’s the first hook that WordPress calls once it’s done loading all plugins.
It’s there to prevent potential conflicts or issues.
*/
add_action('plugins_loaded', array('BoundsPages', 'init'));
?>