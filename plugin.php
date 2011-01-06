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
				'album_thumbsize'=>160,
				'album_thumbcrop'=>'yes',
				'albums_display'=>'rows', 
				'image_thumbsize'=>128, // 94, 110, 128, 200, 220, 288, 320, 400, 512, 576, 640, 720, 800, 912, 1024, 1152, 1280, 1440, 1600
				'image_thumbcrop'=>'yes', // true/false
				'image_maxsize'=>800, // lint to original
				'image_class'=>'picasa_image',
				'use_lightbox'=>true,
				'embed_image_thumbsize'=>128,
				'embed_image_maxsize'=>800
				
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
			add_action( 'wp_ajax_picasa_ajax_list_albums',array('wpPicasa','picasa_ajax_list_albums') );
			
			add_action('admin_menu', array('wpPicasa','add_custom_boxes'));
			#add_action('edit_post',array('wpPicasa','edit_post'), 12, 0 );

			
		}
		self::load_picasa_javascript();
				
	}
	function _activate(){
		// set default option
		add_option('picasaOptions_options', serialize (self::$options),'','yes');
	}
	function load_picasa_javascript(){
		if ( is_admin() ) {
			wp_enqueue_script('json', '/wp-admin/load-scripts.php?c=1&load=json2', array('jquery'), '2', true);
			wp_enqueue_script('picasa_albums_admin', plugins_url('picasa'). '/admin/scripts.js', array('jquery'), '1.0', true);
			wp_enqueue_style('picasa_albums_admin_css',plugins_url('picasa').'/admin/style.css');
			wp_enqueue_style('fancybox_css',plugins_url('picasa').'/fancybox/jquery.fancybox.css');
			wp_enqueue_script('fancybox', plugins_url('picasa') . '/fancybox/jquery.fancybox.js', array('jquery'), '1.3.1', true);
			
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

		add_filter('the_content',array('wpPicasa','picasa_post_filter'));
		add_filter('the_content',array('wpPicasa','picasa_album_filter'));
				
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
	 * box html
	 * @return unknown_type
	 */
	function picasa_admin_album_view(){
		global $post;

		if(!is_array($post->post_excerpt)){
			$post->post_excerpt =  json_decode(htmlspecialchars_decode($post->post_excerpt),true);
		}
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
		if(!is_array($post->post_content)){
			$post->post_content =  json_decode(htmlspecialchars_decode($post->post_content),true);
		}
		echo '<script>';
		echo 'var images = '.json_encode($post->post_content).';';
		echo '</script>';
		echo '<textarea id="content" name="content" style="display:none" class="albumpage">'.json_encode($post->post_content).'</textarea>';
		
		echo '<div class="inside">			
		';		
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
		// /wp-admin/admin-ajax.php?action=myajax-submit
		echo 'doing ajax...';
		// time to curl
		$xml= new wpPicasaApi($options['username'],$_GET['password'],array('thumbsize'=>$options['album_thumbsize']));
		$xml->getAlbums();
		$xml->parseAlbumXml(true);
		$q = 'SELECT ID, post_mime_type FROM '.$wpdb->posts.' WHERE post_type = \''.self::$post_type.'\' ';
		foreach($wpdb->get_results($q, ARRAY_A) as $i=>$row){
			$albums[$row['post_mime_type']] =$row['ID'];
		}
		foreach($xml->getData() as $aData){
			if(is_array($albums) && array_key_exists($aData['id'],$albums)){
				// update existing album. images will not be updated
				self::insertAlbums($aData,$albums[$aData['id']]);
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

	function picasa_ajax_list_albums() {
		global $wpdb;
		$q = 'SELECT ID, post_title FROM '.$wpdb->posts.' WHERE post_type = \''.self::$post_type.'\' AND post_status=\'publish\'';
		foreach($wpdb->get_results($q, ARRAY_A) as $i=>$row){
			echo '<a href="#'.$row['ID'].'" data=\'{"id":"'.$row['ID'].'"}\' onclick="send(this);">'.$row['post_title'].'</a><br />';
		}		
		/*
		 * Now we need to load albums and create filter.
		 * 
		 */
		echo '
		<a href="#" onclick="send(this);">test</a>
			<script type="text/javascript">
			/* <![CDATA[ */
			function send(t){
				var t =  jQuery(t);
				var d = jQuery.parseJSON(t.attr("data"));
				send_to_editor("[PicasaAlbum id="+d.id+"&scroll=false&limit=5]");
			}
			/* ]]> */
			</script>
		';
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
						$aImages = json_decode($row->post_content,true);
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
		$newrules['(album)/(\d*)$'] = 'index.php?post_type=$matches[1]&post_name=$matches[2]';
		$newrules['(album)$'] = 'index.php?post_type=$matches[1]';
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
				self::decode_content(&$post->post_content);
				$res = '';
				foreach($post->post_content as $i=>$aImage){
					if($aImage['show'] == 'yes'){
						$res .= '
								<div style="width: '.($options['image_thumbsize']+10).'px;" class="wp-caption alignleft '.$options['image_class'].'">
									<a href="'.$aImage['fullpath'].'s'.$options['image_maxsize'].'/'.$aImage['file'].'" rel="'.$post->post_name.' nofollow" class="fancybox" title="';
						$res.=(!empty($aImage['summary'])) ? $aImage['summary']:$aImage['file'];
						$res.='">
										<img src="'.$aImage['fullpath'].'s'.intval($options['image_thumbsize']);
						$res.=($options['image_thumbcrop'] == 'yes') ? '-c':'';
						$res.='/'.$aImage['file'].'"';
						$res .= ($options['image_thumbcrop'] == 'yes' && isset($aImage['thumbnail']) ) ? ' width="'.$aImage['thumbnail']['height'].' height="'.$aImage['thumbnail']['height'].'" ':' ';
						$res.=' class="size-medium" alt="" />
									</a>
									<p class="wp-caption-text" style="display:none">';
						$res.=(!empty($aImage['summary'])) ? $aImage['summary']:$aImage['file'];
						$res.='</p>
								</div>
						'; 
					}
				}
				return $res;			
			}else{
				self::decode_content(&$post->post_excerpt);
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
				return $res;			
			}
		}else{
			return $content;
		}		 
	}
	function picasa_post_filter($content){
		global $post,$wpdb;
		$pattern = '/\[PicasaAlbum(.+)\]/';
		$postCache = array();
		if(get_post_type() != self::$post_type){
			return preg_replace_callback($pattern,array('wpPicasa','picasa_post_filter_callback'),$content);
		}
	}
	
	function picasa_post_filter_callback_tmp($matches){
		global $postCache; 
		echo '<br />';
		$postCache[]=$matches;
		print_r($matches);
	}
	function picasa_post_filter_callback($matches){
		global $post, $wpdb, $postCache;
		if(!is_array($postCache)){
			$postCache=array();
		}
				if(count($matches) > 0 && isset($matches[1])){
					parse_str(htmlspecialchars_decode($matches[1]),$args);
					if(count($args) > 0 && isset($args['id']) && intval($args['id']) >0 ){
						$def_args=array(
							'link_to_album'=>'true',
							'scroll'=>'true',
							'limit'=>5,
							'fancybox'=>'false',
							'per_page'=>5,
						);
						$args = array_merge($def_args,$args);
						// make sure we have int as id
						$args['id'] = intval($args['id']);
						if(!array_key_exists($args['id'],$postCache)){
							$q = 'SELECT ID, post_date,post_content,post_title,post_status,ping_status,post_name,post_modified,guid,post_parent,post_type FROM '.$wpdb->posts.' WHERE ID = '.$args['id'].' AND post_status=\'publish\'';
							$rs = $wpdb->get_results($q, ARRAY_A);
							if(array_key_exists(0,$rs)){
								$postCache[$args['id']] =$rs[0];
							}
							unset($rs); 
						}
						if(array_key_exists($args['id'],$postCache) && count($postCache[$args['id']]) > 0){
							
							// get options
							$options=self::$options;
							$options = array_merge($options,get_option($options['key']));
							// get data from JSON
							$images =  json_decode(htmlspecialchars_decode($postCache[$args['id']]['post_content']),true);
							// start output:
							$replacement = '<div class="picasa_album_embed">';
							$replacement .=($args['link_to_album'] == 'true')? '<a href="'.get_permalink($postCache[$args['id']]['ID']).'" style="clear:both">'.$postCache[$args['id']]['post_title'].'</a>':'';
							$replacement .='<div>';
							// scroll set to false -> do not show navigation
							$replacement .=($args['scroll'] == 'true') ? '<a class="prev browse left" style="margin-top:'.($options['image_thumbsize']/2).'px;"></a>':'';
							$replacement .='<div ';
							// scroll set to false -> change class
							$replacement .=($args['scroll'] == 'true') ?  'style="height:'.$options['image_thumbsize'].'px;" class="scrollable"':'class="not-scrollable"';
							$replacement .= '><div class="items" id="album_'.$args['id'].'" ><div>';
							foreach($images as $i=>$image){
								if($i<$args['limit']){
									$replacement .= '<a href="';
									// check if fancybox is true link to image, if not we link to album
									$replacement .= ($args['fancybox'] !== 'false') ? $image['fullpath'].'s'.$options['image_maxsize'].'/'.$image['file']:get_permalink($postCache[$args['id']]['ID']).'#photo_'.$image['id'];
									$replacement .= '" rel="'.$post->post_name.' nofollow"';
									// check if fancybox is true we add class for it
									$replacement .= ($args['fancybox'] !== 'false') ? ' class="fancybox"':''; 
									$replacement.= ' title="';
									$replacement.=(!empty($image['summary'])) ? $image['summary']:$image['file'];
									$replacement.='">';
									$replacement.='<img src="'.$image['fullpath'].'s'.intval($options['image_thumbsize']);
									$replacement.=($options['image_thumbcrop'] == 'yes') ? '-c':'';
									$replacement.='/'.$image['file'].'"';
									$replacement .= ($options['image_thumbcrop'] == 'yes' && isset($aImage['thumbnail']) ) ? ' width="'.$image['thumbnail']['height'].' height="'.$image['thumbnail']['height'].'" ':' ';
									$replacement.=' class="size-medium '; 
									$replacement.=($args['scroll'] !== 'true') ? ' no-scroll':''; 
									$replacement.=' " alt="" /></a>';
								}					
								$replacement.= ($args['scroll'] == 'true' && $i> 0 && ($i%$args['per_page']) == 0 && ($i+1) < $args['limit'] && ($i+1) < count($images)) ? '</div><div>':'';
							}							
							$replacement .= '</div></div></div>';
							// scroll set to false -> do not show navigation
							$replacement .= ($args['scroll'] == 'true') ? '<a class="next browse right" style="margin-top:'.( ($options['image_thumbsize']/2) - 9).'px;"></a>':'';
							$replacement .= '<div class="clear">&nbsp;</div></div></div>';
							return $replacement;
						}
					}
				}			
	}
	
	function decode_content(&$c){
		if(!is_array($c)){
			$c =  json_decode(htmlspecialchars_decode($c),true);
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

}
//register_activation_hook( __FILE__, array('wpPicasa','_activate') );

//add_action('init', array('wpPicasa','init'));


class wpPicasaApi{
	private $xml;
	private $data;
	private $user;
	private $_passwd;
	private $_authCode;
	
	private $params=array(
		'thumbsize'=>160
	);
	
	function __construct($user,$password=null,$params=array()){
		$this->user = $user;
		$this->_setParams($params);		
		if($password !=null && !empty($password)){
			$this->_passwd = $password;
			$this->_authenticate();
		}		
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
	
	private function _authenticate() {	
		$postdata = array(
            'accountType' => 'GOOGLE',
            'Email' => $this->user,
            'Passwd' => $this->_passwd,
            'service' => 'lh2',
            'source' => 'wp-picasa_plugin-v01'
        );
		
		$response = $this->_postTo("https://www.google.com/accounts/ClientLogin", $postdata);
		//process the response;
		if ($response) {
			preg_match('/Auth=(.*)/', $response, $matches);
			if(isset($matches[1])) {
				$this->_authCode = $matches[1];
				return TRUE;
			}
		}
		return false;
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
			throw new Exception('Bad request - '.$response);
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
		if(!empty($this->_authCode)){
			$header[]="Authorization: GoogleLogin auth=".$this->_authCode;
			$url.='&access=all';
		}else{
			$url.='&access=public';
		}		
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
		if($authkey !=null && !empty($authkey)){
			$url.='&authkey='.$authkey;
		}
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
					'title' =>	utf8_encode((string)$oAlbum->title),//2010-09-02 - Russia - Odd Things
					'thumbnail' => (Array)$oAlbum->xpath('./media:group/media:thumbnail'), // 
					'latlong' => '', //
					'summary' =>addslashes(utf8_encode((string) $oAlbum->summary)), //Some things in Russia make you wonder
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
				$aAlbum['latlong'] = (Array)$oAlbum->xpath('./georss:where/gml:Point/gml:pos'); // 
				$aAlbum['latlong'] = explode(' ',(string)$aAlbum['latlong'][0]);
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
					'latlong' => '', //
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
				
				// private albums do not seem to have georss.
				$ns = $xml->getDocNamespaces();
				if(array_key_exists('georss',$ns)){
					// lat long as array
					$aAlbum['latlong'] = (Array)$oAlbum->xpath('./georss:where/gml:Point/gml:pos');
					$aAlbum['latlong'] = explode(' ',(string)$aAlbum['latlong'][0]);
					$aAlbum['latlong'] = (count($aAlbum['latlong']) == 1) ? false:$aAlbum['latlong'];
				}
				
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

if(!function_exists('flushRules')){
	// Remember to flush_rules() when adding rules
	function flushRules(){
		global $wp_rewrite;
	   	$wp_rewrite->flush_rules();
	}
}


/*
{"author":{
	"name":"Mikhail Kozlov",
	"uri":"http://picasaweb.google.com/kozlov.m.a"
},
"id":"5426089371675808385",
"name":"06072005",
"authkey":"Gv1sRgCNGi7fblgt_1OA",
"published":1118027591,
"updated":1263359900,
"title":"06-07-2005",
"thumbnail":{
	"url":"http://lh3.ggpht.com/_X7imT2xUAEM/S01XiO5CUoE/AAAAAAAAQP8/xr6wVjfWXFk/s160-c/06072005.jpg",
	"height":"160",
	"width":"160"
},
"latlong":false,
"summary":"",
"rights":"private",
"links":{
	"text/html":"http://picasaweb.google.com/kozlov.m.a/06072005?authkey=Gv1sRgCNGi7fblgt_1OA",
	"application/atom+xml":"http://picasaweb.google.com/data/entry/api/user/kozlov.m.a/albumid/5426089371675808385?authkey=Gv1sRgCNGi7fblgt_1OA"
}}



[{
"id":"5516889092249585314",
"published":1284500838,
"updated":1284500996,
"file":"IMG_3009.JPG",
"fullpath":"http:\/\/lh5.ggpht.com\/_X7imT2xUAEM\/TI_tZlDDhqI\/AAAAAAABWtc\/iWuezoabhI0\/",
"width":"1600",
"height":"1067",
"size":"149363",
"latlong":false,
"summary":"",
"rights":"public",
"pos":1,
"show":"yes",
"links":{
	"text\/html":"http:\/\/picasaweb.google.com\/kozlov.m.a\/20100902RussiaOddThings#5516889092249585314",
	"application\/atom+xml":"http:\/\/picasaweb.google.com\/data\/entry\/api\/user\/kozlov.m.a\/albumid\/5516889074505060529\/photoid\/5516889092249585314"
	}
},
{"id":"5516889092977245474","published":1284500838,"updated":1284678262,"file":"IMG_2686.JPG","fullpath":"http:\/\/lh4.ggpht.com\/_X7imT2xUAEM\/TI_tZnwivSI\/AAAAAAABWtc\/IXzlfx0gLA0\/","width":"1600","height":"1067","size":"112284","latlong":false,"summary":"LSTU just announced new nanotechnology faculty","rights":"public","pos":2,"show":"yes","links":{"text\/html":"http:\/\/picasaweb.google.com\/kozlov.m.a\/20100902RussiaOddThings#5516889092977245474","application\/atom+xml":"http:\/\/picasaweb.google.com\/data\/entry\/api\/user\/kozlov.m.a\/albumid\/5516889074505060529\/photoid\/5516889092977245474"}},{"id":"5516889100336657378","published":1284500840,"updated":1284591861,"file":"IMG_3075.JPG","fullpath":"http:\/\/lh6.ggpht.com\/_X7imT2xUAEM\/TI_taDLKc-I\/AAAAAAABWtc\/qaAct1MR5Z0\/","width":"1600","height":"1067","size":"157604","latlong":false,"summary":"","rights":"public","pos":3,"show":"yes","links":{"text\/html":"http:\/\/picasaweb.google.com\/kozlov.m.a\/20100902RussiaOddThings#5516889100336657378","application\/atom+xml":"http:\/\/picasaweb.google.com\/data\/entry\/api\/user\/kozlov.m.a\/albumid\/5516889074505060529\/photoid\/5516889100336657378"}},{"id":"5516889108663983346","published":1284500842,"updated":1284678437,"file":"IMG_2978.JPG","fullpath":"http:\/\/lh5.ggpht.com\/_X7imT2xUAEM\/TI_taiMjXPI\/AAAAAAABWtc\/Okv9Fy4C4ps\/","width":"1067","height":"1600","size":"149773","latlong":false,"summary":"","rights":"public","pos":4,"show":"yes","links":{"text\/html":"http:\/\/picasaweb.google.com\/kozlov.m.a\/20100902RussiaOddThings#5516889108663983346","application\/atom+xml":"http:\/\/picasaweb.google.com\/data\/entry\/api\/user\/kozlov.m.a\/albumid\/5516889074505060529\/photoid\/5516889108663983346"}},{"id":"5516889122815513810","published":1284500845,"updated":1284500996,"file":"IMG_2831.JPG","fullpath":"http:\/\/lh3.ggpht.com\/_X7imT2xUAEM\/TI_tbW6ilNI\/AAAAAAABWtc\/f5s-4QaayBI\/","width":"1600","height":"1067","size":"108527","latlong":false,"summary":"","rights":"public","pos":5,"show":"yes","links":{"text\/html":"http:\/\/picasaweb.google.com\/kozlov.m.a\/20100902RussiaOddThings#5516889122815513810","application\/atom+xml":"http:\/\/picasaweb.google.com\/data\/entry\/api\/user\/kozlov.m.a\/albumid\/5516889074505060529\/photoid\/5516889122815513810"}},{"id":"5516889132501258594","published":1284500847,"updated":1284500996,"file":"IMG_2832.JPG","fullpath":"http:\/\/lh6.ggpht.com\/_X7imT2xUAEM\/TI_tb6_zUWI\/AAAAAAABWtc\/WlhDwscJJmE\/","width":"1600","height":"1067","size":"103751","latlong":false,"summary":"","rights":"public","pos":6,"show":"yes","links":{"text\/html":"http:\/\/picasaweb.google.com\/kozlov.m.a\/20100902RussiaOddThings#5516889132501258594","application\/atom+xml":"http:\/\/picasaweb.google.com\/data\/entry\/api\/user\/kozlov.m.a\/albumid\/5516889074505060529\/photoid\/5516889132501258594"}},{"id":"5516889145923142946","published":1284500850,"updated":1284500996,"file":"IMG_2833.JPG","fullpath":"http:\/\/lh3.ggpht.com\/_X7imT2xUAEM\/TI_tcs_1BSI\/AAAAAAABWtc\/cLjBvn8l2mM\/","width":"1600","height":"1067","size":"303092","latlong":false,"summary":"","rights":"public","pos":7,"show":"yes","links":{"text\/html":"http:\/\/picasaweb.google.com\/kozlov.m.a\/20100902RussiaOddThings#5516889145923142946","application\/atom+xml":"http:\/\/picasaweb.google.com\/data\/entry\/api\/user\/kozlov.m.a\/albumid\/5516889074505060529\/photoid\/5516889145923142946"}},{"id":"5516889150458384946","published":1284500851,"updated":1284500996,"file":"IMG_2893.JPG","fullpath":"http:\/\/lh6.ggpht.com\/_X7imT2xUAEM\/TI_tc95HUjI\/AAAAAAABWtc\/CDjjO-gKxgg\/","width":"1600","height":"1067","size":"71513","latlong":false,"summary":"","rights":"public","pos":8,"show":"yes","links":{"text\/html":"http:\/\/picasaweb.google.com\/kozlov.m.a\/20100902RussiaOddThings#5516889150458384946","application\/atom+xml":"http:\/\/picasaweb.google.com\/data\/entry\/api\/user\/kozlov.m.a\/albumid\/5516889074505060529\/photoid\/5516889150458384946"}},{"id":"5516889158469630306","published":1284500853,"updated":1284500996,"file":"IMG_2894.JPG","fullpath":"http:\/\/lh6.ggpht.com\/_X7imT2xUAEM\/TI_tdbvJOWI\/AAAAAAABWtc\/cGshRlQiqtI\/","width":"1600","height":"1067","size":"63584","latlong":false,"summary":"","rights":"public","pos":9,"show":"yes","links":{"text\/html":"http:\/\/picasaweb.google.com\/kozlov.m.a\/20100902RussiaOddThings#5516889158469630306","application\/atom+xml":"http:\/\/picasaweb.google.com\/data\/entry\/api\/user\/kozlov.m.a\/albumid\/5516889074505060529\/photoid\/5516889158469630306"}},{"id":"5516889162592999778","published":1284500854,"updated":1284500996,"file":"IMG_2705.JPG","fullpath":"http:\/\/lh4.ggpht.com\/_X7imT2xUAEM\/TI_tdrGO2WI\/AAAAAAABWtc\/jH8a-oMM0Ck\/","width":"1600","height":"1067","size":"126265","latlong":false,"summary":"","rights":"public","pos":10,"show":"yes","links":{"text\/html":"http:\/\/picasaweb.google.com\/kozlov.m.a\/20100902RussiaOddThings#5516889162592999778","application\/atom+xml":"http:\/\/picasaweb.google.com\/data\/entry\/api\/user\/kozlov.m.a\/albumid\/5516889074505060529\/photoid\/5516889162592999778"}},{"id":"5516889181853156514","published":1284500859,"updated":1284500996,"file":"IMG_2522.JPG","fullpath":"http:\/\/lh4.ggpht.com\/_X7imT2xUAEM\/TI_tey2NhKI\/AAAAAAABWtc\/RH_bIuqknxQ\/","width":"1600","height":"1067","size":"193627","latlong":false,"summary":"","rights":"public","pos":11,"show":"yes",
	"links":{
		"text\/html":"http:\/\/picasaweb.google.com\/kozlov.m.a\/20100902RussiaOddThings#5516889181853156514",
		"application\/atom+xml":"http:\/\/picasaweb.google.com\/data\/entry\/api\/user\/kozlov.m.a\/albumid\/5516889074505060529\/photoid\/5516889181853156514"
	}
}]
*/
function my_refresh_mce($ver) {
  $ver += 3;
  return $ver;
}
add_filter( 'tiny_mce_version', 'my_refresh_mce');


function add_picasa_button() {
   if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
     return;
   if ( get_user_option('rich_editing') == 'true') {
     add_filter('mce_external_plugins', 'add_picasa_tinymce_plugin');
     add_filter('mce_buttons', 'register_picasa_button');
   }
}
function register_picasa_button($buttons) {
   array_push($buttons, "|", "wppicasagallery");
   return $buttons;
}
function add_picasa_tinymce_plugin($plugin_array) {
	$plugin_array['wppicasagallery'] = plugins_url('picasa').'/tinymce/editor_plugin.js';
	return $plugin_array;
}
add_action('init', 'add_picasa_button');

?>