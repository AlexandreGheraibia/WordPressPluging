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
	  // add_action( 'edit_post', array($self, 'checkCanPublish'));
	   add_action( 'trashed_post', array($self, 'page_allowed'));
	   add_action( 'admin_menu', array($self, 'remove_menus'));
	   add_action('publish_post',array($self,''));
	  
  }
  
  public function log_message($message){
	 if (WP_DEBUG === true) {
        if (is_array($message) || is_object($message)) {
            error_log(print_r($message, true));
        } 
		else {
            error_log($message);
        }
    }
  }
 
  function is_user_in_role( $user_id, $role  ) {
    $user = new WP_User( $user_id );
	return ( ! empty( $user->roles ) && is_array( $user->roles ) && in_array( $role, $user->roles ) );
  }
  
  /*
	the editor publish a scontributor link
  */
  function postPublished($postID){
   if(is_admin()||$this->is_user_in_role(get_current_user_id(),'editor' )){
		get_permalink($postID);
		update_option( "some_other_option",$roles[0],true);
   }
  }
  
  /*
	https://wordpress.stackexchange.com/questions/90506/how-we-count-the-user-draft-posts 
  */
  function count_user_posts_by_status($post_status = 'publish',$user_id = 0){
    global $wpdb;
    $count = $wpdb->get_var(
        $wpdb->prepare( 
        "
        SELECT COUNT(ID) FROM $wpdb->posts 
        WHERE post_status = %s 
        AND post_author = %d",
        $post_status,
        $user_id
        )
    );
    return ($count) ? $count : 0;
 }
  /*
	the contributor have already one page publish
  */
  function remove_menus() {
	global $submenu;
	$authorID = get_current_user_id();// Post author ID.
	if ( $this->is_user_in_role($authorID,'scontributor')
		&&($this->count_user_posts_by_status('draft',$authorID)>0
			||($this->count_user_posts_by_status('publish',$authorID)>0
			||($this->count_user_posts_by_status('Pending',$authorID)>0)))) {
		  $submenu['edit.php?post_type=page'][10] =null;
	}
 }
 /*
	source:https://wordpress.stackexchange.com/questions/12512/how-to-update-page-status-from-publish-to-draft-and-draft-to-publish
	$post_id - The ID of the post you'd like to change.
	$status -  The post status publish|pending|draft|private|static|object|attachment|inherit|future|trash.
  */
  function change_post_status($post_id,$status){
	$current_post = get_post( $post_id, 'ARRAY_A' );
	$current_post['post_status'] = $status;
	wp_update_post($current_post);
  }
  
  /*the admin send the page to the trash
	the scontributor take back his page
  */
  public function page_allowed($postID)  {
	  if(is_admin()||current_user_has_role( 'editor' )){
		$author=get_post($postID); 
		$authorID = $author->post_author; // Post author ID.
		if($this->is_user_in_role($authorID,'scontributor')){
			/*$user->remove_role( 'sdeleter' );
			$user->add_role( 'scontributor' );*/
			$this->change_post_status($postID,'draft');
		}
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