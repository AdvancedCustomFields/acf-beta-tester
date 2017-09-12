<?php
/*
Plugin Name: Advanced Custom Fields Beta Tester
Plugin URI: http://www.advancedcustomfields.com/
Description: Get access to the latest versions of Advanced Custom Fields
Version: 1.0.0
Author: Elliot Condon
Author URI: http://www.elliotcondon.com/
License: GPL
Copyright: Elliot Condon
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( !class_exists('acf_beta_tester') ):

class acf_beta_tester {
	
	/** @var array An array of plugin info */
	var $settings;
	
	
	/** @var string The selected version used to update */
	var $version = '';
	
	
	/**
	*  __construct
	*
	*  This function will setup the class functionality
	*
	*  @type	function
	*  @date	12/9/17
	*  @since	1.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function __construct() {
		
		// vars
		$this->settings = array(
			
			// basic
			'name'				=> __('Advanced Custom Fields Beta Tester', 'acf'),
			'version'			=> '1.0.0',
						
			// urls
			'slug'				=> dirname(plugin_basename( __FILE__ )),
			'basename'			=> plugin_basename( __FILE__ ),
			'path'				=> plugin_dir_path( __FILE__ ),
			'dir'				=> plugin_dir_url( __FILE__ ),
			
		);
		
		
		// actions
		if( is_admin() ) {
			
			add_action( 'init', array($this, 'init'), 10 );
			
		}
		
	}
	
	
	/**
	*  init
	*
	*  This function will run after WP is initialized
	*
	*  @type	function
	*  @date	11/9/17
	*  @since	1.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function init() {
		
		// modify plugins transient
		add_filter( 'pre_set_site_transient_update_plugins', array($this, 'modify_plugins_transient'), 10, 1 );
		
		
		// allow custom version to be selected
		if( current_user_can('install_plugins') ) {
			
			add_filter( 'plugin_row_meta', array($this, 'plugin_row_meta'), 10, 4 );
			add_action( 'load-plugins.php', array($this, 'load'), 10 );
			
		}
		
	}
	
	
	/**
	*  request
	*
	*  This function will make a request to an external server
	*
	*  @type	function
	*  @date	8/4/17
	*  @since	1.0.0
	*
	*  @param	$url (string)
	*  @param	$body (array)
	*  @return	(mixed)
	*/
	
	function request( $url = '', $body = null ) {
		
		// post
		$raw_response = wp_remote_post($url, array(
			'timeout'	=> 10,
			'body'		=> $body
		));
		
		
		// wp error
		if( is_wp_error($raw_response) ) {
			
			return $raw_response;
		
		// http error
		} elseif( wp_remote_retrieve_response_code($raw_response) != 200 ) {
			
			return new WP_Error( 'server_error', wp_remote_retrieve_response_message($raw_response) );
			
		}
		
		
		// vars
		$raw_body = wp_remote_retrieve_body($raw_response);
		
		
		// attempt object
		$obj = @unserialize( $raw_body );
		if( $obj ) return $obj;
		
		
		// attempt json
		$json = json_decode( $raw_body, true );
		if( $json ) return $json;
		
		
		// return
		return $json;
		
	}
	
	
	/**
	*  get_plugin_info
	*
	*  This function will get plugin info and save as transient
	*
	*  @type	function
	*  @date	9/4/17
	*  @since	1.0.0
	*
	*  @param	n/a
	*  @return	(array)
	*/
	
	function get_plugin_info() {
		
		// var
		$transient_name = 'acf_beta_tester_info';
		
		
		// delete transient (force-check is used to refresh)
		if( !empty($_GET['force-check']) ) {
		
			delete_transient($transient_name);
			
		}
	
	
		// try transient
		$transient = get_transient($transient_name);
		if( $transient !== false ) return $transient;
		
		
		// connect
		$response = $this->request('http://api.wordpress.org/plugins/info/1.0/advanced-custom-fields');
		
		
		// ensure response is expected object
		if( !is_wp_error($response) ) {
			
			// store minimal data
			$info = array(
				'version'	=> $response->version,
				'versions'	=> array_keys( $response->versions ),
				'tested'	=> $response->tested
			);
			
			
			// order versions (latest first)
			$info['versions'] = array_reverse($info['versions']);
			
			
			// update var
			$response = $info;
			
		}
		
		
		// update transient
		set_transient($transient_name, $response, HOUR_IN_SECONDS);
		
		
		// return
		return $response;
		
	}
	
	
	/**
	*  plugin_row_meta
	*
	*  This function will append extra HTML to the plugin row
	*
	*  @type	function
	*  @date	9/4/17
	*  @since	1.0.0
	*
	*  @param	(mixed)
	*  @return	(mixed)
	*/
	
	function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		
		// check if is acf
		if( $plugin_file == 'advanced-custom-fields/acf.php' ) {
			
			// append html
			$plugin_meta[] = $this->plugin_row_html();
			
		}
		
		
		// return 
		return $plugin_meta;

	}
	
	
	/**
	*  plugin_row_html
	*
	*  This function will return HTML used for the plugin row
	*
	*  @type	function
	*  @date	11/9/17
	*  @since	1.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function plugin_row_html() {
		
		// vars
		$info = $this->get_plugin_info();
			
			
		// start
		ob_start();
		
		?>
		<span id="acfbt-settings">
			<?php _e('Change update to'); ?>
			<select name="acfbt_version">
				<option value=""><?php _e('Select version') ?></option>
				<?php foreach( $info['versions'] as $version ): 
				$label = ( $version == 'trunk' ) ? 'Stable ('.$info['version'].')' : $version;
				?>
				<option value="<?php echo esc_attr($version); ?>"><?php echo esc_html($label); ?></option>	
				<?php endforeach; ?>
			</select>
			<button class="button" name="acfbt" value="<?php echo wp_create_nonce('acfbt'); ?>"><?php _e('Apply') ?></button>
		</span>
		<?php
		
		
		// get
		$html = ob_get_contents();
		ob_end_clean();
		
		
		// return
		return $html;
		
	}
	
	
	/**
	*  load
	*
	*  This function will run when loading the wp-admin/plugins.php page
	*
	*  @type	function
	*  @date	11/9/17
	*  @since	1.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function load() {
		
		// vars
		$nonce = isset($_POST['acfbt']) ? $_POST['acfbt'] : '';
		$version = isset($_POST['acfbt_version']) ? $_POST['acfbt_version'] : '';
		
		
		// bail early nonce does not match
		if( !$nonce || !wp_verify_nonce($nonce, 'acfbt') ) return;
		
		
		// vars
		$info = $this->get_plugin_info();
		
		
		// validate version
		if( empty($info['versions']) || !in_array($version, $info['versions'] ) ) return;
		
		
		// convert 'trunk' to latest version
		if( $version == 'trunk' ) $version = $info['version'];
		
		
		// set specific version
		$this->version = $version;
		
		
		// get transient
		$transient = get_site_transient( 'update_plugins' );
		
		
		// do nothing
		
		
		// trigger update
		set_site_transient( 'update_plugins', $transient );
		
	}
	
	
	/**
	*  modify_plugins_transient
	*
	*  This function will modify the 'update_plugins' transient with custom data
	*
	*  @type	function
	*  @date	11/9/17
	*  @since	1.0.0
	*
	*  @param	$transient (object)
	*  @return	$transient
	*/
	
	function modify_plugins_transient( $transient ) {
		
		// bail early if empty
		if( !$transient || empty($transient->checked) ) return $transient;
		
		
		// bail early if acf was not checked
		if( !isset($transient->checked['advanced-custom-fields/acf.php']) ) return $transient;
		
		
		// vars
		$info = $this->get_plugin_info();
		$old_version = $transient->checked['advanced-custom-fields/acf.php'];
		$new_version = $this->version;
		
		
		// no version selected, find latest tag
		if( !$new_version ) {
			
			// attempt to find latest tag
			foreach( $info['versions'] as $version ) {
				
				// ignore trunk
				if( $version == 'trunk' ) continue;
				
				
				// ignore if older than $old_version
				if( version_compare($version, $old_version, '<') ) continue;
				
				
				// ignore if older than $new_version
				if( version_compare($version, $new_version, '<') ) continue;
				
				
				// this tag is a newer version!
				$new_version = $version;
				
			}
			
		}
		
		
		// bail ealry if no $new_version
		if( !$new_version ) return $transient;
		
		
		// response
		$response = new stdClass();
		$response->id = 'w.org/plugins/advanced-custom-fields';
		$response->slug = 'advanced-custom-fields';
		$response->plugin = 'advanced-custom-fields/acf.php';
		$response->new_version = $new_version;
		$response->url = 'https://wordpress.org/plugins/advanced-custom-fields/';
		$response->package = 'https://downloads.wordpress.org/plugin/advanced-custom-fields.'.$new_version.'.zip';
		$response->tested = $info['tested'];
		
		
		// append
		$transient->response['advanced-custom-fields/acf.php'] = $response;
		
		
		
		// return 
        return $transient;
        
	}
	
}


// globals
global $acf_beta_tester;


// instantiate
$acf_beta_tester = new acf_beta_tester();


// end class
endif;

?>