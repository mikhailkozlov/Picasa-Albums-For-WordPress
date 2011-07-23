<?php

/*
Plugin Name: Picasa Albums
Plugin URI: http://mikhailkozlov.com/picasa-albums-for-wordpress/
Description: Creates custom post type and displays picasa albums
Version: 1.0.6
Author: Mikhail Kozlov	
Author URI: http://mikhailkozlov.com
License: GPLv2 (or later) 
*/
date_default_timezone_set('America/Los_Angeles');

$picasaOption;
$path = str_replace('\\','/',dirname(__FILE__)); // windows scramble
require $path .'/scb/load.php';

// init picasa class
scb_init(array('wpPicasa','init'));

class wpPicasa{
	static $post_type='album';
	static $options=array(
				'v'=>'1.0.6',
				'key'=>'picasaOptions_options',
				'username' => '',
				'album_thumbsize'=>160,
				'album_thumbcrop'=>'yes',
				'albums_display'=>'rows', 
				'image_thumbsize'=>128, // 94, 110, 128, 200, 220, 288, 320, 400, 512, 576, 640, 720, 800, 912, 1024, 1152, 1280, 1440, 1600
				'image_thumbcrop'=>'yes', // true/false
				'image_maxsize'=>800, // lint to original
				'image_class'=>'picasa_image',
				'use_lightbox'=>true,
				'embed_image_thumbsize'=>128,
				'embed_image_maxsize'=>800,
				'gallery_path' => 'album',
				
	);
	function init($options=array()) {
		global $picasaOptions;
		$options=self::$options;
		$options = new scbOptions($options['key'], __FILE__,self::$options);
		if ( is_admin() ) {
			require_once dirname(__FILE__) . '/admin.php';
			new picasaOptions_Options_Page(__FILE__, $options);
			add_action( 'wp_ajax_picasa_ajax_import',array('wpPicasa','picasa_ajax_import') );
			add_action( 'wp_ajax_picasa_ajax_reload_images',array('wpPicasa','picasa_ajax_reload_images') );
			add_action( 'wp_ajax_picasa_ajax_image_action',array('wpPicasa','picasa_ajax_image_action') );
			add_action('admin_menu', array('wpPicasa','add_custom_boxes'));
		}
		self::load_picasa_javascript();
				
	}
	function _activate(){
		// set default option
		add_option('picasaOptions_options', serialize (self::$options),'','yes');
	}
	function load_picasa_javascript(){
		$path = basename(dirname(__FILE__));
		if ( is_admin() ) {
			wp_enqueue_script('json', '/wp-admin/load-scripts.php?c=1&load=json2', array(), '', true);
			wp_enqueue_script('picasa_albums_admin', plugins_url($path). '/admin/scripts.js', array(), '', true);
			wp_enqueue_style('picasa_albums_admin_css',plugins_url($path).'/admin/style.css');
			wp_enqueue_style('fancybox_css',plugins_url($path).'/fancybox/jquery.fancybox-1.3.4.css');
			wp_enqueue_script('fancybox', plugins_url($path) . '/fancybox/jquery.fancybox-custom.js', array(), '', true);
		}else{
			wp_enqueue_script('jquery');
			wp_enqueue_style('picasa_albums_css',plugins_url($path).'/style.css');
			wp_enqueue_style('fancybox_css',plugins_url($path).'/fancybox/jquery.fancybox-1.3.4.css');
			wp_enqueue_script('fancybox', plugins_url($path) . '/fancybox/jquery.fancybox-custom.js', array(), '', true);
			wp_enqueue_script('picasa_albums', plugins_url($path) . '/scripts.js', array(), '', true);
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
		$supports = array('title','author','comments');
		$args = array(
			'rewrite' =>array('slug'=>self::$post_type),
			'labels' => $labels,
			'public' => true,
			'show_ui' => true,
			'query_var' => true,
			'capability_type' => 'page',
			'hierarchical' => false,
			'publicly_queryable' => true,
			'menu_position'=>20,
			'supports' => $supports
		);
		// v.1.0.6 - rewrite if we have too
		$options = get_option(self::$options['key']);
		if(is_array($options) && array_key_exists('gallery_path', $options))
		{
			$args['rewrite'] = array('slug'=>$options['gallery_path']);
		}
		
		register_post_type( 'album',$args);
		register_taxonomy_for_object_type('album', 'album');

		add_filter('the_content',array('wpPicasa','picasa_album_filter'));
		// v 1.0.5
		// some themes call the_exerpt();
		add_filter('the_excerpt',array('wpPicasa','picasa_album_filter'));
		
	}
	function add_custom_boxes(){
		if(isset($_GET['action'])){
			add_meta_box( 'picasa-album','Album Details',array('wpPicasa','picasa_admin_album_view'),self::$post_type, 'normal', 'high');
			add_meta_box( 'picasa-album-images','Album Images',array('wpPicasa','picasa_admin_album_images'),self::$post_type, 'normal', 'high');
			add_meta_box( 'picasa-album-side','Maintenance Functions',array('wpPicasa','picasa_admin_album_import'),self::$post_type, 'side', 'low');
			remove_meta_box( 'slugdiv' , self::$post_type , 'normal' );
		}else{
			remove_meta_box( 'commentstatusdiv' , self::$post_type , 'normal' );
			remove_meta_box( 'authordiv' , self::$post_type , 'normal' ); 
			remove_meta_box( 'submitdiv' , self::$post_type , 'side' );
			remove_meta_box( 'slugdiv' , self::$post_type , 'normal' );
			
			
			add_meta_box( 'picasa-album','Import',array('wpPicasa','picasa_admin_import_album_view'),self::$post_type, 'normal', 'high');
			add_meta_box( 'picasa-album-side-promo','Picasa Album Pro',array('wpPicasa','picasa_admin_import_album_side'),self::$post_type, 'side', 'high');
		}
	}
	
	function picasa_admin_album_import(){
		global $post;
		self::decode_content($post->post_excerpt);
		echo '<div class="submitbox">
			<p>Will reload all data and erase any changes the you made!</p>
		';
		echo scbForms::input(array(
			'type' => 'button',
			'name' => 'import_album_images',
			'id' => 'import_album_images',
			'extra'=>'class="button" data="'.$post->post_excerpt['id'].'" authkey="'.$post->post_excerpt['authkey'].'"',
			'value' => 'Reload Images'
		));
		/**
		 *  need to add		
		echo scbForms::input(array(
			'type' => 'button',
			'name' => 'import_album',
			'id' => 'import_album',
			'extra'=>'class="button" style="float:right" ',
			'value' => 'Reload Details'
		));
		*/
		echo '</div>';
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	function picasa_admin_import_album_view(){
		global $post;
		$options = get_option(self::$options['key']);
		echo '<script type="text/javascript">';
		echo 'jQuery("#titlediv").hide();';
		echo '</script>';
		echo '
			<p>Please note: all new albums will be imported and marked as draft. All existing albums will remain untouched.</p>
			<input type="text" id="username" size="50" value="'.$options['username'].'" name="username">
			<input type="button" id="import_albums" class="button" value="Import" name="import_albums" /><span class="loader hide"><i>Loading... Do not reload this page!</i></span>
			<p>
				<a href="edit.php?post_type='.self::$post_type.'">View Albums</a>
			</p>
			
			
		';
	}
	function picasa_admin_import_album_side(){
		echo '
			<p>Do you need more features?<br /> Check out <a target="blank" href="http://mikhailkozlov.com/picasa_albums_pro/">Picasa Albums Pro</a>.</p>	
		';
	}
	/**
	 * box html
	 * @return unknown_type
	 */
	function picasa_admin_album_view(){
		global $post;
		self::decode_content($post->post_excerpt);
		if(is_array($post->post_excerpt)){
			echo '<script>';
			echo 'var album = '.json_encode($post->post_excerpt).';';
			echo '</script>';
				
			echo '<textarea id="excerpt" name="excerpt" style="display:none">'.json_encode($post->post_excerpt).'</textarea>';
			echo '
			<div class="inside">
				<img id="cover_image" src="'.self::parseThumb($post->post_excerpt['thumbnail']['url']).'" alt="album cover" ';
			echo (isset($options['album_thumbcrop']) && $options['album_thumbcrop'] == 'yes' && isset($post->post_excerpt['thumbnail'])) ? ' width="'.$post->post_excerpt['thumbnail']['height'].' height="'.$post->post_excerpt['thumbnail']['height'].'"':''; 
			echo 'style="float:left; margin-right:5px;"/>
				
				<ul class="inside">
					<li>Published: <strong>'.date('D F, jS Y',$post->post_excerpt['published']).'</strong></li>
					<li>Last updated:  <strong>'.date('D F, jS Y, H:i',$post->post_excerpt['updated']).'</strong></li>
					<li>Original Title:  <strong>'.utf8_decode($post->post_excerpt['title']).'</strong></li>
					<li>Links: <a href="'.$post->post_excerpt['links']['text/html'].'" >Album on Picasa</a> | <a href="'.$post->post_excerpt['links']['application/atom+xml'].'" >Picasa RSS</a></li>
				</il>
				<div class="clear"></div>
				<br />
				<div>
					<p><strong>Album Description:</strong></p>
					<textarea class="attachmentlinks" id="album_summary" tabindex="6" name="album[summary]" cols="40" rows="1">'.$post->post_excerpt['summary'].'</textarea>
					<p>You can provide your custom album description here.</p>
				</div>			
			</div>
			';
		}else{
			echo 'Error! Album data is corrupted! Try to delete this album and <a href="options-general.php?page=picasa-albums">reload</a> it from Picasa again.';
		}
	}
	/**
	 * displays edit page
	 * @return unknown_type
	 */
	function picasa_admin_album_images(){
		global $post;
		$options = get_option(self::$options['key']);
		self::decode_content($post->post_content);
		echo '<script>';
		echo 'var images = '.json_encode($post->post_content).';';
		echo '</script>';
		echo '<textarea id="content" name="content" style="display:none" class="albumpage">'.json_encode($post->post_content).'</textarea>';
		echo '<div class="inside">';		
		if(count($post->post_content) > 0){
			echo '<ul class="ui-sortable">';
			foreach($post->post_content as $i=>$image){
				echo '<li title="'.$image['summary'].'" id="order_'.$i.'"';
				echo '><img width="110" height="110" src="'.$image['fullpath'].'s110-c/'.$image['file'].'" alt="'.$image['summary'].'" class="';
				echo ($image['show'] == 'yes') ? '':'dimlight';
				echo '"/>';
				echo'<div>';
				echo '<a href="#'.$image['fullpath'].'s'.$options['album_thumbsize'].'-c/'.$image['file'].'" id="'.$image['id'].'" title="Set as album cover" class="icon cover_image" ref="'.$options['album_thumbsize'].'"></a>';
				echo '<a href="#hide" id="'.$image['id'].'" title="Show/Hide image from public gallery" class="icon hide_image ';
				echo ($image['show'] == 'yes') ? 'visible" >':'" >'; //echo ($image['show'] == 'yes') ? 'visible" ><span>hide</span><span style="display:none">show</span>':'" ><span style="display:none">hide</span><span>show</span>';
				echo '</a>';
				echo '<a href="'.$image['fullpath'].'s800/'.$image['file'].'" class="icon view_image fancybox" rel="album" title="';
				echo (!empty($image['summary'])) ? $image['summary']:$image['file'];
				echo '" >zoom</a>';
				echo'</div>';
				echo '</li>';
			}
			echo '</ul>';
		}else{
			echo 'No images yet! <a href="#load_imges_now" id="load_imges_now">Get them now!</a> ';
			print_r($post->post_content);
		}
		echo '
				<div class="clear"></div>
			</div>
		<div class="clear"></div>';
	}
	
	/**
	 * AJAX import
	 * @return unknown_type
	 */
	function picasa_ajax_import() {
		global $wpdb;
		$options = get_option(self::$options['key']);
		set_time_limit(300);
		echo 'doing ajax...';
		// time to curl
		$options['username'] = (isset($_GET['user'])) ? trim($_GET['user']):$options['username'];
		$xml= new wpPicasaApi($options['username'],array('thumbsize'=>$options['album_thumbsize']));
		$xml->getAlbums();
		$xml->parseAlbumXml(true);
		$q = 'SELECT ID, post_mime_type FROM '.$wpdb->posts.' WHERE post_type = \''.self::$post_type.'\' ';
		foreach($wpdb->get_results($q, ARRAY_A) as $i=>$row){
			$albums[$row['post_mime_type']] =$row['ID'];
		}
		foreach($xml->getData() as $aData){
			if(isset($albums) && is_array($albums) && array_key_exists($aData['id'],$albums)){
				// update existing album. images will not be updated
				// v 1.0.5
				// self::insertAlbums($aData,$albums[$aData['id']]);
				// we used to update here, but people did not want this.
			}else{
				//new album. images will BE imported
				$post_id = self::insertAlbums($aData,0);
				if(intval($post_id) > 0){
					// time to get images
					$x= new wpPicasaApi($options['username']);
					echo 'new album '.$aData['id'].' auth'.$aData['authkey'].' post id:'.$post_id.'<br />';
					$x->getImages($aData['id'],$aData['authkey']);
					$x->parseImageXml(true);
					self::insertImagesToAlbum($x->getData(),$post_id);
				}
			}
		}
		exit;
	}
	/**
	 * loads images from api
	 * @return bool
	 */
	function picasa_ajax_reload_images() {
		global $wpdb;
		if(isset($_GET['post_ID']) && isset($_GET['id'])){
		$options = get_option(self::$options['key']);
			// time to curl
			$xml= new wpPicasaApi($options['username']);
			$xml->getImages($_REQUEST['id'],$_REQUEST['authkey']);
			$xml->parseImageXml(true);
			self::insertImagesToAlbum($xml->getData(),$_GET['post_ID']);
			echo '{"r":1,"m":"done!"}';
		}else{
			echo '{"r":0,"m":"please provide post and album id"}';
		}
		exit;
	}
	function picasa_ajax_image_action(){
		global $wpdb;
		switch ($_GET['todo']){
			case 'saveAlbum':
				if(isset($_REQUEST['post_ID']) && intval($_REQUEST['post_ID']) >0){
					$aOrder = $_REQUEST['order'];
					$aImages = false;
					$q="SELECT post_excerpt, post_content FROM ".$wpdb->posts." WHERE ID=".intval($_REQUEST['post_ID']);
					$row = $wpdb->get_row($q);
					if(isset($row->post_content)){
						self::decode_content($row->post_content);
						$aImages = $row->post_content;
					}
					if($aImages!== false){
						$aImages = self::sortArrayByArray($aImages,$aOrder,$_REQUEST['id']);
					}					
					echo json_encode($aImages);
				}else{
					echo '{"r":0,"m":"please provide post and album id"}';
				}
			break;
			case 'reloadDetails':
				
			break;
		}
		exit;
	}
	
	// apply sort and show not show
	function sortArrayByArray($array,$order,$ids){
		$ordered = array();
		foreach($order as $key=>$value) {
			if(array_key_exists($value,$array)) {
				if(array_key_exists($array[$value]['id'],$ids)){
					$array[$value]['show'] = $ids[$array[$value]['id']];
				}
				$ordered[$key] = $array[$value];
				unset($array[$value]);
			}
		}
		return $ordered + $array;
	}
	function insertAlbums($data,$id=0){
		global $current_user;
      	get_currentuserinfo();
		$post = array(
			'post_status' => 'draft', 
			'post_type' => 'album',
			'post_title' => $data['title'],
			'post_name' => $data['name'],
			'post_mime_type'=>$data['id'],
			'post_date_gmt' => date('Y-m-d H:i:s',$data['published']),
			'post_modified_gmt' => date('Y-m-d H:i:s',$data['updated']),
			'post_author' => $current_user->ID,
			'post_excerpt' => json_encode($data)
		);
		if(intval($id) > 0){
			$post['ID'] = $id;
			wp_update_post($post);
			return $id;
		}
		$date = new DateTime(date('Y-m-d H:i:s',$data['published']),new DateTimeZone('Europe/London'));
		$date->setTimezone(new DateTimeZone(date('e')));
		$post['post_date'] = $date->format('Y-m-d H:i:s');
		$date = new DateTime(date('Y-m-d H:i:s',$data['updated']),new DateTimeZone('Europe/London'));
		$date->setTimezone(new DateTimeZone(date('e')));
		$post['post_modified'] = $date->format('Y-m-d H:i:s');
		$id=wp_insert_post($post);
		return $id; 		
	}
	function insertImagesToAlbum($data,$id=0){
		global $current_user;
      	get_currentuserinfo();
		$post = array(
			'post_content' => json_encode($data)
		);
		if(intval($id) > 0){
			$post['ID'] = $id;
			$id=wp_update_post($post);
		}
		return $id;
	}
	// Adding a new rule
	function wp_insertPicasaRules($rules){
		$newrules = array();
		// v.1.0.6 - rewrite if we have too
		$options = get_option(self::$options['key']);
		if(is_array($options) && array_key_exists('gallery_path', $options))
		{
			$path = $options['gallery_path'];
			$newrules['('.$path.')/(\d*)$'] = 'index.php?post_type='.self::$post_type.'&post_name=$matches[2]';
			// issie #2 fix
			$newrules['('.$path.')/page/?([0-9]{1,})/?$'] = 'index.php?post_type='.self::$post_type.'&paged=$matches[2]';		
			$newrules['('.$path.')$'] = 'index.php?post_type='.self::$post_type;
		}else{
			$newrules['(album)/(\d*)$'] = 'index.php?post_type=$matches[1]&post_name=$matches[2]';
			// issie #2 fix
			$newrules['(album)/page/?([0-9]{1,})/?$'] = 'index.php?post_type=$matches[1]&paged=$matches[2]';		
			$newrules['(album)$'] = 'index.php?post_type=$matches[1]';
		}
		return $newrules + $rules;
	}
	
	// Adding the id var so that WP recognizes it
	function wp_insertPicasaQueryVars($vars){
	    array_push($vars, 'post_name');
	    return $vars;
	}
	/**
	 * 
	 * 
	 * 
	 * @param $content
	 * @return html
	 */
	function picasa_album_filter($content){
		global $post;
		$options=self::$options;
		$options = array_merge($options,get_option($options['key']));
		if(get_post_type() == self::$post_type){
			if(is_single()){
				self::decode_content($post->post_content);
				// v.1.0.6 - addign hooks to overwrite default style
				if( function_exists( wp_picasa_single_view_filter) ){
					$res = wp_picasa_single_view_filter($post,$options);
				}
				else
				{
					$res = '';
					if(!empty($post->post_content) && is_array($post->post_content)){
						foreach($post->post_content as $i=>$aImage){
							if($aImage['show'] == 'yes'){
								$res .= '
										<div style="width: '.($options['image_thumbsize']+10).'px;" class="wp-caption alignleft '.$options['image_class'].'">
											<a href="'.$aImage['fullpath'].'s'.$options['image_maxsize'].'/'.$aImage['file'].'" data-rel="'.$post->post_name.'" rel="nofollow" class="fancybox" title="';
								$res.=(!empty($aImage['summary'])) ? $aImage['summary']:$aImage['file'];
								$res.='">
												<img src="'.$aImage['fullpath'].'s'.intval($options['image_thumbsize']);
								$res.=($options['image_thumbcrop'] == 'yes') ? '-c':'';
								$res.='/'.$aImage['file'].'"';
								$res .= ($options['image_thumbcrop'] == 'yes' && isset($aImage['thumbnail']) ) ? ' width="'.$aImage['image_thumbsize'].'" height="'.$aImage['image_thumbsize'].'" ':' ';
								$res.=' class="size-medium" alt="" />
											</a>
											<p class="wp-caption-text" style="display:none">';
								$res.=(!empty($aImage['summary'])) ? $aImage['summary']:$aImage['file'];
								$res.='</p>
										</div>
								'; 
							}
						}
					}else{
						$res = 'Error. Please  comeback soon.';
					}
				}
				return $res;			
			}else{
				self::decode_content(&$post->post_excerpt);
				// v.1.0.6 - addign hooks to overwrite default style
				if( function_exists( wp_picasa_list_view_filter) ){
					$res = wp_picasa_list_view_filter($post,$options);
				}
				else
				{

					$res = '
						<div>
							<div style="" class="wp-caption alignleft">
								<a href="'.get_permalink().'">
									<img class="size-medium" title="'.$post->post_excerpt['title'].'" src="'.self::parseThumb($post->post_excerpt['thumbnail']['url']).'" alt=""';
					$res .= ($options['album_thumbcrop'] == 'yes') ? ' width="'.$post->post_excerpt['thumbnail']['height'].' height="'.$post->post_excerpt['thumbnail']['height'].'" ':' '; 
					//			<img height="'.$post->post_excerpt['thumbnail']['height'].'" width="'.$post->post_excerpt['thumbnail']['width'].'" class="size-medium" title="'.$post->post_excerpt['title'].'" alt="" src="'.$post->post_excerpt['thumbnail']['url'].'" />
					$res .= ' /></a>
								<p class="wp-caption-text" style="display:none">'.$post->post_excerpt['title'].'</p>
							</div>
							'.$post->post_excerpt['summary'].'
							<div style="clear:both"></div>
						</div>
					';
				} 
				return $res;			
			}
		}else{
			return $content;
		}		 
	}
	
	function decode_content(&$c){
		if(!is_array($c)){
			$c =  json_decode(htmlspecialchars_decode(stripcslashes($c)),true);
		}
	}
	function parseThumb($path){
		$options=self::$options;
		$options = array_merge($options,get_option($options['key']));
		$path = explode('/',$path);
		$size= (count($path)-2);
		$path[$size] ='s'.$options['album_thumbsize'];
		$path[$size] .= ($options['album_thumbcrop'] == 'yes')? '-c':''; 
		return implode('/',$path);
	}
	/**
	 * deactivation hook
	 */
	function picasa_albums_cleanup(){
		global $wpdb;
		// remove posts
		$q='DELETE FROM '.$wpdb->posts.' WHERE post_type=\''.self::$post_type.'\'';
		$wpdb->query($q);
		// remove settings
		delete_option(self::$options['key']);
	}
}
//register_activation_hook( __FILE__, array('wpPicasa','_activate') );

//add_action('init', array('wpPicasa','init'));


class wpPicasaApi{
	private $xml;
	private $data;
	private $user;
	
	private $params=array(
		'thumbsize'=>160
	);
	
	function __construct($user,$params=array()){
		$this->user = $user;
		$this->_setParams($params);		
	}
	function __get($key){
		return (!isset($this->$key)) ? $this->$key:null;
	}
	function getData(){
		return $this->data;
	}
	
	
	
	/** UTILS **/
	// set addtional params
	private function _setParams($params=array()){
		if(is_array($params)){
			foreach($this->params as $k=>$v){
				if(array_key_exists($k,$params)){
					$this->params[$k]=$params[$k];
				}
			}
		}
	}
	
	private function _postTo($url, $data=array(), $header=array()) {
		
		//check that the url is provided
		if (!isset($url)) {
			return false;
		}
		
		//send the data by curl
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if (count($data)>0) {
			//POST METHOD
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		} else {
			$header[] = array("application/x-www-form-urlencoded");
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		
		$response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
		
		//print_r($info);
		//print $response;
		if($info['http_code'] == 200) {
			return $response;
		} elseif ($info['http_code'] == 400) {
			throw new Exception('Bad request - '.$response);
		} elseif ($info['http_code'] == 401) {
			throw new Exception('Permission Denied - '.$response);
		} else {
			return false;
		}
	}	
	private function _getXml($url, $header=array()) {
		//check that the url is provided
		if (!isset($url)) {
			return false;
		}
		//send the data by curl
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST,0); // do not use POST to get xml feeds. GET only!!!
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //array('Content-type: application/atom+xml','Content-Length: 2000')
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		$response = curl_exec($ch);
		if(intval(curl_errno($ch)) == 0){
        	$this->xml = $response;
		}else{
			$this->xml=null;
			$this->error = curl_error($ch);
		}
        $info = curl_getinfo($ch);
        curl_close($ch);
		
		//print_r($info);
		//print $response;
		if($info['http_code'] == 200) {
			return true;
		} elseif ($info['http_code'] == 400) {
			throw new Exception('Bad request - '.$response .' URL: '.$url);
			return false;
		} elseif ($info['http_code'] == 401) {
			throw new Exception('Permission Denied - '.$response);
			return false;
		} else {
			return false;
		}
		return false;
	}

	
	
	/****** 		Public getters 		********/
	function getAlbums(){
		$header = array( 
		    "MIME-Version: 1.0", 
		    "Content-type: text/html", 
		    "Content-transfer-encoding: text" 
		);
		$url='http://picasaweb.google.com/data/feed/api/user/'.$this->user.'?kind=album&thumbsize='.$this->params['thumbsize'].'c';
		$url.='&access=public';
		return $this->_getXml($url,$header);
	}
	function getImages($aid,$authkey=null){
		$header = array( 
		    "MIME-Version: 1.0", 
		    "Content-type: text/html", 
		    "Content-transfer-encoding: text" 
		);
		//http://picasaweb.google.com/data/feed/api/user/userID/albumid/albumID
		$url='http://picasaweb.google.com/data/feed/api/user/'.$this->user.'/albumid/'.$aid.'?kind=photo';
		// may be we need to pass key here
		$ch = curl_init($url);
		return $this->_getXml($url,$header);
	}
	
	
	/****** 		parse XML 		********/
	function parseAlbumXml($killxml=false){
		$xml = new SimpleXMLElement($this->xml);
		$xml->registerXPathNamespace('media', 'http://search.yahoo.com/mrss/'); // define namespace media
		$xml->registerXPathNamespace('gphoto', 'http://schemas.google.com/photos/2007'); // define namespace media
		$xml->registerXPathNamespace('georss', 'http://www.georss.org/georss'); // define namespace media
		$xml->registerXPathNamespace('gml', 'http://www.opengis.net/gml'); // define namespace media

		#print_r($xml);
		if(count($xml->entry) > 0){
			foreach($xml->entry as $i=>$oAlbum){
				$aAlbum = array(
					'author'=>array(
						'name'=>(string)$oAlbum->author->name, // Mikhail Kozlov
						'uri'=>(string)$oAlbum->author->uri //http://picasaweb.google.com/kozlov.m.a
					), // will keep this on record in case we decide to go with more than one album
					'id'=> (Array)$oAlbum->xpath('./gphoto:id'), //5516889074505060529
					'name'=>'',//20100902RussiaOddThings
					'authkey'=>'',
					'published'=>strtotime($oAlbum->published), // strtotime(2010-09-11T04:58:08.000Z);
					'updated'=>strtotime($oAlbum->updated),// // strtotime(2010-09-11T04:58:08.000Z);
					'title' =>	(string)$oAlbum->title,//2010-09-02 - Russia - Odd Things
					'thumbnail' => (Array)$oAlbum->xpath('./media:group/media:thumbnail'), // 
					'latlong' => '', //
					'summary' =>addslashes((string) $oAlbum->summary), //Some things in Russia make you wonder
					'rights' => (string)$oAlbum->rights, //public
					'links' => array(
						'text/html'=>'', //http://picasaweb.google.com/kozlov.m.a/20100902RussiaOddThings
						'application/atom+xml'=>'' //http://picasaweb.google.com/data/feed/api/user/kozlov.m.a/albumid/5516889074505060529
					)
				);
				foreach($oAlbum->link as $oLink){
					$a = (Array)$oLink->attributes();
					$a = $a['@attributes'];
					if($a['rel'] == 'alternate' || $a['rel'] == 'self'){
						$aAlbum['links'][$a['type']] = $a['href'];
					}
				}
				unset($oLink);
				$aAlbum['thumbnail'] = (Array)$aAlbum['thumbnail'][0];
				$aAlbum['thumbnail'] = $aAlbum['thumbnail']['@attributes'];
				$aAlbum['latlong'] = ( $oAlbum->xpath('./georss:where') !== false && $oAlbum->xpath('./georss:where/gml:Point') !== false ) ? (Array)$oAlbum->xpath('./georss:where/gml:Point/gml:pos'):array(); // 
				$aAlbum['latlong'] = (isset($aAlbum['latlong'][0])) ? explode(' ',(string)$aAlbum['latlong'][0]):array();
				$aAlbum['latlong'] = (count($aAlbum['latlong']) == 1) ? false:$aAlbum['latlong'];
				$aAlbum['id'] = (string)$aAlbum['id'][0];
				$url = parse_url($aAlbum['links']['text/html']);
				$tmp = explode('/',$url['path']);
				$aAlbum['name']=end($tmp);
				// if we use auth set authkey
				if(!empty($this->_authCode)){
					parse_str($url['query'], $url['query']);
					$aAlbum['authkey']=$url['query']['authkey'];
				}				
				unset($tmp);
				$this->data[$aAlbum['name']]=$aAlbum;
				unset($aAlbum);				
			}
			unset($oAlbum);
		}
		unset($xml);
		if($killxml){
			unset($this->xml);
		}
	}

	function parseImageXml($killxml=false){
		$xml = new SimpleXMLElement($this->xml);
		$xml->registerXPathNamespace('media', 'http://search.yahoo.com/mrss/'); // define namespace media
		$xml->registerXPathNamespace('gphoto', 'http://schemas.google.com/photos/2007'); // define namespace media
		$xml->registerXPathNamespace('georss', 'http://www.georss.org/georss'); // define namespace media
		$xml->registerXPathNamespace('gml', 'http://www.opengis.net/gml'); // define namespace media
		$xml->registerXPathNamespace('exif', 'http://schemas.google.com/photos/exif/2007'); // define namespace media
		if(count($xml->entry) > 0){
			$c=0;
			foreach($xml->entry as $i=>$oImage){
				$c++;
				$aImage = array(
					'id'=> (Array)$oImage->xpath('./gphoto:id'), //5516889074505060529
					'published'=>strtotime($oImage->published), // strtotime(2010-09-11T04:58:08.000Z);
					'updated'=>strtotime($oImage->updated),// // strtotime(2010-09-11T04:58:08.000Z);
					'file' =>(string)$oImage->title,//2010-09-02 - Russia - Odd Things
					'fullpath' =>$oImage->content,//2010-09-02 - Russia - Odd Things
				   	'width'=>(Array)$oImage->xpath('./gphoto:width'), // width of the original in px
				    'height'=>(Array)$oImage->xpath('./gphoto:height'), // height of the original in px 
				    'size'=>(Array)$oImage->xpath('./gphoto:size'), // file size of the original in kb				
					'latlong' => '', //
					'thumbnail' => (Array)$oImage->xpath('./media:group/media:thumbnail'), //
					'summary' =>addslashes((string) $oImage->summary), //Some things in Russia make you wonder
					'rights' => (Array)$oImage->xpath('./gphoto:access'), //public
					'pos'=>$c,
					'show'=>'yes',
					'links' => array(
						'text/html'=>'', //http://picasaweb.google.com/kozlov.m.a/20100902RussiaOddThings
						'application/atom+xml'=>'' //http://picasaweb.google.com/data/feed/api/user/kozlov.m.a/albumid/5516889074505060529
					)
				);
				
				foreach($oImage->link as $oLink){
					$a = (Array)$oLink->attributes();
					$a = $a['@attributes'];
					if($a['rel'] == 'alternate' || $a['rel'] == 'self'){
						$aImage['links'][$a['type']] = $a['href'];
					}
				}
				unset($oLink);
				$aImage['thumbnail'] = (Array)$aImage['thumbnail'][0];
				$aImage['thumbnail'] = $aImage['thumbnail']['@attributes'];
				// some trickery to get image path
				$aImage['fullpath'] = (Array)$aImage['fullpath'];
				$aImage['fullpath'] =str_replace($aImage['file'],'',$aImage['fullpath']['@attributes']['src']);
				// flatten id
				$aImage['id'] = (string)$aImage['id'][0];
				
				// private albums do not seem to have georss.
				$ns = $xml->getDocNamespaces();
				if(array_key_exists('georss',$ns)){
					// lat long as array
					$aImage['latlong'] = (Array)$oImage->xpath('./georss:where/gml:Point/gml:pos');
					$aImage['latlong'] = (isset($aImage['latlong']) && isset($aImage['latlong'][0])) ? explode(' ',(string)$aImage['latlong'][0]):array();
					$aImage['latlong'] = (count($aImage['latlong']) == 1) ? false:$aImage['latlong'];
				}
				// flatten right, size, width, height
				$aImage['size'] = (string)$aImage['size'][0];
				$aImage['rights'] = (string)$aImage['rights'][0];
				$aImage['height'] = (string)$aImage['height'][0];
				$aImage['width'] = (string)$aImage['width'][0];
				unset($tmp);
				$this->data[]=$aImage;
				unset($aImage);				
			}
			unset($oImage);
		}
		unset($xml);
		if($killxml){
			unset($this->xml);
		}
	}
}

// new post type has to added at init. else rewrite does not work
add_action('init',array('wpPicasa','create_postType'));

add_filter('rewrite_rules_array',array('wpPicasa','wp_insertPicasaRules'));
add_filter('query_vars',array('wpPicasa','wp_insertPicasaQueryVars'));
add_filter('init','flushRules');

if(!function_exists('flushRules')){
	// Remember to flush_rules() when adding rules
	function flushRules(){
		global $wp_rewrite;
	   	$wp_rewrite->flush_rules();
	}
}

register_deactivation_hook( __FILE__, array('wpPicasa','picasa_albums_cleanup'));
?>