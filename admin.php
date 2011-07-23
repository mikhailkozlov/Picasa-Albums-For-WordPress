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
		
		$rows = array(
			array(
				'title' => __('Import Albums', $this->textdomain),
				'type' => 'button',
				'name' => 'import_albums',
				'id'=>'import_albums',
				'value'=>'Import',
				'extra'=>'class="button"',
				'desc' => '<span class="loader hide"><i>Loading</i></span></label><label>',
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
			)
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

		// v.1.0.6
		$out.=html('h3', __('Url Settings', $this->textdomain));
		if ( get_option('permalink_structure') != '' ) 
		{
			$rows=array(
				array(
					'title' => __('Gallery pretty url', $this->textdomain),
					'type' => 'text',
					'name' => 'gallery_path',
					'extra'=>'class="small-text" ',
					'desc' => '<div>Please make sure to update link to albums in blog\'s menus and all other links pointing to picasa albums.</div>'
				)
			);
			$out.=html('Customize blog\'s url structure', __('', $this->textdomain));	
		}else{
			$out.=html('span', __('If you would like to customize path to your albums <a href="options-permalink.php">permalinks</a> must be enabled!', $this->textdomain));
			$rows=array();
		}
		$out.=$this->table($rows);

		$out.=html('p', __('Do you need more features? Check out <a href="http://mikhailkozlov.com/category/projects/picasa-albums/" target="blank">Picasa Albums Pro</a>.', $this->textdomain));
		echo $this->form_wrap($out);
	}
}

