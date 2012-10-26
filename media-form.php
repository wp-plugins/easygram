<?php // Make sure we've got a post ID
	if(isset($_REQUEST['postid']))
		$postid = $_REQUEST['postid'];
	else
		$postid = $_REQUEST['post_id'];

	// Set the default tab url for usage for all links and form actions
	$taburl = admin_url('media-upload.php?post_id='.$postid.'&amp;tab=eg');
	
	// Start putting together any args we need for our Instagram request
	$args = array();
	
	// Pagination
	if(isset($_GET['max_id']) && $_GET['max_id'] != "") :
		$args['max_id'] = $_GET['max_id'];
	endif;
	
	// Hashkey searching
	if(isset($_GET['hashkey']) && $_GET['hashkey'] != "") :
		$args['hashkey'] = $_GET['hashkey'];
		$taburl .= '&hashkey='.$_GET['hashkey'];
	endif;
			
	// Get Instagram Media
	$args['count'] = 20;
	
	// Instagram API call
	$feed = $this->instagram_get_latest($args);
?>
<form id="filter" action="" method="get">
    <input type="hidden" name="type" value="file" />
    <input type="hidden" name="tab" value="eg" />
    <input type="hidden" name="post_id" value="<?php echo $postid; ?>" />
    
    <p id="media-search" class="search-box">
        <label class="screen-reader-text" for="media-search-input">Search Instagram:</label>
        <input type="search" id="media-search-input" name="hashkey" id="hashkey" value="<?php if(isset($_GET['hashkey'])) echo $_GET['hashkey']; ?>" />
        <input type="submit" name="" id="" class="button" value="Search Tag"  />
    </p>
</form>
<?php if(isset($_GET['error'])) : ?>
   <div id="message" class="error clear">
		<p><?php _e('We had an issue attaching your images to your post.', 'eg'); ?></p>
   </div>
<?php elseif(isset($_POST['eg_attach'])) :
	if(isset($_SESSION['eg_error'])) : ?>
	   <div id="message" class="error clear">
			<p><?php _e('We had an issue attaching your images to your post. '.$_SESSION['eg_error'], 'eg'); ?></p>
	    </div>
	<?php unset($_SESSION['eg_images_added']);
	 else :
		// Read a sneaky session var to see how many images we've added
		if(isset($_SESSION['eg_images_added'])) :
    		$count = $_SESSION['eg_images_added'];
    		unset($_SESSION['eg_images_added']);
    	endif;
    	
    	// Deal with plurals
		if($count > 1)
			$image_text = "images have";
		else 	    		
			$image_text = "image has"; ?>
	    <div class="updated clear">
			<p><?php _e($count.' '.$image_text.' been attached to your post. Click <a href="'.admin_url('media-upload.php?post_id='.$postid.'&amp;tab=gallery').'">here</a> to manage your post gallery.', 'eg'); ?></p>
	    </div>
<?php endif;
endif; ?>
<form enctype="multipart/form-data" method="post" action="" class="media-upload-form validate " id="easygram-form">
	<?php if ( function_exists('wp_nonce_field') )
		wp_nonce_field('eg_nonce');  ?>
		
    <input type="hidden" name="eg_attach" value="1" />
    <div id="media-items">
	    <div class="tablenav">
        	<?php // Hit the first page
        	if(isset($args['max_id'])) : ?>
	        	<a class="page-numbers alignleft" href="<?php echo $taburl; ?>"><?php _e('First Page', 'eg'); ?></a>
        	<?php endif; ?>
	        <div class='tablenav-pages'>
	        	<a class="next page-numbers" href="<?php echo $taburl.'&amp;max_id='.$feed['next_max_id']; ?>"><?php _e('&rarr;', 'eg'); ?></a>
	       </div>
	    </div>
	    
    	<?php
    	// If there's an error, let's make sure we tell the user
    	if ( is_wp_error( $feed ) ) :
		   $error_string = $feed->get_error_message();
			echo '<div id="message" class="error"><p>' . _('There was a problem loading your feed, please <a href="">refresh</a> this tab. Error: ') . $error_string . '</p></div>';
    	// If there's an error and we don't know the problem, give the user a generic warning
		elseif(!is_array($feed['images'])) :			
    		echo '<p>'._('There was a problem loading your feed, please <a href="">refresh</a> this tab, if the problem persists, try re-authenticating your account under the <a href="'.admin_url('admin.php?page=eg_general_settings').'">Easygram Options</a>').'</p>';
    	// If it's all good then loop through the user's images
    	else :
	    	foreach($feed['images'] as $image) : ?>
		        <div id='media-item-<?php echo $post_id; ?>' class='media-item preloaded'>
		            <a class='toggle describe-toggle-on' href='#'>Details</a>
		            <a class='toggle describe-toggle-off' href='#'>Hide</a>
		            <div class="filename new">
						<input type="checkbox" name="attachimages[][id]" value="<?php echo $image['id']; ?>" id="media-<?php echo $image['id']; ?>" />
				        <img class="pinkynail toggle" src="<?php echo $image['image_small']; ?>" alt="<?php echo $image['title']; ?>" />
		            	<span class="title"><label for="media-<?php echo $image['id']; ?>"><?php echo strip_tags($image['short_title']); if(strlen($image['title']) > 45) echo '...'; ?></label></span>
		            </div>
		            <table class='slidetoggle describe startclosed'>
		                <thead class='media-item-info' id='media-head-<?php echo $post_id; ?>'>
		                    <tr valign='top'>
		                        <td id='thumbnail-head-<?php echo $post_id; ?>'>
		                        	<p><a href='http://localhost/wp/?attachment_id=<?php echo $post_id; ?>' target='_blank'><img src='<?php echo $image['image_middle']; ?>' alt='' /></a></p>
		                        </td>
		                        <td>
		                        	<p><strong>Link :</strong> <a href="<?php echo $image['link']; ?>" target="blank"><?php echo $image['link']; ?></a></p>
		                        	<p><strong>User:</strong> <?php echo $image['user']; ?></p>
		                        	<p><strong>Filter:</strong> <?php echo $image['filter']; ?></p>
		                        	<p><strong>Upload date:</strong>  <?php echo date("d M Y", $image['date']); ?></p>
		                        	<?php if(is_array($image['tags']) && !empty($image['tags'])) : ?>
			                        	<p><strong>Tags:</strong> <?php echo "#".implode(" #", $image['tags']);  ?></p>
		                        	<?php endif; ?>
		                        	<p><a href="<?php echo admin_url('media-upload.php?post_id='.$postid.'&amp;_wpnonce='.wp_create_nonce('eg_nonce').'&amp;eg_attach_image='.$image['id']); ?>"><?php _e('Attach image to post', 'eg'); ?></a></p>
		                    	</td>
		                    </tr>
		                </thead>
		            </table>
		        </div>
			<?php endforeach;
    	endif; ?>
    
    	<?php // duplicate pagination in the footer, just for useability ?>
	    <div class="tablenav">
        	<?php if(isset($args['max_id'])) : ?>
	        	<a class="page-numbers alignleft" href="<?php echo $taburl; ?>"><?php _e('First Page', 'eg'); ?></a>
        	<?php endif; ?>
	        <div class='tablenav-pages'>
	        	<a class="next page-numbers" href="<?php echo $taburl.'&amp;max_id='.$feed['next_max_id']; ?>"><?php _e('&rarr;', 'eg'); ?></a>
	       </div>
	       <br class="clear" />
	    </div>
    </div>
    <p class="ml-submit">
    	<?php // If there is no post ID, we'll be adding to the media library and not the post
    	if(isset($post_id) && "" == $post_id)
    		$label = "Import images into library";
    	else  
    		$label = "Attach images to post";
    	?>
    	<input type="submit" name="send" id="send" class="button" value="<?php _e($label, 'eg'); ?>"  />
    	<img src="<?php echo admin_url('images/wpspin_light.gif'); ?>" class="loading no_display" alt="loading">
    	<em><?php _e('You will still be on the same page after the images have been attached.', 'eg'); ?></em>
        <input type="hidden" name="post_id" id="post_id" value="<?php echo $postid; ?>" />
    </p>
</form>
