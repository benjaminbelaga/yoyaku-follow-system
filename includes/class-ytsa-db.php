<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class YFS_DB {
    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'yoyaku_user_follows';
    }

    /**
     * Check if a user is following a specific term_taxonomy_id.
     *
     * @param int $user_id User ID.
     * @param int $term_taxonomy_id Term Taxonomy ID.
     * @return bool True if following, false otherwise.
     */
    public function is_user_following_term( $user_id, $term_taxonomy_id ) {
        if ( ! absint( $user_id ) || ! absint( $term_taxonomy_id ) ) {
            return false;
        }
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE user_id = %d AND term_taxonomy_id = %d",
                $user_id,
                $term_taxonomy_id
            )
        );
        return (bool) $count;
    }

    /**
     * Add a follow record for a user and term_taxonomy_id.
     *
     * @param int $user_id User ID.
     * @param int $term_taxonomy_id Term Taxonomy ID.
     * @return bool|int False on error, number of rows affected on success.
     */
    public function follow_term( $user_id, $term_taxonomy_id ) {
        if ( ! absint( $user_id ) || ! absint( $term_taxonomy_id ) ) {
            return false;
        }

        // Ensure term_taxonomy_id is valid by checking if it exists in wp_term_taxonomy
        $term_exists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT term_taxonomy_id FROM {$this->wpdb->term_taxonomy} WHERE term_taxonomy_id = %d",
                $term_taxonomy_id
            )
        );

        if ( ! $term_exists ) {
            return false; // Term does not exist
        }

        return $this->wpdb->insert(
            $this->table_name,
            array(
                'user_id'          => $user_id,
                'term_taxonomy_id' => $term_taxonomy_id,
                'follow_date'      => current_time( 'mysql', 1 ), // GMT time
            ),
            array( '%d', '%d', '%s' )
        );
    }

    /**
     * Remove a follow record for a user and term_taxonomy_id.
     *
     * @param int $user_id User ID.
     * @param int $term_taxonomy_id Term Taxonomy ID.
     * @return bool|int False on error, number of rows affected on success.
     */
    public function unfollow_term( $user_id, $term_taxonomy_id ) {
        if ( ! absint( $user_id ) || ! absint( $term_taxonomy_id ) ) {
            return false;
        }
        return $this->wpdb->delete(
            $this->table_name,
            array(
                'user_id'          => $user_id,
                'term_taxonomy_id' => $term_taxonomy_id,
            ),
            array( '%d', '%d' )
        );
    }

    /**
     * Get all user IDs following a specific term_taxonomy_id.
     *
     * @param int $term_taxonomy_id Term Taxonomy ID.
     * @return array Array of user IDs.
     */
    public function get_followers_of_term( $term_taxonomy_id ) {
        if ( ! absint( $term_taxonomy_id ) ) {
            return array();
        }
        return $this->wpdb->get_col(
            $this->wpdb->prepare(
                "SELECT user_id FROM {$this->table_name} WHERE term_taxonomy_id = %d",
                $term_taxonomy_id
            )
        );
    }

    /**
     * Get all term_taxonomy_ids (optionally for a specific taxonomy) followed by a user.
     *
     * @param int $user_id User ID.
     * @param string|null $taxonomy Optional. Taxonomy slug (e.g., 'musicartist', 'musiclabel').
     * @return array Array of term_taxonomy_ids.
     */
    public function get_followed_term_taxonomy_ids_by_user( $user_id, $taxonomy = null ) {
        if ( ! absint( $user_id ) ) {
            return array();
        }

        $sql = "SELECT t_f.term_taxonomy_id
                FROM {$this->table_name} AS t_f";

        if ( $taxonomy ) {
            $sql .= " INNER JOIN {$this->wpdb->term_taxonomy} AS tt ON t_f.term_taxonomy_id = tt.term_taxonomy_id
                      WHERE t_f.user_id = %d AND tt.taxonomy = %s";
            return $this->wpdb->get_col( $this->wpdb->prepare( $sql, $user_id, $taxonomy ) );
        } else {
            $sql .= " WHERE t_f.user_id = %d";
            return $this->wpdb->get_col( $this->wpdb->prepare( $sql, $user_id ) );
        }
    }
}