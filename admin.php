<?php

class picasaOptions_Options_Page extends scbAdminPage {

	function setup() {
		$this->textdomain = 'wp-picasaOptions';
		$this->args = array(
			'page_title' => __('Picasa Albums Option', $this->textdomain),
			'menu_title' => __('Picasa Albums', $this->textdomain),
			'parent string'=>'edit.php'
		);
	}

	function validate($options) {
		return $options;
	}
	function page_content() {
		$new = (empty($this->options->username)) ? ' ref="new"':' ref="saved"';
		$rows = array(
			array(
				'title' => __('Picasa User Name', $this->textdomain),
				'type' => 'text',
				'name' => 'username',
				'extra' => 'size="50" '.$new,
				'desc' => '<span style="color:red; display:none;">Save options before importing albums!</span><br />Picasa\'s API does not need password to access your public RSS feed.',
			),
		);
		$out =
		 html('h3', __('Picasa Settings', $this->textdomain))
		.html('p', __('', $this->textdomain))
		.$this->table($rows);
		//.html('p', __('<input type="button" class="button" value="Import Now" id="import_albums" name="import_albums">&nbsp;<span class="loader hide"><i>Loading...</i></span>', $this->textdomain));
		
		$rows = array(
			array(
				'title' => __('Include Private Albums', $this->textdomain),
				'type' => 'checkbox',
				'name' => 'private_import_albums',
				'value'=>'',
				'extra'=>'id="private_import_albums" class="checkbox" checked=""',
				'desc' => '',
			),
		
			array(
				'title' => __('Import Albums', $this->textdomain),
				'type' => 'button',
				'name' => 'import_albums',
				'id'=>'import_albums',
				'value'=>'Import',
				'extra'=>'class="button"',
				'desc' => '<span class="loader hide"><i>Loading...</i></span></label><div id="gpass_holder" class="hide">Enter you password: <input name="gpassword" id="gpassword" type="password" value="" class="medium-text"/> (password will not be saved)</div><label>',
			)
		);
		$out.=html('h3', __('Import Data', $this->textdomain));
		$out.=html('p', __('', $this->textdomain));
		
		$out.=$this->table($rows);
		$rows = array(
			array(
				'title' => __('Album Thumbnail Height', $this->textdomain),
				'type' => 'text',
				'name' => 'album_thumbsize',
				'extra'=>'class="small-text"',
				'desc' => '<span>px. </span>'
			),
			array(
				'title' => __('Crop Album Thumbnails', $this->textdomain),
				'type' => 'select',
				'value'=>array('no'=>'No','yes'=>'Yes'),
				'name' => 'album_thumbcrop',
				'desc' => '<span style="color:red">Caution: Uncropped images may brake grid layout!</span>'
			)/*,
			
			array(
				'title' => __('Album Page Layout', $this->textdomain),
				'type' => 'select',
				'value'=>array('rows'=>'Rows','yes'=>'Grid'),
				'extra'=>' style="width:100px;"',
				'name' => 'albums_display',
				'desc' => '<br /><span>Rows is default WP view. All albums look like blogposts. Grid will generate picasa-like presentation.</span>'
			)
			*/
		);
		$out.=html('h3', __('Album Page Settings', $this->textdomain));
		$out.=html('p', __('', $this->textdomain));
		$out.=$this->table($rows);
		$rows=array(
			array(
				'title' => __('Image Thumbnail Size', $this->textdomain),
				'type' => 'text',
				'name' => 'image_thumbsize',
				'extra'=>'class="small-text"',
				'desc' => '<span>px. <br />Recommended sizes: 32, 48, 64, 72, 104, 144, 150, 160</span>'
			),
			array(
				'title' => __('Image Max Zoom Size', $this->textdomain),
				'type' => 'text',
				'name' => 'image_maxsize',
				'extra'=>'class="small-text"',
				'desc' => '<span>px.<br />
				The max. size of the image users will see in lightbox.<br />
				Available sizes: 94, 110, 128, 200, 220, 288, 320, 400, 512, 576, 640, 720, 800, 912, 1024, 1152, 1280, 1440, 1600
				</span>'
			),
			array(
				'title' => __('Crop Thumbnails', $this->textdomain),
				'type' => 'select',
				'value'=>array('no'=>'No','yes'=>'Yes'),
				'name' => 'image_thumbcrop',
				'desc' => '<br /><span>It is recommended to use crop. this way images look square and align nicely.</span>'
			)
			
		);
		$out.=html('h3', __('Gallery Page Settings', $this->textdomain));
		$out.=html('p', __('Define how your gallery posts will look.', $this->textdomain));
		$out.=$this->table($rows);
		$rows=array(
			array(
				'title' => __('Image Thumbnail Size', $this->textdomain),
				'type' => 'text',
				'name' => 'embed_image_thumbsize',
				'extra'=>'class="small-text"',
				'desc' => '<span>px. <br />Recommended sizes: 32, 48, 64, 72, 104, 144, 150, 160</span>'
			),
			array(
				'title' => __('Image Max Zoom Size', $this->textdomain),
				'type' => 'text',
				'name' => 'embed_image_maxsize',
				'extra'=>'class="small-text"',
				'desc' => '<span>px.<br />
				The max. size of the image users will see in lightbox.<br />
				Available sizes: 94, 110, 128, 200, 220, 288, 320, 400, 512, 576, 640, 720, 800, 912, 1024, 1152, 1280, 1440, 1600
				</span>'
			)
		);
		$out.=html('h3', __('Default Embed Gallery Settings', $this->textdomain));
		$out.=html('p', __('You can embed your galleries into other posts.', $this->textdomain));
		$out.=$this->table($rows);
		echo $this->form_wrap($out);
	}
}

