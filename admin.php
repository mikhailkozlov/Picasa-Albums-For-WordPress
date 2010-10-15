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
		$rows = array(
			array(
				'title' => __('Picasa User Name', $this->textdomain),
				'type' => 'text',
				'name' => 'username',
				'extra' => 'size="50"',
				'desc' => '<br />Picasa\'s API does not need password to access your RSS feed.',
			),
		);
	
		$out =
		 html('h3', __('Picasa Settings', $this->textdomain))
		.html('p', __('', $this->textdomain))
		.$this->table($rows);
		//.html('p', __('<input type="button" class="button" value="Import Now" id="import_albums" name="import_albums">&nbsp;<span class="loader hide"><i>Loading...</i></span>', $this->textdomain));
		
		$rows = array(
			array(
				'title' => __('Import Albums', $this->textdomain),
				'type' => 'button',
				'name' => 'import_albums',
				'id'=>'import_albums',
				'value'=>'Import',
				'extra'=>'class="button"',
				'desc' => '<span class="loader hide"><i>Loading...</i></span>',
			),
		);
		$out.=html('h3', __('Import Data', $this->textdomain));
		$out.=html('p', __('', $this->textdomain));
		
		$out.=$this->table($rows);
	/*
		$rows = array(
			array(
				'title' => __('Database Address', $this->textdomain),
				'type' => 'text',
				'name' => 'db_address',
				'extra' => 'size="50"',
				'desc' => '<br />Try to use DNS address as IP address can change',
			),
			array(
				'title' => __('Database Name', $this->textdomain),
				'type' => 'text',
				'name' => 'db_name',
				'extra' => 'size="50"'
			),
			array(
				'title' => __('User Name', $this->textdomain),
				'type' => 'text',
				'name' => 'db_username',
				'extra' => 'size="50"'
			),
			array(
				'title' => __('Password', $this->textdomain),
				'type' => 'password',
				'name' => 'db_password',
				'extra' => 'size="50"'
			)
		);
		$out .=
		 html('h3', __('Database Connection', $this->textdomain))
		.html('p', __('<span style="color:red">All required!</span>', $this->textdomain))
		.$this->table($rows);
		
		$def_country = (defined('COUNTRY_CODE')) ? COUNTRY_CODE:'not set';
		$def_currency = (defined('BASE_CURR')) ? BASE_CURR:'not set';
		
		$rows = array(
			array(
				'title' => __('Default Currency', $this->textdomain),
				'type' => 'text',
				'name' => 'currency_name',
				'extra' => 'size="50"',
				'desc' => '<br /> Leave blank to fallback on global site settings ('.$def_currency.')'
			),
			array(
				'title' => __('Currency Prefix', $this->textdomain),
				'type' => 'text',
				'name' => 'currency_prefix',
				'extra' => 'size="50"',
				'desc' => '<br /> Like $ or US'
			),
			array(
				'title' => __('Currency Postfix', $this->textdomain),
				'type' => 'text',
				'name' => 'currency_postfix',
				'extra' => 'size="50"',
				'desc' => '<br /> Like CA or GBP'
			),
			array(
				'title' => __('Default Country Code', $this->textdomain),
				'type' => 'text',
				'name' => 'country_code',
				'extra' => 'size="50"',
				'desc' => '<br /> Leave blank to fallback on global site settings ('.$def_country.')'
			)
		);
		$out .=
		 html('h3', __('Country Settings', $this->textdomain))
		.html('p', __('', $this->textdomain))
		.$this->table($rows);
		*/
		echo $this->form_wrap($out);
	}
}

