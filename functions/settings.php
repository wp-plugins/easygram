<?php class eg_settings
{	
	// Script inclusion
	function scripts(){
		global $pagenow;
		
		// jQuery inclusion
		wp_enqueue_script( "jquery");
		
		// Admin Scripts
		if(is_admin() && isset($_REQUEST['page']) && $_REQUEST['page'] == "eg_general_settings") :
			// Scripts
			wp_enqueue_script( 'eg-admin', plugins_url('easygram/js/admin.js'), array( 'jquery' ) );
		endif;
	}
	
	// Admin Settings Styling
	function admin_styles(){
		global $pagenow;
		// Admin Styles
		if(is_admin() && isset($_REQUEST['page']) && $_REQUEST['page'] == "eg_general_settings") :
			wp_register_style( 'eg-admin', plugins_url("easygram/css/admin.css"));
			wp_enqueue_style( 'eg-admin' );
		endif;
	}
	
	
	
	
	// Option deleter
	function clear_options(){
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';	
		delete_option('eg_'.$active_tab.'_options');
		wp_redirect("?page=eg_general_settings&tab=$active_tab");
		
	}
	
	// Add the settings to the menu	
	function eg_menu() {
		$this->pagehook = add_object_page( 'Easygram', 'Easygram', 'administrator', 'eg_general_settings', array(&$this, 'eg_general'), 'http://obox-design.com/images/ocmx-favicon.png' );
	}
	
	// Show the save/reset buttons, not yet applicable for Easygram, but will be used when more functions land up in later versions
	function buttons(){
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
		$html = "";
		$html .= '<a id="clear" href="?page=eg_general_settings&tab=' . $active_tab . '&refresh" class="clear-settings">Clear Settings</a>' ;
		$html .= get_submit_button("Save Changes", "primary", "submit", false);
		$html = '<span>' . $html . '</span>';
		return $html;
	}
	
	// Load the 
	function eg_admin_tabs(){
		$tabs = array("general" => "General");
		return apply_filters('eg_admin_tabs', $tabs);
	}
	
	// The body of the settings Panel
	function eg_general() {
		
		$tabs = $this->eg_admin_tabs();
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general'; ?>
		<div class="wrap">	
			<div id="icon" class="instagram32"></div>
			<h2>Easygram</h2>
			<?php settings_errors(); ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach($tabs as $tab => $label) : ?>
				<a href="?page=eg_general_settings&tab=<?php echo $tab; ?>" class="nav-tab <?php echo $active_tab == $tab ? 'nav-tab-active' : ''; ?>"><?php echo $label; ?></a>
				<?php endforeach; ?> 
			</h2>
			<form  method="post" action="options.php" enctype="multipart/form-data">
				<?php settings_fields('eg_'.$active_tab.'_options'); ?>
				<?php do_settings_sections( 'eg_'.$active_tab.'_options' ); ?>
			</form>
	
		</div>
	<?php
	}
	
	// General Options Intro
	function eg_general_options_callback() {
		echo '<p class="top">Easygram is a plugin which pulls in your Instagram photos into WordPress. Get started by linking your account to WordPress.</p>';
	}
		
	// Create the Easygram options
	function eg_initialize_options() {
		global $eg_notice;
		$eg = new egclass();
		$option = 'eg_accesstoken';
		
		// Logout code
		if(isset($_GET['logout'])) :
			delete_option($option);
			
		// Login Auth code
		elseif(isset($_GET['code'])) :
			$code = $_GET['code'];
			$token = $eg->auth($code);
			if ( is_wp_error( $token ) ) :
			   	$error_string = $token->get_error_message();
			   	$eg_notice = '<div id="message" class="error"><p>' . _('Hang on! We had an issue whilst authenticating your account: ') . $error_string . '</p></div>';
			elseif(isset($token)) :
			   	$eg_notice = '<div id="message" class="updated"><p>' . _('Your account has been successfully authenticated.') . '</p></div>';
				update_option($option, $token);
			endif;
		endif;
		
		
		// If the theme options don't exist, create them.
		if(!get_option('eg_general_options')) :
			add_option('eg_general_options');
		endif;

		add_settings_section(
			'eg_general_settings',				// Page on which to add this section of options
			'Easygram Options',						// Title to be generaled on the administration page
			array(&$this, 'eg_general_options_callback'),	// Callback used to render the description of the section
			'eg_general_options'				// ID used to identify this section and with which to register options
		);
		
		// Only show the help if we're logged in
		if(get_option($option) && !isset($_GET['logout'])) :
			add_settings_field(
				'help', 'Getting started', array(&$this, 'eg_help'), 'eg_general_options', 'eg_general_settings'
			);
		endif;
		
		add_settings_field(
			'auth', 'Authorization', array(&$this, 'eg_auth'), 'eg_general_options', 'eg_general_settings', array('url' => 'https://api.instagram.com/oauth/authorize/?client_id=d25a442159e1442d95aeddc204dce20e&response_type=code&redirect_uri='.$eg->redirect_url)
		);
		
		// Register our settings as defined above
		register_setting(
			'eg_general_options', 'eg_general_options', array(&$this, 'handle_form')
		);
	}
	
	// Auth button HTML is here.
	function eg_auth($args) {
		global $eg_notice;
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
		$option = 'eg_accesstoken';
		$options = get_option($option);
		
		// If we have some info we want to display, kick it out here
		if(isset($eg_notice))
			echo $eg_notice;
			
		if(!get_option($option))
			echo '<a href="'.$args['url'].'">Authorize via Instagram</a>';
		else
			echo '<a id="logout" href="'.admin_url('admin.php?page=eg_general_settings&logout=1').'">Click here to Logout or switch users.</a>';
		echo '<a class="obox-credit" href="http://www.obox-design.com"><img src="'.plugins_url("easygram/css/images/").'obox-logo.png" alt="WordPress Plugins by Obox" /></a>';
	}
	function compat_check(){
		global $egclass;
		// Just make sure we're on the right page first
		if(is_admin() && isset($_REQUEST['page']) && $_REQUEST['page'] == "eg_general_settings") :
			// If we haven't done so already,run a test to make sure the plugin works.
			if (get_transient("eg_upload_check") == false) :
				$mediatest = media_sideload_image('http://easygram.oboxsites.com/test.gif', 0);
				if(is_wp_error($mediatest)) :
					echo '<div id="message" class="error"><p>' . _('There was an issue when performing a simple import test. Correct the following issue before attaching your images. ' . $mediatest->get_error_message()) . '</p></div>';
				else :
					// Extract the image URL to get the attachment ID
				    $new_tag_url = $egclass->get_image_src($mediatest);
		    
		    		// Get the attachment ID from the uploaded image URL
				    $attachment_id = $egclass->get_attachment_id_from_url($new_tag_url);
				    wp_delete_attachment($attachment_id);
				    set_transient("eg_upload_check", true, ( 30 * 60 * 60 * 24 ));
				endif;
			elseif (!function_exists('curl_init')) :
				echo '<div id="message" class="error"><p>' . _('This plugin requires the cURL PHP extension to be installed.') . '</p></div>';
			endif;
		endif;
	}
	
	// Help HTML sits here
	function eg_help($args) {		
		echo '<div class="help">
				<p>Now that you\'ve linked your Instagram account to your WordPress account let us give you a run down of how to use our plugin:</p>
	    
				<ul class="easygram-steps">
			    	<li class="step">
			        	<img src="'.plugins_url("easygram/css/images/").'step-1.png" alt="Add a new Album or Post" />
			            <h4>Add a new Album or Post</h4>
			            <p>When adding a new Album or Post, click on the Media upload icon above the text editor.</p>
			        </li>
			        <li class="step">
			        	<img src="'.plugins_url("easygram/css/images/").'step-2.png" alt="Click the Instagram tab" />
			            <h4>Click the Instagram tab</h4>
			            <p>The media upload window will pop open, once it\'s opened click the Instagram tab.</p>
			        </li>
			        <li class="step">
			        	<img src="'.plugins_url("easygram/css/images/").'step-3.png" alt="Choose your photos" />
			            <h4>Choose your photos and Publish!</h4>
			            <p>When the Instagram tab is clicked all your instagram images will display. Check on the photos you want in your post and click <strong>Attach images to post</strong>.</p>
			        </li>
			    </ul>
		   </div>';		
	}
	
	// Form item HTML sits here.
	function eg_input($args) {
		// First, we read the options collection
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';
		$option = 'eg_'.$active_tab.'_options';
		$options = get_option($option);
		$label = "";
		$id = $args[0];
		$type = $args[1];
		$default = $args[2];
		$excerpt = $args[3];
		if(isset($args[4]))
			$url = $args[4];
		
		if($type == "checkbox" && isset($options[$id])) :
			$value = "1";
		elseif(isset($options[$id])) :
			$value = $options[$id];
		else :
			$value = $default;
		endif;
		
		if ($type == "checkbox") :
			$checked = '';
			if(isset($options[$id])) { $checked = 'checked="checked"'; };
			$input = '<input type="checkbox" id="' . $id . '" name="' . $option . '[' . $id . ']" ' . $checked . '/>'; 
		elseif ($type == "file") :
			$checked = '';
			$count = 0;
			$selected = 0;
			$images = "";
			$uploaded = array();
			$uploaded = get_posts( array( 'post_type' => 'attachment', 'meta_key' => '_eg_related_image', 'meta_value' => $id, 'orderby' => 'none', 'nopaging' => true ) );
			if($value != ""){
				$checked = 'checked="checked"';
			}
			else {$uploadclass='';
				$uploadclass='class="no_general"';}
			$input = '<label class="clear" for="clear-' . $id . '"><input id="clear-' . $id . '" name="" type="checkbox" ' . $checked . ' /> Enable ' . $id . ' </label>';
			$input .= '<div ' . $uploadclass . '>';
			$input .= '<input type="file" id=upload-"' . $id . '" name="' . $id . '_file" />';
			$input .= '<input id="no-' . $id . '" name="' . $option . '[' . $id . ']" type="radio" value="" ' . $checked . '" class="no_general" />';
			$checked = '';
			if(!empty($uploaded)) :
				foreach($uploaded as $image) :
					$full = wp_get_attachment_url($image->ID, "full"); 
					$thumb = wp_get_attachment_url($image->ID, "thumb"); 
					$checked = "";
					$class = "";
					if($value == $full){$checked .= 'checked="checked"'; $class = ' active'; $selected = $count;}						

					$images .= '<li class="default-header' . $class . '">';
						$images .= '<label>';

							$images .= '<input id="' . $id . '" name="' . $option . '[' . $id . ']" type="radio" value="' . $full . '" ' . $checked . ' class="no_general" />';
							$images .= '<img src="' . $thumb . '" alt="" title="" />';
						$images .= '</label>';
					$images .= '</li>';
					$count++;
				endforeach;
				
			endif;
			if(isset($args[4])) :
				foreach($args[4] as $image => $path) :
					$checked = "";
					$class = "";
					if($value == $path){$checked = 'checked="checked"'; $class = ' active'; $selected = $count;}
					$images .= '<li class="default-header' . $class . '">';
						$images .= '<label>';
							
							$images .= '<input id="' . $id . '" name="' . $option . '[' . $id . ']" type="radio" value="' . $path . '" ' . $checked . ' class="no_general" />';
							$images .= '<img src="' . str_replace("bg/", "bg/thumbs/", $path) . '" alt="" title="" width="150" />';
						$images .= '</label>';
					$images .= '</li>';
					$count++;
				endforeach;
				
			endif;
			if(isset($args[4]) || !empty($uploaded)) :				
				$images = '<div class="available-headers"><ul>'.$images.'</ul></div>';
			endif;
			$input = $input.$images;
		elseif ($type == "memo") :
			$input = '<textarea id="' . $id . '" name="' . $option . '[' . $id . ']" cols="50" rows="5">' . $value . '</textarea>'; 
		elseif ($type == "select") :
			$options = $args[4];
			$input = '<select id="' . $id . '" name="' . $option . '[' . $id . ']">' ;
			if(!empty($options)) :
				foreach($options as $option => $option_value) :
					$selected = '';
					if($value == $option_value){$selected = 'selected="selected"';}
					$input .= '<option value="' . $option_value . '" '. $selected . '>' . $option . '</option>';
				endforeach;
			endif;
			$input .= '</select>'; 
		elseif ($type == "password") :
			$input = '<input type="password" id="' . $id . '" name="' . $option . '[' . $id . ']" value="' . $value . '" />'; 
		elseif ($type == "button") :
			$input = '<input type="button" class="button-primary" onclick="javascript:window.location=\'' . $url . '\');" id="' . $id . '" name="' . $option . '[' . $id . ']" value="' . $value . '" />'; 
		elseif ($type == "html") :
			$input = '';
		else :
			$input = '<input type="text" id="' . $id . '" name="' . $option . '[' . $id . ']" value="' . $value . '" />'; 
		endif;
		
		if(!empty($excerpt))
			$label = '<label for="' . $id .'"> '  . $excerpt . '</label>'; 
		
		$input .= '</div>';
		$html = $input.$label;
		
		echo $html;
	
	}
	
	// Save function, deals with file uploads if we have any
	function handle_form($input){
		$newinput = $input;
		$files = $_FILES;
		foreach($files as $input => $values) :	
			if(!empty($values["name"])) :	
				$id = media_handle_upload($input, 0);        
				$attachment = wp_get_attachment_image_src( $id, "full");
				$option = 	str_replace("_file", "", $input);
				update_post_meta($id, '_eg_related_image', $option);
				$newinput[$option] = $attachment[0];
			endif;
		endforeach;
		return $newinput;
	}
	
	function init() {
		// Check if this plugin will actually work!
		add_action('admin_notices', array(&$this, 'compat_check'));
		
		// Script loaders
		add_action( 'admin_print_styles', array(&$this, 'admin_styles'));
		add_action( 'admin_print_scripts', array(&$this, 'scripts'));

		// Menu hook		
		add_action( 'admin_menu', array(&$this, 'eg_menu'));
		
		// Setting initializer
		add_action( 'admin_init', array(&$this, 'eg_initialize_options'));	

		// Setting Reset Hook
		if(isset($_GET["refresh"]))
			add_action( 'admin_init', array(&$this, 'clear_options'));	
			
	}
}