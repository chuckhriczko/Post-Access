<label for="post-access-user">To assign a user to this post, please select a user below:</label>
<select id="post-access-user" name="post-access-user">
				<?php
				//Loop through the users
				foreach($users as $user){
								?><option value="<?php echo $user->ID; ?>"<?php echo $user->ID==2 ? ' selected="selected"' : ''; ?>><?php echo stripslashes($user->data->display_name); ?></option><?php
				}
				?>
</select>
<button id="post-access-user-add" name="post-access-user-add" type="button" class="button secondary-button">Add User</button>
<ul>
				<?php
				//Loop through each of the users associated with this post
				foreach($post_users as $post_user){
								//Make sure we have a user in this array index
								if (isset($post_user->data->display_name) && !empty($post_user->data->display_name)){
												//Generate the list of users
												?><li><a href="#" title="Remove"><?php echo $post_user->data->display_name; ?></a><input type="hidden" name="post-access-user-hidden[]" value="<?php echo $post_user->ID; ?>" /></li><?php
								}
				}
				?>
</ul>