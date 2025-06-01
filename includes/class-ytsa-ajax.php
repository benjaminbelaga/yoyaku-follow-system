<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class YFS_Ajax {
    private $db_handler;

    public function __construct( YFS_DB $db_handler ) {
        $this->db_handler = $db_handler;
    }

    public function init() {
        add_action( 'wp_ajax_yfs_toggle_follow', array( $this, 'toggle_follow_term' ) );
        // Note: No wp_ajax_nopriv_ for follow, as users must be logged in.
    }

    public function toggle_follow_term() {
        check_ajax_referer( 'yfs_follow_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in to follow.', 'yoyaku-follow-system' ) ), 403 );
        }

        $user_id = get_current_user_id();
        $term_taxonomy_id = isset( $_POST['term_taxonomy_id'] ) ? intval( $_POST['term_taxonomy_id'] ) : 0;

        if ( ! $term_taxonomy_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid term specified.', 'yoyaku-follow-system' ) ), 400 );
        }
        
        $term = get_term_by('term_taxonomy_id', $term_taxonomy_id);
        if (!$term || is_wp_error($term)) {
            wp_send_json_error( array( 'message' => __( 'Term not found.', 'yoyaku-follow-system' ) ), 404 );
        }
        $term_name = $term->name;


        $is_currently_following = $this->db_handler->is_user_following_term( $user_id, $term_taxonomy_id );

        if ( $is_currently_following ) {
            $result = $this->db_handler->unfollow_term( $user_id, $term_taxonomy_id );
            if ( $result !== false ) {
                wp_send_json_success( array( 
                    'status' => 'unfollowed', 
                    'message' => sprintf( __( 'Successfully unfollowed %s.', 'yoyaku-follow-system' ), $term_name )
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Could not unfollow. Please try again.', 'yoyaku-follow-system' ) ) );
            }
        } else {
            $result = $this->db_handler->follow_term( $user_id, $term_taxonomy_id );
             if ( $result ) { // $result is number of rows inserted or false on error
                wp_send_json_success( array( 
                    'status' => 'followed', 
                    'message' => sprintf( __( 'Successfully followed %s.', 'yoyaku-follow-system' ), $term_name )
                ) );
            } else {
                wp_send_json_error( array( 'message' => __( 'Could not follow. Please try again.', 'yoyaku-follow-system' ) ) );
            }
        }
        // wp_die() is called automatically by wp_send_json_success/error.
    }
}