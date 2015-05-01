var Post_Access_Admin = {
				anim_speed: 'medium',
				cache: {},
}; //Init the primary admin object

(function($) {
				$(document).ready(function(){
								Post_Access_Admin.init_dom_cache();
								Post_Access_Admin.init_tabbed_lists();
				});
				
				/*******************************************************************************
				 * Initializes the DOM cache for faster access
				 ******************************************************************************/
				Post_Access_Admin.init_dom_cache = function(){
								Post_Access_Admin.cache.metabox = $('#post-access');
								Post_Access_Admin.cache.select = Post_Access_Admin.cache.metabox.find('select');
								Post_Access_Admin.cache.button_add = Post_Access_Admin.cache.select.next('button');
								Post_Access_Admin.cache.tabbed_lists = Post_Access_Admin.cache.button_add.next('ul');
				}
				
				/*******************************************************************************
				 * Initializes the tabbed lists for the services and tools sections
				 ******************************************************************************/
				Post_Access_Admin.init_tabbed_lists = function(){
								//Loop through each list
								Post_Access_Admin.cache.tabbed_lists.each(function(){
												//Cache the textbox
												var $list = $(this);
												
												//Find the add button in this list and bind the click event
												$(this).prev('button').off('click').on('click', function(){
																//Instantiate the list string
																var $ul = $(this).next('ul');
																
																//Append the textbox value to the list
																$ul.append('<li style="display: none;"><a href="#remove" title="Remove">' + Post_Access_Admin.cache.select.find('option:selected').text() + '</a><input type="hidden" name="post-access-user-hidden[]" value="' + Post_Access_Admin.cache.select.find('option:selected').val() + '" /></li>');
																
																//Fade in the new item
																$ul.find('li:last-of-type').fadeIn(Post_Access_Admin.anim_speed);
																
																//Reinitialize the DOM cache and tabbed lists
																Post_Access_Admin.init_dom_cache();
																Post_Access_Admin.init_tabbed_lists();
																
																//Default the select box to the first option
																Post_Access_Admin.cache.select.val(1);
												});
												
												//Refresh the DOM cache
												Post_Access_Admin.init_dom_cache();
												
												//Bind the click event on the tabbed list items
												Post_Access_Admin.bind_list_items($(this));
								});
				}
				
				/*******************************************************************************
				* Binds the list item clicks so they remove the list item
				******************************************************************************/
				Post_Access_Admin.bind_list_items = function($list){
								//Refresh the DOM cache
								Post_Access_Admin.init_dom_cache();
								
								//Bind the click event on the tabbed list items
								$($list).find('li a').off('click').on('click', function(e){
												//Confirm the user wants to remove this item
												if (confirm('Are you sure you would like to remove "' + $(this).text() + '"?')){
																//Remove the item from the list
																$(this).parent('li').fadeOut(Post_Access_Admin.anim_speed, function(){
																				$(this).remove();
																});
												}
												
												//Prevent default action
												if (typeof e.stopPropogation=='function') e.stopPropogation();
												e.preventDefault();
												return false;
								});
				}
}(jQuery));