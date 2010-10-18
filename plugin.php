<?php
date_default_timezone_set('America/Los_Angeles');
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
$path = str_replace('\\','/',dirname(__FILE__)); // windows scramble
require $path .'/scb/load.php';
scb_init(array('wpPicasa','init'));

class wpPicasa{
	static $post_type='album';
	static $options=array(
				'v'=>'1.0',
				'key'=>'picasaOptions_options',
				'username' => '',
				'album_thumbsize'=>288,
				'album_thumbcrop'=>true, 
				'image_thumbsize'=>128, // 94, 110, 128, 200, 220, 288, 320, 400, 512, 576, 640, 720, 800, 912, 1024, 1152, 1280, 1440, 1600
				'image_thumbcrop'=>true, // true/false
				'image_maxsize'=>800, // lint to original
				'image_class'=>'picasa_image',
				'use_lightbox'=>true,
				
	);
	function init($options=array()) {
		global $picasaOptions;
		
		$options=self::$options;
		$options = new scbOptions($options['key'], __FILE__,array(
				'v'=>'1.0',
				'username' => ''
		));
		if ( is_admin() ) {
			require_once dirname(__FILE__) . '/admin.php';
			new picasaOptions_Options_Page(__FILE__, $options);
			add_action( 'wp_ajax_picasa_ajax_import',array('wpPicasa','picasa_ajax_import') );
			add_action( 'wp_ajax_picasa_ajax_reload_images',array('wpPicasa','picasa_ajax_reload_images') );
			add_action('admin_menu', array('wpPicasa','add_custom_boxes'));
			#add_action( "publish_post", array('wpPicasa','publish_post'));
		}
		self::load_picasa_javascript();
				
	}
	function _activate(){
		// set default option
		add_option('picasaOptions_options', serialize (self::$options),'','yes');
	}
	
	function load_picasa_javascript(){
		if ( is_admin() ) {
			wp_enqueue_script('picasa_albums_admin', plugins_url('picasa'). '/admin/scripts.js', array('jquery'), '1.0', true);
			wp_enqueue_style('picasa_albums_admin_css',plugins_url('picasa').'/admin/style.css');
		}else{
			wp_enqueue_style('picasa_albums_css',plugins_url('picasa').'/style.css');
			wp_enqueue_style('fancybox_css',plugins_url('picasa').'/fancybox/jquery.fancybox.css');
			wp_enqueue_script('fancybox', plugins_url('picasa') . '/fancybox/jquery.fancybox.js', array('jquery'), '1.3.1', true);
			
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
		$supports = array('title','author','comments');
		$args = array(
			'rewrite' =>array('slug'=>'album'),
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
		register_post_type( 'album',$args);
		register_taxonomy_for_object_type('album', 'album');
		
		add_filter('the_content',array('wpPicasa','picasa_album_filter'));
		
		// add custom box
		#is_tax('album')
		#query_posts(array('post_type' => array('post','page','album')));
		#print_r(get_post_types( ));
	}
	function add_custom_boxes(){
		add_meta_box( 'picasa-album','Album Details',array('wpPicasa','picasa_admin_album_view'),'album', 'normal', 'high');
		add_meta_box( 'picasa-album-images','Album Images',array('wpPicasa','picasa_admin_album_images'),'album', 'normal', 'high');
		add_meta_box( 'picasa-album-side','Maintenance Functions',array('wpPicasa','picasa_admin_album_import'),'album', 'side', 'low');
	}
	
	function picasa_admin_album_import(){
		global $post;
		if(!is_array($post->post_excerpt)){
			$post->post_excerpt =  json_decode(htmlspecialchars_decode($post->post_excerpt),true);
		}
		
		echo '<div class="submitbox">
			<p>Will reload all data and erase any changes the you made!</p>
		';
		echo scbForms::input(array(
			'type' => 'button',
			'name' => 'import_album_images',
			'id' => 'import_album_images',
			'extra'=>'class="button" data="'.$post->post_excerpt['id'].'"',
			'value' => 'Reload Images'
		));		
		echo scbForms::input(array(
			'type' => 'button',
			'name' => 'import_album',
			'id' => 'import_album',
			'extra'=>'class="button" style="float:right" ',
			'value' => 'Reload Details'
		));
		echo '</div>';
	}
	/**
	 * box html
	 * @return unknown_type
	 */
	function picasa_admin_album_view(){
		global $post;

		if(!is_array($post->post_excerpt)){
			$post->post_excerpt =  json_decode(htmlspecialchars_decode($post->post_excerpt),true);
		}
		if(is_array($post->post_excerpt)){
		echo '<textarea id="excerpt" name="excerpt" style="display:none">'.json_encode($post->post_excerpt).'</textarea>';
		echo '
		<div class="inside">
			<img src="'.$post->post_excerpt['thumbnail']['url'].'" alt="album cover" width="'.$post->post_excerpt['thumbnail']['width'].'" height="'.$post->post_excerpt['thumbnail']['height'].'" style="float:left; margin-right:5px;"/>
			
			<ul class="inside">
				<li>Published: <strong>'.date('D F, jS Y',$post->post_excerpt['published']).'</strong></li>
				<li>Last updated:  <strong>'.date('D F, jS Y, H:i',$post->post_excerpt['updated']).'</strong></li>
				<li>Original Title:  <strong>'.$post->post_excerpt['title'].'</strong></li>
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
		/*			
		echo '<pre>';
		echo  'Sting: '.print_r($post->post_excerpt).'<br />';
		echo '</pre>';
		*/
		
		}else{
			echo 'Error! Album data is corrupted!';
		}
	}
	/**
	 * displays edit page
	 * @return unknown_type
	 */
	function picasa_admin_album_images(){
		global $post;
		if(!is_array($post->post_content)){
			$post->post_content =  json_decode(htmlspecialchars_decode($post->post_content),true);
		}
		/*
		echo '<pre>';
		print_r($post->post_content[1]);
		echo '</pre>';
		*/
		echo '<textarea id="content" name="content" style="display:none">'.json_encode($post->post_content).'</textarea>';
		echo '<div class="inside">';
		if(count($post->post_content) > 0){
			foreach($post->post_content as $i=>$image){
				echo '<a class="thickbox" href="'.$image['fullpath'].'s720/'.$image['file'].'" title="';
				echo (!empty($image['summary'])) ? htmlspecialchars($image['summary']):$image['file'];
				echo '"><img src="'.$image['fullpath'].'s110-c/'.$image['file'].'" alt="'.$image['summary'].'" /></a>';
			}
		}else{
			echo 'No images!';
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
		// /wp-admin/admin-ajax.php?action=myajax-submit
		echo 'ajax...';
		// time to curl
		$xml= new wpPicasaApi($options['username']);
		$xml->getAlbums();
		$xml->parseAlbumXml(true);
		$q = 'SELECT ID, post_parent FROM '.$wpdb->posts.' WHERE post_type = \''.self::$post_type.'\' ';
		foreach($wpdb->get_results($q, ARRAY_A) as $i=>$row){
			$albums[$row['post_parent']] =$row['ID'];
		}
		foreach($xml->getData() as $aData){
			if(is_array($albums) && array_key_exists($aData['id'],$albums)){
				self::insertAlbums($aData,$albums[$aData['id']]);
			}else{
				self::insertAlbums($aData);
			}
		}
		exit;
	}
	
	/**
	 * loads images from api
	 * @return bool
	 */
	function picasa_ajax_reload_images() {
		global $seodb, $wpdb;
		if(isset($_GET['post_ID']) && isset($_GET['id'])){
		$options = get_option(self::$options['key']);
			// time to curl
			$xml= new wpPicasaApi($options['username']);
			$xml->getImages($_REQUEST['id']);
			$xml->parseImageXml(true);
			self::insertImagesToAlbum($xml->getData(),$_GET['post_ID']);
			echo '{"r":1,"m":"done!"}';
		}else{
			echo '{"r":0,"m":"plese provide post and album id"}';
		}
		exit;
	}
	function insertAlbums($data,$id=0){
		global $current_user;
      	get_currentuserinfo();
		$post = array(
			'post_status' => 'draft', 
			'post_type' => 'album',
			'post_title' => $data['title'],
			'post_name' => $data['name'],
			'post_parent'=>$data['id'],
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
		$newrules['(album)/(\d*)$'] = 'index.php?post_type=$matches[1]&post_name=$matches[2]';
		$newrules['(album)$'] = 'index.php?post_type=$matches[1]';
		return $newrules + $rules;
	}
	
	// Adding the id var so that WP recognizes it
	function wp_insertPicasaQueryVars($vars){
	    array_push($vars, 'post_name');
	    return $vars;
	}
	
	function picasa_album_filter($content){
		global $post;
		if(get_post_type() == self::$post_type){
			if(is_single()){
				self::decode_content(&$post->post_content);
				$res = '';
				foreach($post->post_content as $i=>$aImage){
					$res .= '
							<div style="width: '.(self::$options['image_thumbsize']+10).'px;" class="wp-caption alignleft '.self::$options['image_class'].'">
								<a href="'.$aImage['fullpath'].'s'.self::$options['image_maxsize'].'/'.$aImage['file'].'" rel="'.$post->post_name.' nofollow" class="fancybox" title="';
					$res.=(!empty($aImage['summary'])) ? $aImage['summary']:$aImage['file'];
					$res.='">
									<img src="'.$aImage['fullpath'].'s'.self::$options['image_thumbsize'];
					$res.=(self::$options['image_thumbcrop']) ? '-c':'';
					$res.='/'.$aImage['file'].'" width="'.self::$options['image_thumbsize'].'" class="size-medium" title="'.$aImage['file'].'" alt="" />
								</a>
								<p class="wp-caption-text" style="display:none">'.$post->post_excerpt['title'].'</p>
							</div>
					'; 
				}
				return $res;			
			}else{
				self::decode_content(&$post->post_excerpt);
				$res = '
					<div>
						<div style="width: '.($post->post_excerpt['thumbnail']['width']+10).'px;" class="wp-caption alignleft">
							<a href="'.get_permalink().'">
								<img height="'.$post->post_excerpt['thumbnail']['height'].'" width="'.$post->post_excerpt['thumbnail']['width'].'" class="size-medium" title="'.$post->post_excerpt['title'].'" alt="" src="'.$post->post_excerpt['thumbnail']['url'].'" />
							</a>
							<p class="wp-caption-text" style="display:none">'.$post->post_excerpt['title'].'</p>
						</div>
						'.$post->post_excerpt['summary'].'
					</div>
				'; 
				return $res;			
			}
		}else{
			return $content;
		}		 
	}
	function decode_content(&$c){
		if(!is_array($c)){
			$c =  json_decode(htmlspecialchars_decode($c),true);
		}
	}
	
}
//register_activation_hook( __FILE__, array('wpPicasa','_activate') );

//add_action('init', array('wpPicasa','init'));


class wpPicasaApi{
	private $xml;
	private $data;
	private $user;
	
	function __construct($user){
		$this->user = $user;
	}
	function __get($key){
		return (!isset($this->$key)) ? $this->$key:null;
	}
	function getData(){
		return $this->data;
	}
	function getAlbums(){
		$header = array( 
		    "MIME-Version: 1.0", 
		    "Content-type: text/html", 
		    "Content-transfer-encoding: text" 
		);
		$url='http://picasaweb.google.com/data/feed/api/user/'.$this->user;
		$ch = curl_init($url);		
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST,0); // do not use POST to get xml feeds. GET only!!!
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //array('Content-type: application/atom+xml','Content-Length: 2000')
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		if(intval(curl_errno($ch)) == 0){
        	$this->xml = curl_exec($ch);
		}else{
			$this->xml=null;
			$this->error = curl_error($ch);
		}
        curl_close($ch);
		return true;
	}
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
					'published'=>strtotime($oAlbum->published), // strtotime(2010-09-11T04:58:08.000Z);
					'updated'=>strtotime($oAlbum->updated),// // strtotime(2010-09-11T04:58:08.000Z);
					'title' =>(string)$oAlbum->title,//2010-09-02 - Russia - Odd Things
					'thumbnail' => (Array)$oAlbum->xpath('./media:group/media:thumbnail'), // 
					'latlong' => (Array)$oAlbum->xpath('./georss:where/gml:Point/gml:pos'), //
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
				$aAlbum['latlong'] = explode(' ',(string)$aAlbum['latlong'][0]);
				$aAlbum['latlong'] = (count($aAlbum['latlong']) == 1) ? false:$aAlbum['latlong'];
				$aAlbum['id'] = (string)$aAlbum['id'][0];
				$tmp = explode('/',$aAlbum['links']['text/html']);
				$aAlbum['name']=end($tmp);
				
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
	function getImages($aid){
		$header = array( 
		    "MIME-Version: 1.0", 
		    "Content-type: text/html", 
		    "Content-transfer-encoding: text" 
		);
		//http://picasaweb.google.com/data/feed/api/user/userID/albumid/albumID
		$url='http://picasaweb.google.com/data/feed/api/user/'.$this->user.'/albumid/'.$aid;
		$ch = curl_init($url);		
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST,0); // do not use POST to get xml feeds. GET only!!!
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //array('Content-type: application/atom+xml','Content-Length: 2000')
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		if(intval(curl_errno($ch)) == 0){
        	$this->xml = curl_exec($ch);
		}else{
			$this->xml=null;
			$this->error = curl_error($ch);
		}
        curl_close($ch);
		return true;
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
			foreach($xml->entry as $i=>$oAlbum){
				$c++;
				$aAlbum = array(
					'id'=> (Array)$oAlbum->xpath('./gphoto:id'), //5516889074505060529
					'published'=>strtotime($oAlbum->published), // strtotime(2010-09-11T04:58:08.000Z);
					'updated'=>strtotime($oAlbum->updated),// // strtotime(2010-09-11T04:58:08.000Z);
					'file' =>(string)$oAlbum->title,//2010-09-02 - Russia - Odd Things
					'fullpath' =>$oAlbum->content,//2010-09-02 - Russia - Odd Things
				   	'width'=>(Array)$oAlbum->xpath('./gphoto:width'), // width of the original in px
				    'height'=>(Array)$oAlbum->xpath('./gphoto:height'), // height of the original in px 
				    'size'=>(Array)$oAlbum->xpath('./gphoto:size'), // file size of the original in kb				
					'latlong' => (Array)$oAlbum->xpath('./georss:where/gml:Point/gml:pos'), //
					'summary' =>addslashes((string) $oAlbum->summary), //Some things in Russia make you wonder
					'rights' => (Array)$oAlbum->xpath('./gphoto:access'), //public
					'pos'=>$c,
					'show'=>'yes',
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
				$tmp = explode('/',$aAlbum['thumbnail']['url']);
				// some trickery to get image path
				$aAlbum['fullpath'] = (Array)$aAlbum['fullpath'];
				$aAlbum['fullpath'] =str_replace($aAlbum['file'],'',$aAlbum['fullpath']['@attributes']['src']);
				// flatten id
				$aAlbum['id'] = (string)$aAlbum['id'][0];
				// lat long as array
				$aAlbum['latlong'] = explode(' ',(string)$aAlbum['latlong'][0]);
				$aAlbum['latlong'] = (count($aAlbum['latlong']) == 1) ? false:$aAlbum['latlong'];
				// flatten right, size, width, height
				$aAlbum['size'] = (string)$aAlbum['size'][0];
				$aAlbum['rights'] = (string)$aAlbum['rights'][0];
				$aAlbum['height'] = (string)$aAlbum['height'][0];
				$aAlbum['width'] = (string)$aAlbum['width'][0];
				unset($tmp);
				$this->data[]=$aAlbum;
				unset($aAlbum);				
			}
			unset($oAlbum);
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

if(!function_exists(flushRules)){
	// Remember to flush_rules() when adding rules
	function flushRules(){
		global $wp_rewrite;
	   	$wp_rewrite->flush_rules();
	}
}