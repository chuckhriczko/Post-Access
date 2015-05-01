<?php
require_once('post_access_model.class.php');
/*******************************************************************************
 * Define our initial class
 ******************************************************************************/
class Post_Access {
				//Instantiate our public variables
				private $model, $plugin_path, $plugin_uri, $user;
				
				/*******************************************************************************
				 * Instantiate our constructor
				 ******************************************************************************/
				public function __construct(){
								//Call the init function
								$this->init();
				}
				
				/*******************************************************************************
				 * Perform initialization functions
				 ******************************************************************************/
				public function init(){
								//Instantiate our model
								$this->model = new Post_Access_Model();
								
								//Init paths
								$this->plugin_path = plugin_dir_path(__FILE__).'../';
								$this->plugin_uri = plugin_dir_url(__FILE__).'../';
								
								//Add an init hook for getting user information
								add_action('init', function(){
												//Get the logged in user's information
												$this->user = get_userdata(get_current_user_id());
								});
								
								//Add an init hook for verifying the user
								add_action('admin_init', function(){
												//Verify the user can access the plugin
												$this->init_hooks();
								});
								
								//Add the "User Restricted" page
								add_action('admin_menu', array(&$this, 'admin_menu'));
								
								//Add the filter for determining whether or not the edit link should appear in the WP admin bar
								add_filter('edit_post_link', array(&$this, 'edit_post_link'));
								
								//Adds and removes columns from the pages listing
								add_filter('manage_pages_columns', array(&$this, 'manage_pages_columns'));
								
								//Adds the data for the added columns
								add_action('manage_pages_custom_column', array(&$this, 'manage_pages_custom_column'), 10, 2);
				}
				
				/*******************************************************************************
				 * Verifies the user is at least an admin
				 ******************************************************************************/
				public function verify_user(){
								//Only continue if the currently logged in user is at least an administrator
								return isset($this->user->allcaps['activate_plugins']) && $this->user->allcaps['activate_plugins']>0 ? true : false;
				}
				
				/*******************************************************************************
				 * Initializes the hooks for the plugin
				 ******************************************************************************/
				public function init_hooks(){
								//Include scripts and styles for the admin
								add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
								
								//Add the action for when the edit page loads
								add_action('load-page.php', array(&$this, 'edit_page'));
								
								//Add hook to filter out posts from the post listing page that this user cannot edit
								add_action('the_posts', array(&$this, 'the_posts'));
								
								//Verify this user can see the metaboxes
								if ($this->verify_user()){
												//Add meta boxes to the custom post type editor screen
												add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'), 1);
												
												//Save the meta data when a post is saved
												add_action('save_post', array(&$this, 'save_post'));
								}
				}
				
				/*******************************************************************************
				 * Removes and adds columns to the pages listing
				 ******************************************************************************/
				public function manage_pages_columns($columns){
								//Remove the columns that come after the User column
								unset($columns['author'], $columns['categories'], $columns['comments'], $columns['date'], $columns['tags']);
								
								//Add the user column
								$columns['user'] = 'User';
								
								//Readd the removed columns
								$columns['categories'] = 'Categories';
								$columns['tags'] = 'Tags';
								$columns['comments'] = 'Comments';
								$columns['date'] = 'Date';
								
								return $columns;
				}
				
				/*******************************************************************************
				 * Adds data to the custom columns
				 ******************************************************************************/
				public function manage_pages_custom_column($column_name, $post_id = 0){
								//Init the no user message
								$none = '<ul><li>Not Assigned</li></ul>';
								
								//Determine the column
								switch($column_name){
												case 'user':
																//Verify the post ID exists
																if (isset($post_id) && !empty($post_id)){
																				//Get the user associated with this post ID
																				$users = get_post_meta($post_id, 'post-access-user', true);
																				
																				//Verify we have user IDs
																				if (!empty($users)){
																								//Verify we received an array and, if not, make it one
																								$users = is_array($users) ? $users : array($users);
																								
																								//Init our HTML variable
																								$html = '<ul>';
																								
																								//Loop through the users
																								foreach($users as $key=>$user){
																												//Get the user data for this user
																												$users[$key] = get_userdata($user);
																												
																												//Verify we have data
																												$html .= !empty($users[$key]) ? (isset($users[$key]->data->display_name) && !empty($users[$key]->data->display_name) ? '<li>'.$users[$key]->data->display_name.'</li>' : '') : '';
																								}
																								
																								//Close the list
																								$html .= '</ul>';
																								
																								//Echo the HTML or the $none message if the HTML is empty
																								echo empty($html) ? $none: $html;
																				} else {
																								echo $none;
																				}
																} else {
																				echo $none;
																}
																
																break;
								}
    }
				
				/*******************************************************************************
				 * Adds the "User Restricted" page
				 ******************************************************************************/
				public function admin_menu(){
								//Add the menu item without a parent slug. This will create a page without a menu item
								add_submenu_page('parent_does_not_exist', 'User Restricted', 'User Restricted', 'edit_posts', 'post-access-user-restricted', array(&$this, 'display_page_user_restricted'));
				}
				
				/*******************************************************************************
				 * Displays the "User Restricted" page
				 ******************************************************************************/
				public function display_page_user_restricted(){
								//Include the template
								@include($this->plugin_path.'/tpl/admin/page-user-restricted.php');
				}
				
				/*******************************************************************************
				 * Displays the post access settings page
				 ******************************************************************************/
				public function display_page_post_access($post){
								global $wpdb;
								
								//Include the template
								include($this->plugin_path.'tpl/admin/page-post-access.php');
				}
				
				/*******************************************************************************
				 * Adds custom meta boxes to the custom post type editor screen
				 ******************************************************************************/
				public function add_meta_boxes($post_type){
								//Add the main metabox to the admin section
								add_meta_box('post-access', 'Post Access', array(&$this, 'display_meta_box_post_access'), $post_type, 'side', 'high');
				}
				
				/*******************************************************************************
				 * Displays the custom meta boxes
				 ******************************************************************************/
				public function display_meta_box_post_access($post){
								//Get the list of users
								$users = $this->model->get_wp_users();
								
								//Get the user for this post
								$post_users = get_post_meta($post->ID, 'post-access-user', true);
								
								//Verify we recieved a value
								$post_users = empty($post_users) ? array(0) : $post_users;
								
								//Check if this is a serialized string
								$post_users = @unserialize($post_users)===false ? array(0 => $post_users) : unserialize($post_users);
								
								//If we have a multidimensional array, make it a single dimensional array
								$post_users = isset($post_users[0]) && !empty($post_users[0]) && is_array($post_users[0]) ? $post_users[0] : $post_users;
								
								//Loop through the post users
								foreach($post_users as $key=>$post_user){
												$post_users[$key] = get_userdata(is_array($post_user) ? $post_user[0] : $post_user);
								}
								
								//Include the template
								include($this->plugin_path.'tpl/admin/metabox-post-access.php');
				}
				
				/*******************************************************************************
				 * Saves the meta box data when a post is saved
				 ******************************************************************************/
				public function save_post($post_id, $post_obj = ''){
								//If the post object is not set, get it from the provided post ID
								$post_obj = empty($post_obj) ? get_post($post_id) : $post_obj;
								
								//Add the metadata to the post if the user has been selected
								update_post_meta($post_id, 'post-access-user', isset($_POST['post-access-user-hidden']) && !empty($_POST['post-access-user-hidden']) ? $_POST['post-access-user-hidden'] : array());
				}
				
				/*******************************************************************************
				 * Registers scripts and styles to be placed in the admin header
				 ******************************************************************************/
				public function admin_enqueue_scripts(){
								//Set the script dependencies
								$deps = array('jquery');
								
								//Enqueue scripts
								wp_enqueue_script('post-access-admin-script', $this->plugin_uri.'assets/js/admin.js', $deps);
								
								//Enqueue styles
								wp_enqueue_style('post-access-admin-style', $this->plugin_uri.'assets/css/admin.css');
				}
				
				/*******************************************************************************
				 * Block user if this post has a restriction
				 ******************************************************************************/
				public function edit_page(){
								//Get the post object based on the post ID
								$post_id = isset($_GET['post']) && !empty($_GET['post']) ? (int)$_GET['post'] : 0;
								
								//Make sure the post ID is not empty
								if ($post_id>0){
												//Get the post access user
												$user_data = get_post_meta($post_id, 'post-access-user', true);
												
												//Process the user ID(s)
												$user_ids = !is_array($user_data) ? array($user_data) : $user_data;
												
												//Init the authorized flag
												$authorized = false;
												
												//Loop through each user ID and see if any of them match the current user ID
												foreach($user_ids as $id){
													//Check if this user is the current user
													$authorized = $id==$this->user->data->ID ? true : $authorized;
												}
												
												//Verify the value is not empty and, if not, redirect the user to the "User Restricted" page
												if (!$authorized && !current_user_can('manage_options')) wp_redirect(admin_url().'admin.php?page=post-access-user-restricted');
								}
				}
				
				/*******************************************************************************
				 * Filters out posts from the post listing that this user is not authorized to see
				 ******************************************************************************/
				public function the_posts($posts){
								//Verify we are on the admin side
								if (is_admin()){
												//Loop through the posts
												foreach($posts as $key=>$cur_post){
																//Make sure this is not an attachment (media library)
																if (!in_array($cur_post->post_type, array('attachment', 'image'))){
																				//Get the post access user for this post
																				$users = get_post_meta($cur_post->ID, 'post-access-user', true);
																				
																				//If we don't have a post-access-user saved, set it to 0 (not restricted)
																				$users = empty($users) ? 0 : is_array($users) ? $users : array($users);
																				
																				//Check if the user is an admin
																				$is_admin = isset($this->user->allcaps['activate_plugins']) && $this->user->allcaps['activate_plugins']>0 ? true : false;
																				
																				//Init the user authorized flag
																				$authorized = $is_admin ? true : false;
																				
																				//If we are an admin, we don't have to sort through the posts
																				if (!$is_admin){
																								//Loop through the users associated with this post
																								foreach($users as $user){
																												//Check to see if this post is not accessible by this user and, if not, remove the post from the array
																												if (($user>0 && ($this->user->data->ID==$user)) || empty($user)){
																																//Set the authorized flag
																																$authorized = true;
																																
																																//Break out of the loop
																																break;
																												}
																								}
																								
																								//Remove this post from the array if the user is not authorized
																								if (!$authorized || empty($users[0])) unset($posts[$key]);
																				}
																}
												}
								}
								
								//Return the reindexed array
								return array_values($posts);
				}
				
				/*******************************************************************************
				 * Hides the edit post link if this post is restricted from editing by this user
				 ******************************************************************************/
				public function edit_post_link($link){
								//Get the post ID from the link
								preg_match('/post=[1-9]*/', $link, $matches);
								
								//Get the post ID from the matches
								$post_id = !empty($matches) && is_array($matches) ? (int)str_replace('post=', '', $matches[0]) : 0;
								
								//Get the post access user
								$user_id = (int)get_post_meta($post_id, 'post-access-user', true);
								
								//Check if this user is an admin
								$is_admin = isset($this->user->allcaps['activate_plugins']) && $this->user->allcaps['activate_plugins']>0 ? true : false;
								
								//Check if this post is restricted from being edited by the current user
								$link = $user_id>0 && ($this->user->data->ID!=$user_id && !$is_admin) ? str_replace('class="', 'class="edit-post-link-hidden ', $link) : $link;
								
								return $link;
				}
}
?>