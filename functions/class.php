<?php class egclass
{
	// Setup some defaults which we'll use throughout the plugin
	public function __construct()
	    {
			$this->client_id = 'd25a442159e1442d95aeddc204dce20e';
			$this->client_secret = '85bd847851104448886895dc29def2fd';
			$this->redirect_url = 'http://easygram.oboxsites.com/?site_return='.str_replace("http://", "", esc_url(admin_url('admin.php?page=eg_general_settings')));
		}
		
	//Basic Instagram Auth code below, more info here: http://instagram.com/developer/authentication/
	function auth($code){
		$args = array(
				'body' => array(
					'grant_type' => 'authorization_code',
					'client_id' => $this->client_id,
					'client_secret' => $this->client_secret,
					'redirect_uri' => $this->redirect_url,
					'code' => $code
				),
				'sslverify' => apply_filters('https_local_ssl_verify', false)
			);
		$response = wp_remote_post("https://api.instagram.com/oauth/access_token", $args);

		//Good response from the server
		if(!is_wp_error($response) && $response['response']['code'] == 200):
			$auth = json_decode($response['body']);
			// We've got an access token!
			if(isset($auth->access_token)):
				return $auth->access_token;
			// If we somehow get a response, but no token just pass back null so that we can throw an error
			else:
				return null;
			endif;
		// Bad response, send the error through
		else:
			return $response;
		endif;
	}
	
	// Get one media item using the Media ID
	function instagram_get_media($id){
		// Fetch our Access Token
		$accesstoken = get_option('eg_accesstoken');
		
		// Only run the query if we have a token, otherwise we'll tell the user to re-auth
		if($accesstoken != null) :
			$apiurl = "https://api.instagram.com/v1/media/".$id."?access_token=".$accesstoken;
			$response = wp_remote_get($apiurl,
				array(
					'sslverify' => apply_filters('https_local_ssl_verify', false)
				)
			);
			// Good response
			if(!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200):
				$data = json_decode($response['body']);
				if($data->meta->code == 200) :
					return $data->data;
				else :
					return false;
				endif;
			// Failure
			else :
				return false;
			endif;
		else :
			return false;
		endif;
	}
	
	// This function gets the user's feed
	function instagram_get_latest($args){
		$images = array();
		
		// Default settings, can be overriden by sending $args to the function
		$defaults = array (
	 		'accesstoken' => get_option('eg_accesstoken'),
	 		'count' => 12,
	 		'hashkey' => false,
	 		'max_id' => false
		);
		
		// Take the args and turn 'em into variables
		$args = wp_parse_args( $args, $defaults );
		extract( $args, EXTR_SKIP );

		// If we've got an access token, run the query
		if($accesstoken != null) :
			// Hashe key search
			if($hashkey == false)
				$apiurl = "https://api.instagram.com/v1/users/self/media/recent?count=".$count."&access_token=".$accesstoken;
			// User feed
			else 
				$apiurl = "https://api.instagram.com/v1/tags/".$hashkey."/media/recent/?count=".$count."&access_token=".$accesstoken;
		// Else just return false
		else :
			return false;
		endif;
		
		// Pagination happens here
		if($max_id != false)
			$apiurl .= '&max_id='.$max_id;
			
		$response = wp_remote_get($apiurl,
			array(
				'sslverify' => apply_filters('https_local_ssl_verify', false)
			)
		);
		
		// Positive response
		if(!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200):
			$data = json_decode($response['body']);
			if(!empty($data) && $data->meta->code == 200) :
				// Build up the image array as we loop through our feed, turning it into something nice and usable
				foreach($data->data as $item) :
					
					// The title may vary
					if(isset($instance['hashtag'], $item->caption->text)):
						$image_title = $item->user->username.': &quot;'.filter_var($item->caption->text, FILTER_SANITIZE_STRING).'&quot;';
					elseif(isset($instance['hashtag']) && !isset($item->caption->text)):
						$image_title = "instagram by ".$item->user->username;
					elseif(isset($item->caption->text)) :
						$image_title = filter_var($item->caption->text, FILTER_SANITIZE_STRING);
					else :
						$image_title = ''; 
					endif;
					
					// Image population right here
					$images['images'][] = array(
						"id" => $item->id,
						"title" => $image_title,
						"short_title" => substr($image_title, 0, 45),
						"user" => $item->user->username,
						"date" => $item->created_time,
						"link" => $item->link,
						"type" => $item->type,
						"filter" => $item->filter,
						"tags" => $item->tags,
						"image_small" => $item->images->thumbnail->url,
						"image_middle" => $item->images->low_resolution->url,
						"image_large" => $item->images->standard_resolution->url
					);
				endforeach;		
				
				// Send through the pagination argument 
				$images['next_max_id'] = $data->pagination->next_max_id;
				return $images;
			else :
				return null;
			endif;
		
		// WP Error, send it through please
		elseif(is_wp_error($response)) :
			return $response;
		else :
			return null;
		endif;
	}

	// Script and style inclusion, does what it says on the tin
	function scripts_and_styles(){
		global $pagenow;
		// Don't mess around with anything but our very own media tab!
		if(is_admin() && isset($_REQUEST['tab']) && $_REQUEST['tab'] == "eg") :
			wp_enqueue_script( 'eg-media-script', plugins_url('easygram/js/media.js'), array( 'jquery' ) );
			wp_register_style( 'eg-media-style', plugins_url("easygram/css/media.css"));
			wp_enqueue_style( 'eg-media-style' );
		endif;
	}
	
	// Register our media tab
	function media_tab($tabs) {
		$newtab = array('eg' => __('Instagram', 'eg'));
		return array_merge($tabs, $newtab);
	}
	
	// Load up the media form here, it's an include because it's frankly huge
	function media_form() {
		media_upload_header();
		include(EGDIR.'/media-form.php');
	}
	
	// Make sure we load all the correct styles etc.
	function media_menu_handle() {
	    return wp_iframe(array(&$this, 'media_form'));
	}
	
	// This function breaks down tag attributs for us
	function extract_html_tags($html_content, $tag_name, $allowed_protocols=array()) {
		// Default list of protocols copied from wp-includes/kses.php:wp_kses()
		if (empty($allowed_protocols))
			$allowed_protocols = array('http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'svn');
			$tag_list = array();
			$tag_name = strtolower($tag_name);
			$tag_regexp = '/<\/?\s*'.$tag_name.'\s+(.+?)>/i';
			if (preg_match_all($tag_regexp, $html_content, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					// Parse attributes
					$attributes = array();
					foreach (wp_kses_hair($match[1], $allowed_protocols) as $attr)
						$attributes[$attr['name']] = $attr['value'];
					
					// Group all data of the tag in one array
					$tag_list[] = array( 'tag_string'       => $match[0],
									'tag_name'         => $tag_name,
									'attribute_string' => $match[1],
									'attributes'       => $attributes
								);
					}
				}
			return $tag_list;
		}
		
	// Get the image src using the extract_html_tags function, return just the image src
	function get_image_src($html_content = ''){
	    $new_tag_data = $this->extract_html_tags($html_content, 'img');
	    $src = $new_tag_data[0]['attributes']['src'];
	    return $src;
	}
	// Let's get the attachment ID by using the file url.
	function get_attachment_id_from_url($url) {
    	global $wpdb;
    	$query = "SELECT ID FROM {$wpdb->posts} WHERE guid='$url'";
    	return $wpdb->get_var($query);
	}
     
    // Sideload an image into the post
    function attach_image_to_post($postid = 0, $instaid = 0){
	    // Get the Instagram info
		$image = $this->instagram_get_media($instaid);

		// Get the url
		$url = $image->images->standard_resolution->url;

		// Get caption
		if(!empty($image->caption->text))
			$desc = $image->caption->text;
		else
			$desc = "";
			
		// Get the Post ID and kick off side-load		
		$new_tag = media_sideload_image($url, $postid, $desc);

		if(is_wp_error($new_tag)) :
			$_SESSION['eg_error'] = $new_tag->get_error_message();
			return $new_tag;	
		endif;
		
	    // Extract the image URL to get the attachment ID
	    $new_tag_url = $this->get_image_src($new_tag);
	    
	    // Get the attachment ID from the uploaded image URL
	    $attachment_id = $this->get_attachment_id_from_url($new_tag_url);

	    // Update the attachment with all the cool Instagram Data we got!
	    $instagrammeta = array();

	    // Update the attachment meta so that we can use these details in a theme eg. get_post_meta($attachment->ID, '_instagram_meta', true);
	    $instagrammeta['location'] = $image->location;
	    $instagrammeta['filter'] = $image->filter;
	    $instagrammeta['date'] = $image->created_time;
	    $instagrammeta['username'] = $image->user->username;
	    $instagrammeta['tags'] = $image->tags;
	    
	    // Update the attachment with the same tags we've got in iGram.
	    if(is_array($image->tags) && !empty($image->tags)) :
		    wp_set_post_terms( $attachment_id, implode(',',$image->tags), 'post_tag', true);
	    endif;
	    
	    // Use a hidden meta field so that people can't get all involved and mess up our clean data
		update_post_meta($attachment_id, '_instagram_meta', $instagrammeta);
    }
    
	function attach_images() {
		// Solo image attachment
		if(isset($_GET['eg_attach_image']) && $_GET['eg_attach_image'] != "") :		
			// Nonce FTW
			$nonce = $_REQUEST['_wpnonce'];
			if (wp_verify_nonce($nonce, 'eg_nonce') ) :
				$image = $this->attach_image_to_post($_GET['post_id'], $_GET['eg_attach_image']);				
			endif;
			
			// If there is an error take us back to the Easygram tab 
			if(is_wp_error($image)) :
				wp_redirect(admin_url('media-upload.php?post_id='.$_GET['post_id'].'&tab=eg&error=1'));
			else:
				// Send us to the Gallery Tab. Reason we do a redirect is so that you can delete the image afterwards! 
				wp_redirect(admin_url('media-upload.php?post_id='.$_GET['post_id'].'&tab=gallery'));
			endif;
		// Multiple image attachment
		elseif(isset($_POST['eg_attach']) && isset($_POST['attachimages'])) :		
			// Get the Post ID and kick off side-load
			$postid = $_REQUEST['post_id'];					

			// Nonce FTW
			$nonce = $_REQUEST['_wpnonce'];
			if (wp_verify_nonce($nonce, 'eg_nonce') ) :			
				// Loop through the checkboxes
				$i = 0;
				foreach($_POST['attachimages'] as $attachment) :
					$this->attach_image_to_post($postid, $attachment['id']);
					$i++;
				endforeach;
				$_SESSION['eg_images_added'] = $i;
			endif;
		endif;
	}
	
	// The Kick Off!
	function initiate(){
		// Media script & style hook
		add_action( 'admin_print_scripts', array(&$this, 'scripts_and_styles'));
		
		// Media tab hooks
		add_filter('media_upload_tabs', array(&$this, 'media_tab'));
		add_action('media_upload_eg', array(&$this, 'media_menu_handle'));
		
		// Image sideloader
		add_action('admin_init', array(&$this, 'attach_images'));
	}
}