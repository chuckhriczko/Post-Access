<?php
/*
Plugin Name: Post Access
Plugin URI: http://www.objectunoriented.com/projects/post-access
Description: Adds ability to restrict editing and creating of posts to specific users
Version: 1.0
Author: Charles Hriczko
Author URI: http://www.objectunoriented.com
License: GPLv2
*/
require_once('lib/constants.php');
require_once('lib/post_access.class.php');

//Instantiate our class
$post_access = new Post_Access();
?>