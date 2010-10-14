<?php
/**
 * @package MyPicasaPluginOnline
 */
/*
Plugin Name: My Picasa WP Plugin
Plugin URI: http://www.vayama.com/
Description: Creates custom post type and displays picasa albums
Version: 0.0.1
Author: Mikhail Kozlov	
Author URI: http://mikhailkozlov.com
License: GPLv2
*/
/**
 * 
 */

$picasaOption;

class wpPicasa{
	static $options;
	function init($options) {
		global $picasaOptions;
		require dirname(__FILE__) .'/scb/load.php';
		$options = new scbOptions('picasaOptions_options', __FILE__,array(
				'v'=>'1.0',
				'username' => 'mkozlov'
		));
		if ( is_admin() ) {
			require_once dirname(__FILE__) . '/admin.php';
			new picasaOptions_Options_Page(__FILE__, $options);
			add_action( 'wp_ajax_picasa_ajax_import',array('wpPicasa','picasa_ajax_import') );
			add_action('admin_menu', array('wpPicasa','add_custom_boxes'));
		}	
		self::$options = $options;
		self::create_postType();
		self::load_picasa_javascript();		
	}
	
	function load_picasa_javascript(){
		if ( is_admin() ) {
			wp_enqueue_script('picasa_albums_admin', plugins_url('picasa'). '/scripts.js', array('jquery'), '1.0', true);
		}else{
			wp_enqueue_script('picasa_albums', plugins_url('picasa') . '/scripts.js', array('jquery'), '1.1', true);
		}	
	}
	
	/**
	 * register custom post type
	 * @return unknown_type
	 */
	function create_postType() {
		$labels = array(
		'name' => _x('Albums', 'post type general name'),
		'singular_name' => _x('Album', 'post type singular name'),
		'add_new' => _x('Import Album', 'Album'),
		'add_new_item' => __('Import Album'),
		'edit_item' => __('Edit Album'),
		'new_item' => __('New Album'),
		'view_item' => __('View Album'),
		'search_items' => __('Search Albums'),
		'not_found' =>  __('No Albums found'),
		'not_found_in_trash' => __('No Albums found in Trash'),
		'parent_item_colon' => ''
		);
		$supports = array('title','editor','author','thumbnail','excerpt','comments');
		register_post_type( 'album',array('labels' => $labels,'public' => true,'supports' => $supports));
		// add custom box
				
	}
	function add_custom_boxes(){
		add_meta_box( 'picasa-set','Album View',array('wpPicasa','picasa_album_view'),'album', 'normal', 'low');
	}
	/**
	 * box html
	 * @return unknown_type
	 */
	function picasa_album_view(){
		echo '<p>';
		echo 'albums will go here';	
		echo '</p>';
	}
	/**
	 * AJAX import
	 * @return unknown_type
	 */
	function picasa_ajax_import() {
		global $seodb, $wpdb;
		// /wp-admin/admin-ajax.php?action=myajax-submit
		echo 'ajax...';
		exit;
	}
	
}
add_action('init', array('wpPicasa','init'));