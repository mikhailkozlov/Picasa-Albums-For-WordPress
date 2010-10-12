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
 

add_action( 'init', 'create_albums' );
add_action( 'admin_init','_picasa_admin_init');



function create_albums() {
	
	
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

  $supports = array('title','revisions');

  register_post_type( 'album',
    array(
      'labels' => $labels,
      'public' => true,
      'supports' => $supports
    )
  );
}

function _picasa_admin_init(){
	add_meta_box( 'picasa_set','Album View','picasa_album_view','album', 'normal', 'low');
}

function _on_admin_init_h(){
	add_meta_box( 'picasa_set','Album View','picasa_album_view','album', 'normal', 'low');
	#add_action( 'wp_insert_post','cef_on_wp_insert_post', 10, 0 );	
	#add_action( 'save_post','cef_on_wp_update_post', 10, 0 );
	
}
function picasa_album_view(){
	echo '<p>';
	echo 'albums will go here';	
	echo '</p>';
}

function cef_on_wp_insert_post(){
	global $id;
	if(isset($_REQUEST['post_ID']) && $_REQUEST['post_ID'] > 0){
		$val = (isset($_REQUEST['_show_price_ticker'])) ? intval($_REQUEST['_show_price_ticker']):0;
		add_post_meta($_REQUEST['post_ID'], '_show_price_ticker', $val, true);
	}
}

function cef_on_wp_update_post(){
	$val = 0;
	if(isset($_REQUEST['post_ID']) && $_REQUEST['post_ID'] > 0){
		$val = (isset($_REQUEST['_show_price_ticker'])) ? intval($_REQUEST['_show_price_ticker']):0;
		update_post_meta($_REQUEST['post_ID'], '_show_price_ticker',$val);
	}
}