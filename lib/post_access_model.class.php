<?php
/*******************************************************************************
 * This model class is used to separate all the data related processes from
 * the plugin logic
 ******************************************************************************/
class Post_Access_Model {
				/*******************************************************************************
				 * Gets all WP users or a single user based on ID
				 ******************************************************************************/
				public function get_wp_users($id = 0, $numusers = 0, $orderby = 'display_name', $order = 'ASC'){
								//Init the arguments for the get_users() function below
								$args = array('orderby' => $orderby, 'order'   => $order);
								
								//Determine whether to search by user ID
								if (!empty($id)) $args['search'] = $id;
								
								//Determine whether to search by user ID
								if (!empty($numusers)) $args['number'] = $numusers;
								
								//Return users based on arguments above
								return get_users($args);
				}
}
?>