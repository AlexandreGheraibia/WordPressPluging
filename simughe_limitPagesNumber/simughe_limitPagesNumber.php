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
//include 'simurghe_menu.php';

/*source:https://codex.wordpress.org/Writing_a_Plugin
Security reason
blocking direct access to the plug-in
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

 class BoundsPages {
 public static $self=null;
 public static function add_action($action,$function,$param1=null,$param2=null){
	 add_action( $action, array(self::$self,$function),$param1,$param2);
 }
 
 public static function add_filter($action,$function,$param1=null,$param2=null){
	 add_filter( $action, array(self::$self,$function),$param1,$param2);
 }
  /*
	Apparently it's a good practice to pass by an init function.
	For avoid the coupling between wordpress and the custom class
  */
  public static function init(){
	if(BoundsPages::$self===null){
		self::$self = new self();
		self::add_action( 'admin_menu','disable_create_newpost');
		self::add_action('transition_post_status','postPublished', 10, 3 );
		self::add_action('manage_posts_columns','custom_post_page_columns');
		self::add_filter( 'page_row_actions','add_page_row_actions', 10, 2 );
		self::add_action( 'admin_init','restitute_page_to_author');
		self::add_filter( 'views_edit-page', 'custom_edit_view' ,10,1);
		self::add_action( 'wp_trash_post','my_postTrashed',10,1);
		self::add_action('untrash_post', 'my_restore_post',10,1);
	}
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
  
  
  /*check if the user with the $user_id has the role $role*/
  function is_user_in_role( $user_id, $role  ) {
    $user = new WP_User( $user_id );
	return ( ! empty( $user->roles ) && is_array( $user->roles ) && in_array( $role, $user->roles ) );
  }
   
  /*
	add a link in the list
  */
  function supp_entry_HTML($content,$title,$address,$postID){
    $dom = new DOMDocument();
	$dom->encoding = 'UTF-8';
    $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'),LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	$list = $dom->getElementsByTagName('li');
	//see add nonce
	if(isset($list)&&$list->count()>0){
		$elemToSupp=null;
		foreach ($list as $li) {//add a stop condition when the is found
			$links=$li->getElementsByTagName('a');
			if($links->count()>0 ){
					$a=$links[0];
					if(!strcmp($a->getAttribute("rel"),$postID)){
						$elemToSupp=$li;
					}
			}
		}
		if($elemToSupp!=null){
			$elemToSupp->parentNode->removeChild($elemToSupp);
			return $dom->saveHTML($dom->documentElement);
		}
	}
    return null;

}

function add_entry_HTML($content,$title,$address,$postID){
    $dom = new DOMDocument();
	$dom->encoding = 'UTF-8';
    $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'),LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	//see add nonce
	$ol = $dom->getElementsByTagName('ol');
	if(isset($ol)&&$ol->count()>0){
		$ol = $ol[0];
		$p=$ol->getElementsByTagName('p');
		if(isset($p)&&$p->count()>0){
			$p=$p[0];
			$ol->removeChild($p);
		}
		$newLi=$dom->createElement('li');
		$newLi->innerHTML="";
		$newLink=$dom->createElement('a',$title);
		$newLink->setAttribute('href',$address);
		$newLink->setAttribute('rel',$postID);
		$newLi->appendChild($newLink);
		$ol->appendChild($newLi);
		return $dom->saveHTML($dom->documentElement);
	}
     
    return null;

}

/*
	the editor/admin suppress a scontributor page, we suppress his link
  */
function my_postTrashed($postID){
	global $wpdb;
	$userID=get_current_user_id();
	if(is_admin()&&$this->is_user_in_role($userID,'editor' )||$this->is_user_in_role($userID,'administrator' )){
		$post_content=get_post($postID); 
		$authorID = $post_content->post_author; // Post author ID.
		if($this->is_user_in_role($authorID,'scontributor')&&get_post_status($postID)=='publish'){
				$wpdb->query('START TRANSACTION');
				$post_1253=get_post(1253); 
				$content = $post_1253->post_content;
				$res=$this->supp_entry_HTML($content,$post_content->post_title,get_permalink($postID),$postID);
				if($res!=null){
					$my_post = array(
						'ID'           => 1253,
						'post_content' => $res);
					wp_update_post( $my_post);
				    $wpdb->query('COMMIT'); // if you come here then well done
				}
				else{
					 $wpdb->query('ROLLBACK'); // // something went wrong, Rollback
				}
			
		}
	}
}

function my_restore_post($postID){
	global $wpdb;
	$userID=get_current_user_id();
	if(is_admin()&&$this->is_user_in_role($userID,'editor' )||$this->is_user_in_role($userID,'administrator' )){
		$post_content=get_post($postID); 
		$authorID = $post_content->post_author; // Post author ID.
		if($this->is_user_in_role($authorID,'scontributor')&&get_post_status($postID)=='trash'){
			// Get previous post status
			$status = get_post_meta( $postID, '_wp_trash_meta_status', true );
			// Only untrash if the previous status wasn't a 'draft'
			if( 'draft' !== $status ){
				$wpdb->query('START TRANSACTION');
				$post_1253=get_post(1253); 
				$content = $post_1253->post_content;
				$links=explode("__",get_permalink($postID));
				$link=get_permalink($postID);
				if(isset($links)&&count($links)>0&&strlen($links[0])>0){
					$link=$links[0]."/";
				}
				$res=$this->add_entry_HTML($content,$post_content->post_title,$link,$postID);
				if($res!=null){
					$my_post = array(
						'ID'           => 1253,
						'post_content' => $res);
					wp_update_post( $my_post);
					$wpdb->query('COMMIT'); // if you come here then well done
				}
				else{
					 $wpdb->query('ROLLBACK'); // // something went wrong, Rollback
				}
			}
		}
	}
}
  /*
	the editor/admin publish a scontributor link
  */
  function postPublished($new_status, $old_status, $post){
	global $wpdb;
	$userID=get_current_user_id();
	if(is_admin()&&$this->is_user_in_role($userID,'editor' )||$this->is_user_in_role($userID,'administrator' )){
		//$this->log_message($new_status.' '.$old_status);
		if(!strcmp('publish', $new_status)&&(!strcmp($old_status, 'draft')||!strcmp($old_status, 'pending'))&& !strcmp($post->post_type, 'page')){
			$postID=$post->ID;
			if(!wp_is_post_revision( $postID )){
				$post_content=get_post($postID); 
				$authorID = $post_content->post_author; // Post author ID.
				if($this->is_user_in_role($authorID,'scontributor')&&get_post_status($postID)=='publish'){
					$post_1253=get_post(1253); 
					$content = $post_1253->post_content;
					$res=$this->add_entry_HTML($content,$post_content->post_title,get_permalink($postID),$postID);
					if($res!=null){
						$my_post = array(
							'ID'           => 1253,
							'post_content' => $res);
						wp_update_post( $my_post);
						$wpdb->query('COMMIT'); // if you come here then well done
					}
					else{
						$wpdb->query('ROLLBACK'); // // something went wrong, Rollback
					}
				}
			}
		}
	}
  }
  
  /*
	https://wordpress.stackexchange.com/questions/90506/how-we-count-the-user-draft-posts 
  */
  function count_user_posts_by_status($user_id = 0){
    global $wpdb;
    $count = $wpdb->get_var(
        $wpdb->prepare( 
        "
        SELECT COUNT(ID) FROM $wpdb->posts 
        WHERE (post_status = 'publish'
		OR post_status = 'Pending'
		OR post_status='Future' 
		OR post_status='draft')
        AND post_author = %d",
        $user_id
        )
    );
    return ($count) ? $count : 0;
 }
 
 
  /*
	the contributor have already one page publish
	todo reworks on just one database request-----------------------------------------------------------------------------------------
  */
  function disable_create_newpost() {
	global $wp_post_types;
	global $submenu;
	$authorID = get_current_user_id();// Post author ID.
	if (is_admin()&& $this->is_user_in_role($authorID,'scontributor')
		&& $this->count_user_posts_by_status($authorID)>0) {
		 log_message($this->count_user_posts_by_status($authorID));
		 $wp_post_types['page']->cap->create_posts = 'do_not_allow';
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
  
	/*
		add a link in the admin/editor for restitute the page to his author
		todo enlarge for the editor
	*/

	function add_page_row_actions( $actions,$post)
	{	if(is_admin()){
			$userID=get_current_user_id();
			if($this->is_user_in_role($userID,'administrator')||$this->is_user_in_role($userID,'editor' )){
				
				if(get_post_type() === 'page'){
					$postID=$post->ID;
					$author=get_post($postID); 
					$authorID = $author->post_author; // Post author ID.
					if($this->is_user_in_role($authorID,'scontributor')&&get_post_status($postID)=='publish'){
						$url = add_query_arg(
							array(
							  'post_id' => $postID,
							  'user_id' => $userID,
							  'my_action' => 'restitute_page_to_author',
							)
						  );
						$link=$url;
						$link = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($link, 'cluqLimit_restitute_page') : $link;
						//put a nonce here-------------------------------------------------------------------------------------
						//$actions['rendre'] = '<a href="' . esc_url( $url ) . '">'.($this->is_user_in_role($userID,'scontributor')?'Editer':'Restituer').'</a>';
						$actions['rendre'] = '<a href="' . $link . '">'.'Restituer'.'</a>';
					}
				}
			}
			else{
				if(isset($actions['rendre'])){
					unset($actions['rendre']);
				}
			}
		}
		return $actions;
	}
	
	function restitute_page_to_author(){
		
		//check the nonce here-------------------------------------------------------------------------------------
	  if (is_admin()&&isset( $_REQUEST['my_action'] ) && 
	   'restitute_page_to_author' == $_REQUEST['my_action']  ) {
		if(isset($_REQUEST['post_id'])&&isset($_REQUEST['user_id'])&&$_REQUEST['user_id']==get_current_user_id()){
			$postID=$_REQUEST['post_id'];
			$post_content=get_post($postID); 
			$authorID = $post_content->post_author; // Post author ID.
			if($this->is_user_in_role($authorID,'scontributor')){
					check_admin_referer('cluqLimit_restitute_page');
					$this->change_post_status($postID,'draft');
					$post_1253=get_post(1253); 
					$content = $post_1253->post_content;
					$res=$this->supp_entry_HTML($content,$post_content->post_title,get_permalink($postID),$postID);
					if($res!=null){
						$my_post = array(
							'ID'           => 1253,
							'post_content' => $res);
						wp_update_post( $my_post);
					}
			}
		}
	  }
	}
	
	/*limite la vue*/
	function custom_edit_view($views){
		if(is_admin()&&$this->is_user_in_role(get_current_user_id(),'scontributor')){
			foreach($views as $key =>$value){
				if($key!=='mine'){
					unset($views[$key]);
				}
			}
		}
		return $views;
	}
}

/*
source:https://carlalexander.ca/designing-class-wordpress-hooks/
Why use that hook and not another? It’s the first hook that WordPress calls once it’s done loading all plugins.
It’s there to prevent potential conflicts or issues.
*/
add_action('plugins_loaded', array('BoundsPages', 'init'));
?>