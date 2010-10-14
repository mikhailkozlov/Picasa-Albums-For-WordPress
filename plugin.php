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

$path = str_replace('\\','/',dirname(__FILE__)); // windows scramble
require $path .'/scb/load.php';
scb_init(array('wpPicasa','init'));

class wpPicasa{
	static $options=array(
				'v'=>'1.0',
				'key'=>'picasaOptions_options',
				'username' => ''
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
			add_action('admin_menu', array('wpPicasa','add_custom_boxes'));
		}

		

		self::create_postType();
		self::load_picasa_javascript();		
	}
	function _activate(){
		// set default option
		add_option('picasaOptions_options', serialize (self::$options),'','yes');
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
		register_post_type( 'album',array('rewrite' =>array('slug'=>'album'),'labels' => $labels,'public' => true,'supports' => $supports));
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
		$options = get_option(self::$options['key']);
		// /wp-admin/admin-ajax.php?action=myajax-submit
		echo 'ajax...';
		// time to curl
		/*
		$xml= new wpPicasaApi($options['username']);
		$xml->getAlbums();
		$xml->parseAlbumXml(true);
		foreach($xml->getData() as $aData){
			
			self::insertAlbums($aData);
		}
		*/		
		exit;
	}
	function insertAlbums($data){
		global $current_user;
      	get_currentuserinfo();
      	
		$post = array(
			'post_status' => 'draft', 
			'post_type' => 'album',
			'post_title' => $data['title'],
			'post_name' => $data['name'],
			'post_date_gmt' => date('Y-m-d H:i:s',$data['published']),
			'post_modified_gmt' => date('Y-m-d H:i:s',$data['updated']),
			'post_author' => $current_user->ID,
			'post_excerpt' => serialize($data)
		);
		print_r($post);
		#$id=wp_insert_post($post);		
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
					'id'=> (string)$oAlbum->xpath('./gphoto:id'), //5516889074505060529
					'name'=>'',//20100902RussiaOddThings
					'published'=>strtotime($oAlbum->published), // strtotime(2010-09-11T04:58:08.000Z);
					'updated'=>strtotime($oAlbum->updated),// // strtotime(2010-09-11T04:58:08.000Z);
					'title' =>(string)$oAlbum->title,//2010-09-02 - Russia - Odd Things
					'thumbnail' => (Array)$oAlbum->xpath('./media:group/media:thumbnail'), // 
					'latlong' => (Array)$oAlbum->xpath('./georss:where/gml:Point/gml:pos'), //
					'summary' =>(string) $oAlbum->summary, //Some things in Russia make you wonder
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
}