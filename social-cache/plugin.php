<?php
/*
Plugin Name: Social Cache
Plugin URI: http://www.bigspaceship.com
Description: This panel allows users to cache interesting content from Facebook and Twitter. Each piece of content can be categorized for use in the site.
Version: 1.0.0
Author: Big Spaceship
Author URI: http://www.bigspaceship.com/
Copyright: Big Spaceship, LLC
*/

$socialCache = new SocialCache();

class SocialCache
{ 
	var $_pluginDirUrl;
	var $_pluginDirServerPath;

	var $_themeUrl;
	var $_siteUrl;
	var $_adminUrl;

	var $_settingsId = 'bss_social_cache_settings';
	var $_settings = false;

	var $_settingsConfig = array('facebook_app_id'=>array('label'=>'Facebook App Id','type'=>'text','default'=>''),
								 'facebook_app_secret'=>array('label'=>'Facebook App Secret','type'=>'text','default'=>''));

	function SocialCache()
	{
		$this->_pluginDirServerPath = dirname(__FILE__).''; 	 // server path to this plugin directory
		$this->_pluginDirUrl = plugins_url('',__FILE__); // www path to this plugin directory
		$this->_siteUrl = get_bloginfo('url');
		$this->_adminUrl = admin_url();
		$this->_themeUrl = get_bloginfo('template_directory');

		// jk: actions
		add_action('init',array($this,'onInitHandler'));
		add_action('admin_menu', array($this,'onAdminMenuHandler'));
		add_action('admin_head', array($this,'onAdminHeadHandler'));
		add_action('save_post', array($this,'onStatusMessageSaved') );
		add_action('delete_post',array($this,'onStatusMessageDeleted'));
		add_action('manage_posts_custom_column',array($this,'onAdminCustomColumnData'));

		add_filter('request',array($this,'onAdminColumnsOrderBy'));
		add_filter("manage_edit-bss_social_cache_columns",array($this,'onAdminColumns'));
		add_filter('manage_edit-bss_social_cache_sortable_columns',array($this,'onAdminSortableColumns'));
		add_filter('enter_title_here',array($this,'onEnterTitleHandler'), 10 , 2);

		return true;
	}

	public function onInitHandler() {
		register_post_type('bss_social_cache',array('labels'=>array('name'=>'Social Cache','singular_name'=>'Social'),
											  		'supports'=>array('title', 'excerpt'),
   								  			  		'rewrite'=>array('with-front'=>false),
								  			  		'publicly_queryable'=>true,
								  			  		'public'=>true,
								  			  		'menu_icon'=>$this->_pluginDirUrl.'/icon.png'));
	}

	public function onAdminHeadHandler($foo) {
		global $post, $pagenow;

		// check to see if the settings exist
		$settings = get_option($this->_settingsId);
		if(!$settings) $error = true;
		else {
			$this->_settings = json_decode($settings,true);
			if($this->_settings['facebook_app_id'] == '' || $this->_settings['facebook_app_secret'] == '') $error = true;
		}

		if($error) {
			add_action('admin_notices',function() {
	            echo "<div id='bss_social_cache_warning' class='error fade'><p><strong>Your Facebook API settings are not set in the Social Cache Settings. You cannot cache Facebook posts until you add these.</strong></p></div>";
			});			
		}

		// check post type to see if we need to add JS or CSS to the admin
		$postType = get_post_type($post);
		if($postType == 'bss_social_cache') {
			if(in_array($pagenow, array('post-new.php'))) {
				$data = array();
				$data['pluginDir'] = $this->_pluginDirUrl;
				$data['settings'] = array();

				foreach($this->_settingsConfig as $id=>&$setting) {
					if($this->_settings && isset($this->_settings[$id])) {
						$setting['value'] = $this->_settings[$id];
					}
					else {
						$setting['value'] = $setting['default'];
					}

					$data['settings'][$id] = $setting;
				}

				$this->_render($this->_pluginDirServerPath.'/templates/admin-head.php',$data);
			}
			else if(in_array($pagenow,array('post.php'))) {
				echo "<link rel='stylesheet' href='{$this->_pluginDirUrl}/css/social-cache.css' />";
				echo "<link rel='stylesheet' href='{$this->_pluginDirUrl}/css/social-cache-edit.css' />";
			}
		}
	}

	public function onSettingsAdminHeadHandler() {
		if(isset($_POST['submit'])) {
			$this->_updateSettings();
		}
	}

	// columns
	public function onAdminColumns($columns) {
		$columns = array("cb" => "<input type=\"checkbox\" />","title" => "Username",'social_network'=>'Social Network',"social_date_published" => "Date Created",'date'=>'Date Cached');
		return $columns;
	}

	public function onAdminSortableColumns($columns) {
		$columns['social_network'] = 'social_network';
		$columns['social_date_published'] = 'social_date_published';
		return $columns;
	}

	public function onAdminCustomColumnData($key) {
		global $post;
		$postType = get_post_type($post);
		if($postType == 'bss_social_cache') {
			$customFields = get_post_custom();

			switch($key) {
				case 'social_network':
					echo ucwords($customFields['social-cache-message-type'][0]);
				break;

				case 'social_date_published':
					$publishDate = strtotime($customFields['social-cache-date-published'][0]);
					echo date('F j, Y',$publishDate);
				break;
			}
			
		}
	}

	public function onAdminColumnsOrderBy($args) {
		if (isset( $args['post_type']) && $args['post_type'] == 'bss_social_cache') {
			if (isset($args['orderby'])) {
				switch($args['orderby']) {
					case 'social_date_published':
						$args = array_merge($args,array('meta_key'=>'social-cache-date-published','orderby'=>'meta_value'));
					break;

					case 'social_network':
						$args = array_merge($args,array('meta_key'=>'social-cache-message-type','orderby'=>'meta_value'));
					break;
				}
			}
		}

		return $args;
	}

	// when the menu appears
	public function onAdminMenuHandler() {
		// url is an advanced box so we can hide the rest with JS
		add_meta_box('bss_social_cache_url','URL', array($this,'renderMetabox'), 'bss_social_cache', 'advanced', 'high', array('template'=>'metaboxes/url.php','type'=>'url') );

		// normal meta boxes
		add_meta_box('bss_social_meta','Social Metadata', array($this,'renderMetabox'), 'bss_social_cache', 'normal', 'low', array('template'=>'metaboxes/metadata.php','type'=>'metadata') );

		$submenu = add_submenu_page('edit.php?post_type=bss_social_cache', 'Social Cache Settings','Settings','manage_options','bss_social_cache',array($this,'renderSettingsPage'));
		add_action('admin_head-'. $submenu,array($this,'onSettingsAdminHeadHandler'));
	}

	// jk: edit and add pages
	public function onEnterTitleHandler($default,$post) {
		switch ($post->post_type) {
			case 'bss_social_cache':
				return 'Enter Author Here';
			break;
		}
	}

	// jk: meta boxes and custom saving
	public function renderMetabox($post,$options) {
		$data = array();
		if($options['args']['type'] == 'url') {
			$data['value'] = get_post_meta($post->ID,$options['args']['type'],true);
			if($data['value'] == '') $data['value'] = 'Enter status message URL here.';
		}
		else {
			$fields = array('social-cache-message-type','social-cache-json-cache','social-cache-date-published');
			$data['fields'] = array();

			foreach($fields as $field) {
				$data['fields'][$field] = get_post_meta($post->ID,$field,true);
			}
		}

		$this->_render($this->_pluginDirServerPath.'/templates/'.$options['args']['template'],$data);
	}

	public function onStatusMessageSaved($postId) {
		$postType = get_post_type($postId);
		if($postType == 'bss_social_cache') {
			// kill autosave
			if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				 return;
			}

			// jk: TO DO: add nonce for better verification. don't really understand that atm

    		if(!current_user_can('edit_page',$postId)) {
    			return;
    		}

    		// jk: saving
    		$fields = array('social-cache-message-url','social-cache-message-type','social-cache-json-cache','social-cache-date-published');
    		foreach($fields as $key) {
    			if(isset($_POST[$key])) {
	    			$value = $_POST[$key];
		    		update_post_meta($postId,$key,$value);
		    	}
    		}
		}

		return true;
	}

	public function onStatusMessageDeleted($postId) {
		// jk: this is probably not nearly thorough enough right now. someone should spruce this up.

		$postType = get_post_type($postId);
		if($postType == 'bss_social_cache') {
			$fields = array('social-cache-message-url','social-cache-message-type','social-cache-json-cache','social-cache-date-published');
			foreach($fields as $key) {
				delete_post_meta($postId,$key);
			}
		}

		return true;
	}

	// jk: settings page
	private function _updateSettings() {
		$settings = array();
		$settings['facebook_app_id'] = $_POST['facebook_app_id'];
		$settings['facebook_app_secret'] = $_POST['facebook_app_secret'];

		$json = json_encode($settings);

		if(get_option($this->_settingsId) != $json) {
		    update_option($this->_settingsId,$json);
		}
		else {
		    add_option($this->_settingsId,$json);
		}

		$this->_settings = $settings;

		add_action('admin_notices',function() {
            echo "<div id='bss_social_cache_warning' class='updated fade'><p><strong>Settings saved.</strong></p></div>";
		});		
	}

	public function renderSettingsPage() {
		$data = array();
		$data['settings'] = array();

		foreach($this->_settingsConfig as $id=>&$setting) {
			if($this->_settings && isset($this->_settings[$id])) {
				$setting['value'] = $this->_settings[$id];
			}
			else {
				$setting['value'] = $setting['default'];
			}

			$data['settings'][$id] = $setting;
		}

		$this->_render($this->_pluginDirServerPath.'/templates/settings.php',$data);
	}

	private function _render($template,$args) {
		ob_start();
		extract($args);
	    include($template);
	    ob_end_flush();
	}

}
?>