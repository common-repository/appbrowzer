<?php

class CustomValidator {

	private function __construct() {

	}

	public static function postCommentValidator($data){

		/*if(!isset($data['post_url']) && empty($data['post_url'])){
			ABWS_Output::get()->output( array('error'=>'Post URL is Required') ); 		
		}*/

		if(is_null($data['post_url']) && empty($data['post_url'])){
			ABWS_Output::get()->output( array('error'=>'Post URL is required') ); 		
		}

		if(is_null($data['content']) && empty($data['content'])){
			ABWS_Output::get()->output( array('error'=>'Comment content is required!') ); 		
		}

		if(is_null($data['name']) && empty($data['name'])){
			ABWS_Output::get()->output( array('error'=>'User Name is Required') ); 		
		}

		if(is_null($data['email']) && empty($data['email'])){
			ABWS_Output::get()->output( array('error'=>'Email is Required') ); 		
		}

		/*if(!isset($data['user_email'])){
			ABWS_Output::get()->output( array('error'=>'User Email is required!') ); 		
		}*/

	}


	public static function custom_wp_allow_comment( $commentdata ) {
		global $wpdb;

		// Simple duplicate check
		// expected_slashed ($comment_post_ID, $comment_author, $comment_author_email, $comment_content)
		$dupe = $wpdb->prepare(
			"SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_parent = %s AND comment_approved != 'trash' AND ( comment_author = %s ",
			wp_unslash( $commentdata['comment_post_ID'] ),
			wp_unslash( $commentdata['comment_parent'] ),
			wp_unslash( $commentdata['comment_author'] )
		);
		if ( $commentdata['comment_author_email'] ) {
			$dupe .= $wpdb->prepare(
				"OR comment_author_email = %s ",
				wp_unslash( $commentdata['comment_author_email'] )
			);
		}
		$dupe .= $wpdb->prepare(
			") AND comment_content = %s LIMIT 1",
			wp_unslash( $commentdata['comment_content'] )
		);

		$dupe_id = $wpdb->get_var( $dupe );

		/**
		 * Filters the ID, if any, of the duplicate comment found when creating a new comment.
		 *
		 * Return an empty value from this filter to allow what WP considers a duplicate comment.
		 *
		 * @since 4.4.0
		 *
		 * @param int   $dupe_id     ID of the comment identified as a duplicate.
		 * @param array $commentdata Data for the comment being created.
		 */
		$dupe_id = apply_filters( 'duplicate_comment_id', $dupe_id, $commentdata );

		if ( $dupe_id ) {
			/**
			 * Fires immediately after a duplicate comment is detected.
			 *
			 * @since 3.0.0
			 *
			 * @param array $commentdata Comment data.
			 */
			do_action( 'comment_duplicate_trigger', $commentdata );
			if ( defined( 'DOING_AJAX' ) ) {
				die( __('Duplicate comment detected; it looks as though you&#8217;ve already said that!') );
			}

			return array('success' => false, 'error'=>'Duplicate comment detected; it looks as though you&#8217;ve already said that!');

			//return ABWS_Output::get()->output( array('error'=>'Duplicate comment detected; it looks as though you&#8217;ve already said that!') ); 		
			//wp_die( __( 'Duplicate comment detected; it looks as though you&#8217;ve already said that!' ), 409 );
		}

		/**
		 * Fires immediately before a comment is marked approved.
		 *
		 * Allows checking for comment flooding.
		 *
		 * @since 2.3.0
		 *
		 * @param string $comment_author_IP    Comment author's IP address.
		 * @param string $comment_author_email Comment author's email.
		 * @param string $comment_date_gmt     GMT date the comment was posted.
		 */
		do_action(
			'check_comment_flood',
			$commentdata['comment_author_IP'],
			$commentdata['comment_author_email'],
			$commentdata['comment_date_gmt']
		);

		if ( ! empty( $commentdata['user_id'] ) ) {
			$user = get_userdata( $commentdata['user_id'] );
			$post_author = $wpdb->get_var( $wpdb->prepare(
				"SELECT post_author FROM $wpdb->posts WHERE ID = %d LIMIT 1",
				$commentdata['comment_post_ID']
			) );
		}

		if ( isset( $user ) && ( $commentdata['user_id'] == $post_author || $user->has_cap( 'moderate_comments' ) ) ) {
			// The author and the admins get respect.
			$approved = 1;
		} else {
			// Everyone else's comments will be checked.
			if ( check_comment(
				$commentdata['comment_author'],
				$commentdata['comment_author_email'],
				$commentdata['comment_author_url'],
				$commentdata['comment_content'],
				$commentdata['comment_author_IP'],
				$commentdata['comment_agent'],
				$commentdata['comment_type']
			) ) {
				$approved = 1;
			} else {
				$approved = 0;
			}

			if ( wp_blacklist_check(
				$commentdata['comment_author'],
				$commentdata['comment_author_email'],
				$commentdata['comment_author_url'],
				$commentdata['comment_content'],
				$commentdata['comment_author_IP'],
				$commentdata['comment_agent']
			) ) {
				$approved = EMPTY_TRASH_DAYS ? 'trash' : 'spam';
			}
		}

		/**
		 * Filter a comment's approval status before it is set.
		 *
		 * @since 2.1.0
		 *
		 * @param bool|string $approved    The approval status. Accepts 1, 0, or 'spam'.
		 * @param array       $commentdata Comment data.
		 */
		$approved = apply_filters( 'pre_comment_approved', $approved, $commentdata );
		return array('success' => true, 'approved' => $approved );
	}
} 
