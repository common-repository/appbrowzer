<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class ABWS_Catch_Request {

	private static $instance = null;

	/**
	 * Get singleton instance of class
	 *
	 * @return null|ABWS_Catch_Request
	 */
	public static function get() {

		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->hooks();
	}

	/**
	 * Setup hooks
	 */
	private function hooks() {
		add_action( 'template_redirect', array( $this, 'handle_request' ) );
	}

	/**
	 * Handle webservice request
	 */
	public function handle_request() {
		global $wp_query;

		if ( $wp_query->get( 'api' ) ) {
			if ( $wp_query->get( 'service' ) != '' ) { 
				// Check if the action exists
				if ( has_action( 'wpsws_webservice_' . $wp_query->get( 'service' ) ) ) {
					// Do action
					do_action( 'wpsws_webservice_' . $wp_query->get( 'service' ) );
					// Bye
					exit;
				}
			}
			wp_die( 'Webservice not found' );
		}
	}

	#CR Made a Static Method for error message
    public function check_auth_key(){
		$options = APP_Browzer_Web_Service::get()->get_options();
        $auth_key  = isset($_SERVER['HTTP_AUTH_KEY'])?$_SERVER['HTTP_AUTH_KEY']:'';	      
		if(empty($auth_key)){
			/*header('X-Authenticated: False');
			header('HTTP/1.0 401 Unauthorized');							 
			ABWS_Output::get()->output( array('error'=>'Auth key is required for web service.') );
			exit;*/
			ABWS_Catch_Request::authKeyFailure(array('error'=>'Auth key is required for web service.'));
		} 
		if(!isset($options['ABWS_auth_key'])){
			/*header('X-Authenticated: False');
			header('HTTP/1.0 401 Unauthorized');					 
			ABWS_Output::get()->output( array('error'=>'Please set a auth key in General Configuration.') );
			exit;*/

			ABWS_Catch_Request::authKeyFailure(array('error'=>'Please set a auth key in General Configuration.'));
		}
		if($auth_key != $options['ABWS_auth_key']){
			/*header('X-Authenticated: False');
			header('HTTP/1.0 401 Unauthorized');	
			ABWS_Output::get()->output( array('error'=>'Invalid auth key.') );
			exit;	*/
			ABWS_Catch_Request::authKeyFailure(array('error'=>'Invalid auth key.'));
		}
  	}	


  	//////////////////// Static Methods /////////////////////////
	//////////////////////////// Developer : Murali//////////////

  	public static function authKeyFailure($output){
  		header('X-Authenticated: False');
		header('HTTP/1.0 401 Unauthorized');							 
		ABWS_Output::get()->output( $output );
		exit;
  	}
	

}
