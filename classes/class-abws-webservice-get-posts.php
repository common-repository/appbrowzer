<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class ABWS_Webservice_get_posts {

	private static $instance = null;

	public static $all_reply_comments = array();
	public static $reply_comments_count = 0;

	/**
	 * Get singleton instance of class
	 *
	 * @return null|ABWS_Webservice_get_posts
	 */
	#DONE #Alligning #NOCR
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
		add_action( 'wpsws_webservice_get_posts', array( $this, 'get_posts' ) );
		add_action( 'wpsws_webservice_get_post', array( $this, 'get_post' ) );
		add_action( 'wpsws_webservice_app_configuration', array( $this, 'app_configuration' ) );
		add_action( 'wpsws_webservice_article_card', array( $this, 'article_card' ) );
		add_action( 'wpsws_webservice_app_update', array( $this, 'app_update' ) );
		add_action( 'wpsws_webservice_card_layout', array( $this, 'card_layout' ) );
		add_action( 'wpsws_webservice_auth_login', array( $this, 'auth_login' ) );
		add_action( 'wpsws_webservice_register_webhook', array( $this, 'register_webhook' ) );
		add_action( 'wpsws_webservice_get_notifications', array($this, 'get_notifications' ));
		add_action( 'wpsws_webservice_get_comments', array( $this, 'get_comments_data' ) );
		add_action( 'wpsws_webservice_post_comment', array( $this, 'post_comment' ) );
	}
   
	/**
	 * Function to get the default settings
	 *
	 * @return array
	 */
	#DONE #Alligning #NOCR
	public static function get_default_settings() {
		return array( 
				'enabled' => 'false',
				'fields' => array(),
				'custom' => array() 
		);
	}

	#DONE #Alligning
	public function get_notifications() {

    	global $wpdb;

		$post_type = 'post';

		// Global options
		$options = APP_Browzer_Web_Service::get()->get_options();

		// Get 'get_posts' options
		$gp_options = array();
		if ( isset( $options['app_config'] ) ) {
			$gp_options = $options['app_config'];
		}
        
		$dbwhere = "wpost.post_type = 'post' AND (wpost.post_status = 'publish' OR wpost.post_status = 'private') AND $wpdb->terms.term_status=0 "; 
		
		$page_url = get_site_url() . '/api/get_notifications?';
		
		// if ( isset( $_GET['search'] ) ) {			
		// 	$like = '%' . $wpdb->esc_like( $_GET['search'] ) . '%';			
		// 	$dbwhere  .= $wpdb->prepare( " AND ((wpost.post_title LIKE %s) OR (wpost.post_content LIKE %s))", $like, $like );
			
		// 	$page_url = get_site_url() . '/api/get_posts?search='.urlencode($_GET['search']).'&';
		// }
		
		// if ( isset( $_GET['category'] ) ) {			
		// 	$dbwhere  .= $wpdb->prepare(" AND wp_terms.name LIKE %s",$_GET['category']);
		// 	$page_url = get_site_url() . '/api/get_posts?category='.urlencode($_GET['category']).'&';
		// }

		// Get posts
		
		if($_GET["per_page"] && $_GET["per_page"] > 0){
			$posts_per_page = $_GET["per_page"];
		/*} elseif (isset($gp_options['post_per_page']) && $gp_options['post_per_page'] >0) {
			$posts_per_page =$gp_options['post_per_page'];*/
		} else{
		  $posts_per_page = 20;
		}
		if (isset($_GET["page"]) && is_numeric($_GET["page"]) ) { $page  = $_GET["page"]; } else { $page=1; }; 
		
		if($page >0 )
		  $start_from = ($page-1) * $posts_per_page; 
		else
		  $start_from = 0;  

		$getPostsAttr = array(
					'posts_per_page' => $posts_per_page,
					'start_from' => $start_from,
					'dbwhere' => $dbwhere
		);

		$posts = ABWS_Webservice_get_posts::getPosts($getPostsAttr);
	    $post_count = $wpdb->get_row( "SELECT FOUND_ROWS() as total;" );

	    $postMetaAttr = array(
	    				'post_count' => $post_count,
	    				'posts_per_page' => $posts_per_page,
	    				'page_url' => $page_url,
	    				'page' => $page
	    );
		
		$meta_arr = ABWS_Webservice_get_posts::postMetaData($postMetaAttr);

        if($post_count->total > 0){

			$previous_page = $next_page  = ''; #CR #Made this in single Line
		  
	      	$total_pages = ceil($post_count->total / $posts_per_page); 

		  	if($start_from > 0) {		   
		   		$previous_page =  $page_url.'page='.($page-1);
		  	}

		  	if($total_pages!=$page){
		   		$next_page =  $page_url.'page='.($page+1);
		  	}
		  
		  	$meta_arr['count'] =  $post_count->total;
		  	$meta_arr['previous'] = $previous_page;
		  	$meta_arr['current'] =  $page_url.'page='.$page;
		  	$meta_arr['next'] =  $next_page;
		}

		// Data array
		$return_data = $response_data = array(); #CR #Made This in Single Line

		foreach ( $posts as $post ) {

			$post_url = get_site_url() . '/api/get_post?url='.get_permalink($post->ID);	

			$postArr  = array();

			$postArr['dynamic_ui_url'] = get_site_url() . '/api/article_card/';
			$postArr['content'] = array(
								'type' => 'object',
								'root_key' => '',
								'data_url' => $post_url
							);

			$data = array();
			$author_name = get_the_author_meta('user_nicename', $post->post_author);
			$data['featured_image'] = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );

			$data['author'] = array(
							'name' => $author_name,
							'author_id' => $post->post_author
						);

			$data['title'] = $post->post_title;
			$data['formatted_date'] = date('d M Y', strtotime($post->post_date));
			$data['featured_image'] = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );
			$data['content_url'] = get_site_url() . '/api/get_post?url='.get_permalink($post->ID);
			$data['post_id'] = $post->ID;
			
			$post_tags = wp_get_post_tags($post->ID);

			$tags = array();
			foreach($post_tags as $t) {
				$tag = get_tag($t);
				$tags[] = array(
							'tag_id' => $t->term_id,
							'name' => $tag->name,
							'slug' => $tag->slug
						);
			}

			$data['tags'] = $tags; 
			/*setup_postdata($post);
			$data['summary'] =  html_entity_decode(strip_tags(get_the_excerpt()));*/


			$trimmed_words = wp_trim_words( $post->post_content, $num_words = 50, $more = '... Read More' );
			$summary = html_entity_decode(strip_tags($trimmed_words));
			$data['summary'] =  $summary;

			$return_data[] = $data;
		}

		if(!empty($data)) {
			$response_data['meta'] = $meta_arr ;     
			$response_data['notifications'] = $return_data;  
		}
		ABWS_Output::get()->output( $response_data );

		//Return Something Here if the $data is Empty
		//TODO From Dev.
	}

	/**
	 * This is the default included 'get_posts' webservice
	 * This webservice will fetch all posts of set post type
	 *
	 * @todo
	 * - All sorts of security checks
	 * - Allow custom query variables in webservice (e.g. custom sorting, posts_per_page, etc.)
	 */

	#DONE #Alligning
	public function get_posts() {
    	global $wpdb;

		$post_type = 'post';

		// Global options
		$options = APP_Browzer_Web_Service::get()->get_options();

		// Get 'get_posts' options
		$gp_options = array();
		if ( isset( $options['app_config'] ) ) {
			$gp_options = $options['app_config'];
		}
        
         
		$dbwhere = "wpost.post_type = 'post' AND (wpost.post_status = 'publish' OR wpost.post_status = 'private') AND $wpdb->terms.term_status=0 "; 
		
		$page_url = get_site_url() . '/api/get_posts?';
		
		if ( isset( $_GET['search'] ) ) {			
			$like = '%' . $wpdb->esc_like( $_GET['search'] ) . '%';			
			$dbwhere  .= $wpdb->prepare( " AND ((wpost.post_title LIKE %s) OR (wpost.post_content LIKE %s))", $like, $like );
			
			$page_url = get_site_url() . '/api/get_posts?search='.urlencode($_GET['search']).'&';
		}
		
		// Get posts
		if($_GET["per_page"] && $_GET["per_page"] > 0){
			$posts_per_page = $_GET["per_page"];
		/*} elseif (isset($gp_options['post_per_page']) && $gp_options['post_per_page'] >0) {
			$posts_per_page =$gp_options['post_per_page'];*/
		} else{
		  $posts_per_page = 20;
		}

		#CR Made this proper If Condition by keeping in multiple line
		#$page and $start_from Intialised at top
		$page = 1; $start_from = 0;
		if (isset($_GET["page"]) && is_numeric($_GET["page"]) ) { 
			$page = $_GET["page"]; 
		}
		
		#CR Made this proper If Condition by keeping in multiple line
		if( $page > 0 ) {
		  	$start_from = ($page-1) * $posts_per_page; 
		}

		if ( isset( $_GET['category'] ) ) {	

			$category_id = intval($_GET['category']);

			if($category_id !== 0 ){
				//check if category exists
				$category = get_the_category_by_ID(intval($_GET['category']));		

				if(!empty($category)){

					$postOffset = intval($posts_per_page) * $postsPerPage;

					$category_posts_arg = array(
						'category' => $category_id,
						'posts_per_page' => $posts_per_page,
						'offset' => $start_from
					);

					$category_posts = get_posts( $category_posts_arg );	


					$category_all_posts = get_posts(
						array(
							'category' => $category_id,
							'numberposts' => -1,
						)
					);

					$post_count = new stdClass();
					$post_count->total = count($category_all_posts);
				}
			}
			$dbwhere  .= $wpdb->prepare(" AND wp_terms.name LIKE %s",$_GET['category']);
			$page_url = get_site_url() . '/api/get_posts?category='.urlencode($_GET['category']).'&';
		}

		$getPostsAttr = array(
					'posts_per_page' => $posts_per_page,
					'start_from' => $start_from,
					'dbwhere' => $dbwhere,
					'category_posts' => $category_posts
		);

		$posts = ABWS_Webservice_get_posts::getPosts($getPostsAttr);

		if(!isset($post_count)){
			$post_count = $wpdb->get_row( "SELECT FOUND_ROWS() as total;" );	
		}

	    $postMetaAttr = array(
	    				'post_count' => $post_count,
	    				'posts_per_page' => $posts_per_page,
	    				'page_url' => $page_url,
	    				'page' => $page,
	    				'start_from' => $start_from
	    );
		
		$meta_arr = ABWS_Webservice_get_posts::postMetaData($postMetaAttr);

		// Data array
		#CR #Made this in Single Line
		$return_data = $response_data = array();
        
        /*$js_file_url = plugin_dir_url( ABWS_PLUGIN_FILE ) . 'assets/js/abws.js';
		$css_file_url = plugin_dir_url( ABWS_PLUGIN_FILE ) . 'assets/css/app_style.css';*/
        
		// Loop through posts
		foreach ( $posts as $post ) {
			$return_data[] = ABWS_Webservice_get_posts::postCardData($post);
		}

		//var_dump($return_data); die;
		if(!empty($return_data)){
        	$response_data['meta'] = $meta_arr;
        	$response_data['posts'] = $return_data;  
		} else {
			$response_data['meta'] = array("count" => 0);
			$response_data['posts'] = array();
		}

		ABWS_Output::get()->output( $response_data );
	}

	#DONE #Alligning
	public function get_post() {      


		if ( ! isset( $_GET['id'] ) ) {
			APP_Browzer_Web_Service::get()->throw_error( 'No ID type set.' );
		}
		$id = esc_sql( $_GET['id'] );

		$post = get_post($id);

		if(!is_null($post) && !empty($post)) {
			/*if ( ! isset( $_GET['url'] ) ) {
				APP_Browzer_Web_Service::get()->throw_error( 'No url type set.' );
			}

			// Set post type
			$url = esc_sql( $_GET['url'] );
			$post_slug = basename($url);*/
			$post_type = 'post';

			// Global options
			$options = APP_Browzer_Web_Service::get()->get_options();

			// Get 'get_posts' options
			$gp_options = array();
			if ( isset( $options['get_posts'] ) ) {
				$gp_options = $options['get_posts'];
			}

			// Fix scenario where there are no settings for given post type
			if ( ! isset( $gp_options[$post_type] ) ) {
				$gp_options[$post_type] = array();
			}

			// Setup options
	 		$pt_options = wp_parse_args( $gp_options[$post_type], ABWS_Webservice_get_posts::get_default_settings() );

			// Setup default query vars
			$default_query_arguments = array(
			    'name'   => $post_slug,
				'posts_per_page' => 1,
				'order'          => 'ASC',
				'orderby'        => 'title',
			);

			// Get query vars
			$query_vars = array();
			if ( isset( $_GET['qv'] ) ) {
				$query_vars = $_GET['qv'];
			}

			// Merge query vars
			$query_vars = wp_parse_args( $query_vars, $default_query_arguments );

			// Set post type
			$query_vars['post_type'] = $post_type;
	        /*$js_file_url = plugin_dir_url( ABWS_PLUGIN_FILE ) . 'assets/js/abws.js';
			$css_file_url = plugin_dir_url( ABWS_PLUGIN_FILE ) . 'assets/css/app_style.css';*/
			// Get posts
			$posts = get_posts( $query_vars );
			
			if(! $posts ) {
	        	throw new Exception("NoSuchPostBySpecifiedURL");
	      	}
			
			// Data array
			$return_data = array();
	        
			//if(!empty($posts)){		
				if(!is_null($post))   {
					$return_data = ABWS_Webservice_get_posts::postCardData($post);		
				}
				
			//ss}
			
			ABWS_Output::get()->output( $return_data );	
		} else {
			APP_Browzer_Web_Service::get()->throw_error( 'No Post Found for the given ID' );
		}
	 
		
   	}
  	
  	#DONE #Alligning
  	public function app_configuration() {
		$return_data = $this->get_configuration_data();										
		ABWS_Output::get()->output( $return_data );											
  	}  
  
  	#DONE #Alligning
  	public function get_configuration_data(){
  
    	// Global options
		$options = APP_Browzer_Web_Service::get()->get_options();
        $return_data = array();   
		// Get 'app_config' options
		$gp_options = array();

		if ( isset( $options['app_config'] ) ) {
			$gp_options = $options['app_config'];
		}
		
       	$return_data['general_configuration'] = array(
            'name' => $gp_options['app_name'],
			'logo' => $gp_options['app_logo'],
			'banner' => $gp_options['app_banner'],
             /*'content_url' =>get_site_url() . '/api/get_posts/',*/
            'dynamic_ui_url' => get_site_url() . '/api/article_card/',
            'content' => array(
            				'type' => "array",
            				'root_key' => "posts",
            				'data_url' => get_site_url() ."/api/get_posts",
              				'search_url' => get_site_url() ."/api/get_posts?search=#[app.search_term]"
              			)
		);
		
		$args = array(
					'orderby'       =>  'term_order',
					'depth'         =>  0,
					'child_of'      => 0,
					'hide_empty'    =>  0,
					'taxonomy'      => 'category',
		);
		$categories = get_categories( $args ); 
		
		if(!empty($categories)){

		   $category = array();

		   foreach($categories as $terms){		     
				$visibility = ($terms->term_status == 0 ) ? true : false;
		     	$category[] = array(
			                    'id' => $terms->cat_ID,
								'name' => $terms->cat_name,
								/*'url' => get_site_url() . '/api/get_posts?category='.urlencode($terms->name),*/
								'url' => get_site_url() . '/api/get_posts?category='.urlencode($terms->cat_ID),
								'visibility' => $visibility,
								'position' => $terms->term_order
							); 
		   	}
		   	$return_data['navigation_configuration']['categories'] =  $category;
		}											
		return $return_data;									
  	}
  
  	#DONE #Alligning
  	public function article_card() {
        $options = APP_Browzer_Web_Service::get()->get_options();         
		// Get 'app_config' options
		$gp_options = array();
		if ( isset( $options['app_config'] ) ) {
			$gp_options = $options['app_config'];
		}		     
	   	header('Content-Type: application/json; charset=utf-8');	  
       	echo stripslashes($gp_options['article_card']);	  
  	}
  
  	#DONE #Alligning
  	public function app_update() {	  
		global $wpdb; 	

	 	$json = file_get_contents('php://input');
     	$postData = json_decode($json,true);    
	 	ABWS_Catch_Request::get()->check_auth_key(); 
	        
		if(!empty($postData)){
		
			$optionsArr = APP_Browzer_Web_Service::get()->get_options();

			//Below Code is Commented Out for the reason It should accept Image Url instead of Base 64 

			/*$file_url = '';		

			if(isset($postData['logo']) && $postData['logo'] != '') {			
				$filteredData=substr($postData['logo'], strpos($postData['logo'], ",")+1);
				$unencodedData=base64_decode($filteredData);			
				$f = finfo_open();
	            $mime_type = finfo_buffer($f, $unencodedData, FILEINFO_MIME_TYPE);
	            $split = explode( '/', $mime_type );
	            $type  = $split[1]; 						
				$filename = uniqid().".{$type}";
				
				$wp_upload_dir = wp_upload_dir();
				
				$file          = $wp_upload_dir['path'] . '/' .$filename;
				$file_url      = $wp_upload_dir['url'] . '/' .$filename;				
				$fp            = fopen( $file, 'wb' );
				fwrite( $fp, $unencodedData);
				fclose( $fp );
			}

			/// For Banner Image
			$banner_url = '';

			if(isset($postData['banner']) && $postData['banner'] != '' ){		
			
				$filteredData = substr($postData['banner'], strpos($postData['banner'], ",")+1);
				$unencodedData=base64_decode($filteredData);			
				$f = finfo_open();
            	$mime_type = finfo_buffer($f, $unencodedData, FILEINFO_MIME_TYPE);
            	$split = explode( '/', $mime_type );
            	$type  = $split[1]; 						
				$filename = uniqid().".{$type}";
			
				$wp_upload_dir = wp_upload_dir();
			
				$file          = $wp_upload_dir['path'] . '/' .$filename;
				$banner_url    = $wp_upload_dir['url'] . '/' .$filename;				
				$fp            = fopen( $file, 'wb' );
				fwrite( $fp, $unencodedData);
				fclose( $fp );			
			}*/

			$logo_url = $postData['logo'];
			$banner_url = $postData['banner'];
	  	
	  		$app_name = ( $postData['app_name'] != '' ) ? $postData['app_name']:$optionsArr['app_config']['app_name'];

	  		$article_card = ( $postData['card_layout']!='' ) ? json_encode($postData['card_layout']) : $optionsArr['app_config']['article_card'];

	  		$file_url = ($file_url!='')?$file_url:$optionsArr['app_config']['app_logo'];

	  		$banner_url = ($banner_url!='')?$banner_url:$optionsArr['app_config']['app_banner'];

	  		$navigation = ( $postData['navigation'] != '' ) ? $postData['app_name']:$optionsArr['app_config']['app_name'];
	  
	  		$optionsArr['app_config'] = array(
	  			'app_name' => wp_unslash($app_name),
	  			'app_banner' => $banner_url,
	  			'app_logo' => $logo_url,
	  			'article_card' => wp_unslash($article_card),
	  			'navigation' => $navigation,
	  			'theme_color' => $postData['theme_color'],
	  			'post_per_page' => $optionsArr['app_config']['post_per_page']
	  		); 
	  	  	  
	  		APP_Browzer_Web_Service::get()->save_options( $optionsArr );
	  
	  		if(isset($postData['navigation']) && $postData['navigation']!='') {
		   		foreach($postData['navigation'] as $naviData) {
					$term    = get_term_by('name', $naviData['name'], 'category');
					$status  = ($naviData['visibility']) ? 0 : 1;
			   		if($term->term_id!=''){
				 		$wpdb->update( 
				 				$wpdb->terms,
				 				array(
				 					'term_order' => $naviData['position'],
				 					'term_status' => $status
				 				),
				 				array('term_id' => $term->term_id) 
				 		); 
					}
		    	}
	    	} 
	   
	    	$return_data = $this->get_configuration_data();										
			ABWS_Output::get()->output( $return_data );	
	   
	 	} else {
	   		ABWS_Output::get()->output( array('error'=>'Empty json raw data') ); 
	 	}
   	}
   
   	#DONE #Alligning
   	public function card_layout() {	 
		global $wpdb;  
		
		$json = file_get_contents('php://input');
    	$postData = json_decode($json,true);
    
    	ABWS_Catch_Request::get()->check_auth_key(); 
    
		if(!empty($postData)){	 
	  
	  		$optionsArr = APP_Browzer_Web_Service::get()->get_options();		
	  	
	  		$app_name = $optionsArr['app_config']['app_name'];
	  		$article_card = ( $postData['card_layout'] != '' ) ? json_encode($postData['card_layout']) : $optionsArr['app_config']['article_card'];
	  		$file_url = $optionsArr['app_config']['app_logo'];	
	  
	  		$banner_url  = $optionsArr['app_config']['app_banner'];
	  
	  		$optionsArr['app_config'] = array(
	  						'app_name' => wp_unslash($app_name),
	  						'app_banner' => $banner_url,
	  						'app_logo' => $file_url,
	  						'article_card' => wp_unslash($article_card),
	  						'post_per_page' => $optionsArr['app_config']['post_per_page']
	  		); 
	  
	   		APP_Browzer_Web_Service::get()->save_options( $optionsArr );
	   
	   		$return_data = array('status'=>'success');
	   		ABWS_Output::get()->output( $return_data );
	 	} else {
	   		ABWS_Output::get()->output( array('error'=>'Empty json raw data') ); 
	 	}
   	}
   

	  #DONE #Alligning #CR
   	public function auth_login() {

	   	global $wpdb;

	   	$redirect_url = isset($_GET['redirect_uri']) ? $_GET['redirect_uri'] : 'http%3A%2F%2Fappbrowzer.com%2Fdashboard%2FgetStarted';

	   	$state = isset($_GET['state']) ? $_GET['state'] : '';
	   	/*if(empty($redirect_url)) {
			echo '<div id="login_error">' . apply_filters( 'login_errors', 'Return url not defined.' ) . "</div>\n";
			exit;	
		}*/
		
		$optionsArr = APP_Browzer_Web_Service::get()->get_options();	    
		if(!isset($optionsArr['ABWS_auth_key'])){
			$sec_key = wp_generate_password( 48, false );
			$optionsArr['ABWS_auth_key'] = $sec_key;	
		} else {
			$sec_key = $optionsArr['ABWS_auth_key'];
		}

		#CR #defined at top of if condition
	   
	   	if(is_user_logged_in() ){
			/*$sec_key = wp_generate_password( 48, false );
		   	$optionsArr = APP_Browzer_Web_Service::get()->get_options();	    
		   	$optionsArr['ABWS_auth_key'] = $sec_key;*/
		   	//APP_Browzer_Web_Service::get()->save_options( $optionsArr );
		   	$redirect_url.='?auth_key='.$sec_key.'&state='.$state;
		   	$redirect_url = urldecode($redirect_url);
		   	//wp_redirect($redirect_url);		 
	   	} else {
			/*$sec_key = wp_generate_password( 48, false );
		   	$optionsArr = APP_Browzer_Web_Service::get()->get_options();	    
		   	$optionsArr['ABWS_auth_key'] = $sec_key;*/
		   	$optionsArr['redirect_uri']  = $redirect_url;
		  	$optionsArr['ABWS_state']  = $state;
		   	//APP_Browzer_Web_Service::get()->save_options( $optionsArr );
		   
		  	$redirect_url = site_url( 'wp-login.php')."?redirect_to=$redirect_url" ; 

		  	/*$login_url = site_url( 'wp-login.php')."?redirect_to=$redirect_url" ; 
	      	wp_redirect($login_url);*/
	   	}
		APP_Browzer_Web_Service::get()->save_options( $optionsArr );
		wp_redirect($redirect_url);
	}

    
    public function register_webhook(){
		global $wpdb; 
		$json = file_get_contents('php://input');
		$postData = json_decode($json,true);
		
		ABWS_Catch_Request::get()->check_auth_key();
		
		if(!empty($postData)) { 
			$optionsArr = APP_Browzer_Web_Service::get()->get_options();
			$optionsArr['webhook_url']  = $postData['webhook_url'];
		 	APP_Browzer_Web_Service::get()->save_options( $optionsArr );
		} else {
	    	ABWS_Output::get()->output( array('error'=>'Empty json raw data') ); 
	    }
	}  

	public function get_comments_data(){
		if ( ! isset( $_GET['id'] ) ) {
			APP_Browzer_Web_Service::get()->throw_error( 'No ID type set.' );
		}
		$id = esc_sql( $_GET['id'] );

		$post = get_post($id);
		//$post = ABWS_Webservice_get_posts::findPostByUrl($id);

		if(is_null($post) || !empty($post)){

			$return_data = array();

			//get_comments is the wordpress default method to get the comments
			if ( isset( $_GET['comment_id'] ) && !empty($_GET['comment_id']) ) {
				//get the Replies of the Comment
				$comments = get_comments(array('post_id' => $post->ID, 'parent' => $_GET['comment_id'], 'order' => 'ASC'));

				self::getCommentReplies($comments);
				$reply_comments = self::$all_reply_comments;
				$comments = array_merge($comments,$reply_comments);

			} else {
				//and args parent set to 0 because to not load the reply comments
				$comments = get_comments(array('post_id' => $post->ID, 'order' => 'ASC'));	
			}

			$meta_arr = array('count' => count($comments));
			$return_data['meta'] = $meta_arr;
			$return_data['comments'] = ABWS_Webservice_get_posts::formCommentsJson($comments);	
			ABWS_Output::get()->output( $return_data );
		} else {
			APP_Browzer_Web_Service::get()->throw_error( 'No Post Found for the given ID' );
		}
		
	}

	public function post_comment(){
		global $wpdb;
    
    	ABWS_Catch_Request::get()->check_auth_key(); 
    
		if(!empty($_POST)){	 
			$postData = $_POST;
			$validator = CustomValidator::postCommentValidator($postData);

			//get the Post Object
			$post_url = esc_sql( $_POST['post_url'] );
			$post = ABWS_Webservice_get_posts::findPostByUrl($post_url);

			if(!empty($post)){

				$current_time_gmt = gmdate("Y-m-d H:i:s");
				$current_time_obj = new DateTime();
				$current_time = $current_time_obj->format('Y-m-d H:i:s');

				$comment_parent_id = 0;

				if(!is_null($postData['comment_parent_id']) && !empty($postData['comment_parent_id'])){
					$comment_parent_id = $postData['comment_parent_id'];
				}

				$comment_agent = $_SERVER['HTTP_USER_AGENT'];

				if(!is_null($_SERVER['HTTP_X_USER_AGENT'])){
					$comment_agent = $_SERVER['HTTP_X_USER_AGENT'];
				}


				/*$wpdb->insert('wp_comments', array(
				    'comment_post_ID' => $post->ID,
				    'comment_author' => $postData['name'],
				    'comment_author_email' => $postData['email'],
				    'comment_content' => $postData['content'],
				    'comment_date' => $current_time,
				    'comment_date_gmt' => $current_time_gmt,
				    'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
				    'comment_agent' => $comment_agent,
				    'comment_parent' => $comment_parent_id
				));*/


				$commentdata = array(
					'comment_post_ID' => $post->ID,
				    'comment_author' => $postData['name'],
				    'comment_author_email' => $postData['email'],
				    'comment_content' => $postData['content'],
				    'comment_date' => $current_time,
				    'comment_date_gmt' => $current_time_gmt,
				    'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
				    'comment_agent' => $comment_agent,
				    'comment_parent' => $comment_parent_id
				);

				#TODO Validate the Comment whether it is duplicate and Valid
				$valid_comment_checker = CustomValidator::custom_wp_allow_comment( $commentdata );

				if($valid_comment_checker['success']){
					//Insert new comment and get the comment ID
					$comment_id = wp_new_comment( $commentdata );

					//check whether the admin has auto approve comment settings activated
					$comment_array = get_comments(array('ID' => $comment_id));
					$comment = $comment_array[0];	

					$returnResponse = array();

					$returnResponse = ABWS_Webservice_get_posts::getCommentArray($comment);
					return ABWS_Output::get()->success_output( $returnResponse );	
				}
				return ABWS_Output::get()->output( $valid_comment_checker );	
			}
			return ABWS_Output::get()->output( array('error'=>'Post Not Found!!') ); 
		} 
	   	ABWS_Output::get()->output( array('error'=>'Empty Data') ); 
	 	
	}

	//////////////////////////////// Static Methods /////////////////////////
	//////////////////////////// Developer : Murali///////////////////////////

	public static function postCardData($post){

		$js_file_url = plugin_dir_url( ABWS_PLUGIN_FILE ) . 'assets/js/abws.js';
		$css_file_url = plugin_dir_url( ABWS_PLUGIN_FILE ) . 'assets/css/app_style.css';

		#CR Made this in Single Line Intialization
	   	$data = $images = $videos = array();
	   
       	$data['post_id'] = $post->ID;
	   	//$data['comments-count'] = wp_count_comments( $post->ID )->all;
	   	$data['comments-count'] = count(get_comments(array('post_id' => $post->ID, 'parent' => 0)));
	   	$data['permalink'] = get_permalink($post->ID);
	   	$data['featured_image'] = wp_get_attachment_url( get_post_thumbnail_id($post->ID) );
	   	$author_name = get_the_author_meta('user_nicename', $post->post_author);
	   	$data['author'] = array('name' =>$author_name,'author_id'=>$post->post_author);
	   	$data['post_type'] = $post->post_type;
	   
	    $post_categories = wp_get_post_categories( $post->ID);
		$cats = array();
			
		foreach($post_categories as $c){
			$cat = get_category( $c );
			$cats[] = array( 
						'cat_id' => $c,
						'name' => $cat->name,
						'slug' => $cat->slug 
					);
		}

	   	$data['categories'] = $cats;
		  	$post_tags = wp_get_post_tags($post->ID);

			$tags = array();
		foreach($post_tags as $t) {
			$tag = get_tag($t);
			$tags[] = array(
						'tag_id' => $t->term_id,
						'name' => $tag->name,
						'slug' => $tag->slug
					);
		}

	   	$data['tags'] = $tags; 
	   	$data['title'] = $post->post_title;
	   	$data['date']  = $post->post_date;
	   	$data['formatted_date'] = date('d M Y', strtotime($post->post_date));
	   
	   	$media_images = get_attached_media( 'image',$post->ID ); 
		if ( $media_images ) {
			foreach ( $media_images as $media_image ) {
				$images[] = array('url' => wp_get_attachment_url( $media_image->ID));
			}
		}
	   	$data['images'] = $images;
	   
	   	$media_videos = get_attached_media( 'video',$post->ID ); 
		if ( $media_videos ) {
			foreach ( $media_videos as $media_video ) {
				$videos[] = array('url' => wp_get_attachment_url( $media_video->ID));
			}
		}
		$data['videos'] = $videos;

		$data['lazy']  = false;		 
		$data['content_url'] = get_site_url() . '/api/get_post?url='.get_permalink($post->ID);

		$post_content = apply_filters('the_content', $post->post_content);

		$content_head = '<!DOCTYPE html><html dir="ltr"><head><meta charset="utf-8"><meta name="description" content=""><meta name="keywords" content=""><meta name="language" content="en"/><meta name="viewport" content="width=device-width, minimum-scale=1, maximum-scale=1, user-scalable=no"> <link href="'.$css_file_url.'" rel="stylesheet" media="all" /><script type="text/javascript" src="'.$js_file_url.'"></script> </head>';

		$content_body = '<body class="ab_body"> <article class="ab_article">  <h1 class="gamma ab_post_title">'.$post->post_title.'</h1> <div class="thin-border"></div></div> <div class="ab_post_meta ab_post_author"><span>By </span>'.$author_name.'</div><div class="ab_post_meta ab_post_date"><time title="'.$post->post_date.'">'.mysql2date('F j, Y', $post->post_date).'</time></div><div class="ab_clear"></div>'.$post_content.'</article> </body></html>';

		$content = $content_head . $content_body;
		$data['content'] = $content;
		$data['sticky']  = is_sticky($post->ID);	   

		
		/*setup_postdata($post);
		global $post;*/
		//var_dump(html_entity_decode(strip_tags($post->post_content))); die;
		/*var_dump($summary); die;*/
		//$data['summary'] =  html_entity_decode(strip_tags(get_the_excerpt()));

		$trimmed_words = wp_trim_words( $post->post_content, $num_words = 50, $more = '... Read More' );
		$summary = html_entity_decode(strip_tags($trimmed_words));
		$data['summary'] =  $summary;

		return $data;
	}  

	public static function postMetaData($attributes){

		$post_count = $attributes['post_count'];
		$posts_per_page = $attributes['posts_per_page'];
		$page_url = $attributes['page_url'];
		$page = $attributes['page'];
		$start_from = $attributes['start_from'];

		$meta_arr = array();
		if($post_count->total > 0){
			$previous_page = $next_page  = ''; #CR #Made this in single Line
		  	$total_pages = ceil($post_count->total / $posts_per_page); 
		  	if($start_from > 0) {		 
		   		$previous_page =  $page_url.'page='.($page-1);
		  	}
		  	if($total_pages != $page ){
		   		$next_page =  $page_url.'page='.($page+1);
		  	}

		  	$meta_arr['count'] =  intval($post_count->total);
		  	$meta_arr['previous'] = $previous_page;
		  	$meta_arr['current'] =  $page_url.'page='.$page;
		  	$meta_arr['next'] =  $next_page;
		}

		return $meta_arr;
	}  	

	public static function getPosts($attributes) {

		if(!is_null($attributes['category_posts'])){
			return $attributes['category_posts'];
		}

		global $wpdb;

		$posts_per_page = $attributes['posts_per_page'];
		$start_from = $attributes['start_from'];
		$dbwhere = $attributes['dbwhere'];

		$querystr = "SELECT SQL_CALC_FOUND_ROWS DISTINCT wpost.*
						FROM $wpdb->posts as wpost
						INNER JOIN $wpdb->term_relationships
						ON (wpost.ID = $wpdb->term_relationships.object_id)
						INNER JOIN $wpdb->term_taxonomy
						ON ($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)
						INNER JOIN $wpdb->terms
						ON ($wpdb->terms.term_id = $wpdb->term_taxonomy.term_id)
						AND $wpdb->term_taxonomy.taxonomy = 'category' 
						WHERE $dbwhere ORDER BY wpost.post_date DESC LIMIT $start_from, $posts_per_page";
					/*Removed the sort by term_order for getting the recent notifications*/
					/*WHERE $dbwhere ORDER BY $wpdb->terms.term_order ASC, wpost.post_date DESC LIMIT $start_from, $posts_per_page*/

        $posts = $wpdb->get_results($querystr, OBJECT);
        return $posts;
	}

	public static function findPostByUrl($url){
		$post_slug = basename($url);
		$post_type = 'post';

		// Global options
		$options = APP_Browzer_Web_Service::get()->get_options();

		// Get 'get_posts' options
		$gp_options = array();
		if ( isset( $options['get_posts'] ) ) {
			$gp_options = $options['get_posts'];
		}

		// Fix scenario where there are no settings for given post type
		if ( ! isset( $gp_options[$post_type] ) ) {
			$gp_options[$post_type] = array();
		}

		// Setup options
 		$pt_options = wp_parse_args( $gp_options[$post_type], ABWS_Webservice_get_posts::get_default_settings() );

		// Setup default query vars
		$default_query_arguments = array(
		    'name'   => $post_slug,
			'posts_per_page' => 1,
			'order'          => 'ASC',
			'orderby'        => 'title',
		);

		// Get query vars
		$query_vars = array();
		if ( isset( $_GET['qv'] ) ) {
			$query_vars = $_GET['qv'];
		}

		// Merge query vars
		$query_vars = wp_parse_args( $query_vars, $default_query_arguments );

		// Set post type
		$query_vars['post_type'] = $post_type;
        /*$js_file_url = plugin_dir_url( ABWS_PLUGIN_FILE ) . 'assets/js/abws.js';
		$css_file_url = plugin_dir_url( ABWS_PLUGIN_FILE ) . 'assets/css/app_style.css';*/
		// Get posts
		$posts = get_posts( $query_vars );

		/*if(! $posts ) {
			var_dump('NoSuchPostBySpecifiedURL'); die;
        	throw new Exception("NoSuchPostBySpecifiedURL");
      	}*/
        
		if(!empty($posts)){		 
			$post = $posts[0];
			return $post;
		}
		return array();
	}

	public static function formCommentsJson($comments){
		$return_array = array();

		foreach ($comments as $key => $comment) {
			array_push($return_array,ABWS_Webservice_get_posts::getCommentArray($comment));
		}

		return $return_array;
	}

	public static function getCommentArray($comment){
		$comment_array = array();
		$comment_array['id'] = $comment->comment_ID;

		$author_array = array(
							'name' => $comment->comment_author,
							'email' => $comment->comment_author_email,
							'url' => $comment->comment_author_url
						);
		$comment_array['author'] = $author_array;
		$comment_array['content'] = $comment->comment_content;
		$comment_array['commented_date'] = $comment->comment_date;
		$comment_array['commented_at'] = human_time_diff( strtotime( $comment->comment_date ), current_time('timestamp') ) . ' ago';
		$comment_array['is_approved'] = $comment->comment_approved == "1" ? true : false;
		$comment_array['comment_parent_id'] = $comment->comment_parent;
		$reply_comments = get_comments(array('parent' => $comment->comment_ID));

		self::$reply_comments_count = 0;

		self::getAllReplyComments($reply_comments);

		$comment_array['replies_count'] = self::$reply_comments_count;

		/*if(!empty($reply_comments)){
			$comment_array['reply_comments'] = ABWS_Webservice_get_posts::formCommentsJson($reply_comments);
		}*/
		return $comment_array;
	}

	public static function getCommentReplies($comments){
		//get all the reply Hierachies
		foreach ($comments as $key => $comment) {

			//check for reply comment
			$reply_comments = get_comments(array('parent' => $comment->comment_ID, 'order' => 'ASC'));			

			foreach ($reply_comments as $key => $reply_comment) {
				array_push(self::$all_reply_comments, $reply_comment);
			}

			if(count($reply_comments) !== 0){
				self::getCommentReplies($reply_comments);
			}
		}
	}

	public static function getAllReplyComments($reply_comments){
		foreach ($reply_comments as $key => $rc) {
			self::$reply_comments_count = self::$reply_comments_count + 1;
			$h_reply_comments = get_comments(array('parent' => $rc->comment_ID));
			if(count($h_reply_comments) !== 0){
				self::getAllReplyComments($h_reply_comments);
			}
		}
	}

}
