<?php 
/*
   	Plugin Name: Easygram
   	Plugin URI: http://obox-design.com
   	Description: Import your Instagram content into your WordPress site!
	Author: Obox Design
	Tags: instagram, photography, galleries, obox, easygram
	Version: 1.0.1
	Author URI: http://www.obox-design.com
*/

define("EGDIR", ABSPATH."wp-content/plugins/easygram/");

/*******************/
/* Kick off Plugin */

if(!function_exists('eg_includes')) :
	function eg_includes(){
		global $egclass;
		include_once ("functions/load-includes.php");
		$egclass = new egclass();
		$egclass->initiate();	
	}
	add_action('plugins_loaded', 'eg_includes');
endif;

if ( isset($content_func) &&  ( (is_array( $content_func ) && ! empty( $content_func[1] ) && 0 === strpos( (string) $content_func[1], 'media' ) ) || 0 === strpos( $content_func, 'media' ) ) )
    wp_enqueue_style( 'media' );
    

if(!function_exists('eg_admin')) :
	function eg_admin(){
		global $eg_settings;
		$eg_settings = new eg_settings();
		$eg_settings->init();
	}
	add_action('plugins_loaded', 'eg_admin');
endif;

if(!function_exists('eg_activate')) :
	function eg_activate() {
	    add_option('eg_do_activation_redirect', true);
	}
	
	function eg_redirect() {
	    if (get_option('eg_do_activation_redirect', false)) {
	        delete_option('eg_do_activation_redirect');
	        wp_redirect(admin_url('admin.php?page=eg_general_settings'));
	    }
	}
	register_activation_hook(__FILE__, 'eg_activate');
	add_action('admin_init', 'eg_redirect');
endif;