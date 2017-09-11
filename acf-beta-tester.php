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
	
	// vars
	var $settings;
	
	
	/*
	*  __construct
	*
	*  This function will setup the class functionality
	*
	*  @type	function
	*  @date	5/03/2014
	*  @since	5.0.0
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
		
	}
	
	
}


// globals
global $acf_beta_tester;


// instantiate
$acf_beta_tester = new acf_beta_tester();


// end class
endif;

?>