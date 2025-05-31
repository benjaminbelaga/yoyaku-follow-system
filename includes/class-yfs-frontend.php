<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class YFS_Frontend {
    private $db_handler;

    public function __construct( YFS_DB $db_handler ) {
        $this->db_handler = $db_handler;
    }

    public function init() {
        // Hook to display button on single product pages (meta area)
        add_action( 'woocommerce_product_meta_end', array( $this, 'display_follow_buttons_single_product_meta' ), 20 );

        // Hook to display button on taxonomy archive pages (e.g., below title/description)
        add_action( 'woocommerce_archive_description', array( $this, 'display_follow_button_taxonomy_archive' ), 20 );

        // Hooks for product grid/loop - these need to be specific to your theme's loop structure.
        // Your theme `yoyaku` uses `blocksy:woocommerce:product-card:title:after`.
        // We need to add separate buttons for artist and label if they exist.
        add_action( 'blocksy:woocommerce:product-card:title:after', array( $this, 'display_follow_buttons_product_loop_artist' ), 14 );
        add_action( 'blocksy:woocommerce:product-card:title:after', array( $this, 'display_follow_buttons_product_loop_label' ), 15 );
    }

    /**
     * Generates the HTML for a follow button.
     *
     * @param int    $term_id         Term ID.
     * @param string $taxonomy_slug   Taxonomy slug (e.g., 'musicartist', 'musiclabel').
     * @param bool   $is_page_level   If true, displays a more prominent button with text.
     * @return string HTML for the follow button, or empty string.
     */
    private function get_follow_button_html( $term_id, $taxonomy_slug, $is_page_level = false ) {
        $term = get_term_by( 'id', $term_id, $taxonomy_slug );
        if ( ! $term || is_wp_error( $term ) ) {
            return '';
        }
        $term_taxonomy_id = $term->term_taxonomy_id;
        $term_name = $term->name;

        if ( ! is_user_logged_in() ) {
            $login_url = wp_login_url( get_permalink() ); // Or use yfs_ajax_obj.login_url if button is purely JS rendered
            $button_text_content = $is_page_level ? ' ' . sprintf(esc_html__('Follow %s', 'yoyaku-follow-system'), esc_html($term_name)) : '';
            return sprintf(
                '<a href="%s" class="yfs-follow-button yfs-login-prompt" title="%s">
                    <svg width="16" height="16" class="yfs-icon"><use xlink:href="#%s"></use></svg>%s
                </a>',
                esc_url( $login_url ),
                esc_attr__( 'Login to follow', 'yoyaku-follow-system' ),
                'icon-eye-subscribe', // Default icon_follow from localized object
                $button_text_content
            );
        }

        $user_id = get_current_user_id();
        $is_following = $this->db_handler->is_user_following_term( $user_id, $term_taxonomy_id );

        $button_text_key = $is_following ? 'following' : 'follow';
        $icon_key = $is_following ? 'icon_following' : 'icon_follow'; // Corresponds to localized yfs_ajax_obj
        
        $title_action_key = $is_following ? 'unfollow' : 'follow';
        $title = sprintf(esc_html__('%s %s', 'yoyaku-follow-system'), esc_html(ucfirst( __( $title_action_key, 'yoyaku-follow-system' ) ) ), esc_html($term_name) );

        $button_text_content = '';
        if ( $is_page_level ) {
             $button_text_content = ' <span class="yfs-button-text">' . sprintf(esc_html__('%s %s', 'yoyaku-follow-system'), esc_html(ucfirst( __( $button_text_key, 'yoyaku-follow-system' ) )), esc_html($term_name)) . '</span>';
        } else {
            // For grid icons, text might be omitted or handled by title/tooltip
            // $button_text_content = ' <span class="yfs-button-text screen-reader-text">' . esc_html( __( $button_text_key, 'yoyaku-follow-system' ) ) . '</span>';
        }
        
        $css_classes = "yfs-follow-button";
        if ($is_following) $css_classes .= " is-following";
        if ($is_page_level) $css_classes .= " page-level-subscribe-button";
        if ($taxonomy_slug === YFS_TAX_MUSICARTIST) $css_classes .= " yfs-follow-artist";
        if ($taxonomy_slug === YFS_TAX_MUSICLABEL) $css_classes .= " yfs-follow-label";


        return sprintf(
            '<button type="button" class="%s" data-term-id="%d" data-term-taxonomy-id="%d" data-taxonomy-slug="%s" data-term-name="%s" aria-pressed="%s" title="%s">
                <svg width="%d" height="%d" class="yfs-icon"><use xlink:href="#%s"></use></svg>%s
            </button>',
            esc_attr( $css_classes ),
            esc_attr( $term_id ),
            esc_attr( $term_taxonomy_id ),
            esc_attr( $taxonomy_slug ),
            esc_attr( $term_name ), // Add term name as data attribute
            esc_attr( $is_following ? 'true' : 'false' ),
            esc_attr( $title ),
            esc_attr( $is_page_level ? 20 : 16 ), // Icon size
            esc_attr( $is_page_level ? 20 : 16 ), // Icon size
            esc_attr( $is_following ? 'icon-eye-subscribed' : 'icon-eye-subscribe' ), // Directly use icon ID
            $button_text_content
        );
    }

    /**
     * Display follow buttons for artist and label on single product pages (meta section).
     */
    public function display_follow_buttons_single_product_meta() {
        global $product;
        if ( ! $product || ! is_user_logged_in() ) return; // Only show for logged-in users for now

        $product_id = $product->get_id();

        // Artists
        $artist_terms = wp_get_post_terms( $product_id, YFS_TAX_MUSICARTIST );
        if ( ! empty( $artist_terms ) && ! is_wp_error( $artist_terms ) ) {
            echo '<div class="yfs-product-meta-follow yfs-artist-meta-follow">';
            foreach ( $artist_terms as $term ) {
                // Outputting term name and then button
                echo '<span class="yfs-meta-term-name">' . esc_html__( 'Artist:', 'yoyaku-follow-system' ) . ' ' . esc_html( $term->name ) . '</span>';
                echo $this->get_follow_button_html( $term->term_id, YFS_TAX_MUSICARTIST, false ); // false for smaller icon-only button
                echo '<br>';
            }
            echo '</div>';
        }

        // Labels
        $label_terms = wp_get_post_terms( $product_id, YFS_TAX_MUSICLABEL );
        if ( ! empty( $label_terms ) && ! is_wp_error( $label_terms ) ) {
            echo '<div class="yfs-product-meta-follow yfs-label-meta-follow">';
            foreach ( $label_terms as $term ) {
                 echo '<span class="yfs-meta-term-name">' . esc_html__( 'Label:', 'yoyaku-follow-system' ) . ' ' . esc_html( $term->name ) . '</span>';
                echo $this->get_follow_button_html( $term->term_id, YFS_TAX_MUSICLABEL, false );
                echo '<br>';
            }
            echo '</div>';
        }
    }

    /**
     * Display a main follow button on taxonomy archive pages.
     */
    public function display_follow_button_taxonomy_archive() {
        if ( is_tax( YFS_TAX_MUSICLABEL ) || is_tax( YFS_TAX_MUSICARTIST ) ) {
            $term = get_queried_object();
            if ( $term && isset($term->term_id) && isset($term->taxonomy) ) {
                echo '<div class="yfs-taxonomy-archive-follow">';
                // true for page_level button (larger, with text)
                echo $this->get_follow_button_html( $term->term_id, $term->taxonomy, true );
                echo '</div>';
            }
        }
    }

    /**
     * Display follow buttons for artists in the product loop.
     */
    public function display_follow_buttons_product_loop_artist(){
        global $product;
        if ( ! $product || ! is_user_logged_in() ) return;

        $artist_terms = wp_get_post_terms( $product->get_id(), YFS_TAX_MUSICARTIST );
        if ( ! empty( $artist_terms ) && ! is_wp_error( $artist_terms ) ) {
            // In the loop, usually, we show one artist or a primary one.
            // If multiple, decide how to display. For now, let's take the first one.
            $term = $artist_terms[0];
            // This will appear next to the artist name usually.
            // Your theme's `yoyaku_wc_template_loop_artists` already lists artists.
            // This could be integrated there, or simply add a button next to the list.
            // For simplicity, let's add a small button here. This might need CSS to position correctly.
             // echo $this->get_follow_button_html( $term->term_id, YFS_TAX_MUSICARTIST, false );
            // Instead of echoing here, it's better to modify yoyaku_wc_template_loop_artists
            // in your theme (inc/woocommerce/archive/helpers.php) to include this button logic
            // next to each artist name for better UI.
            // For now, this will add a button after the artist list.
            echo '<span class="yfs-loop-follow-wrapper yfs-loop-artist-follow">';
            foreach($artist_terms as $artist_term) {
                 echo $this->get_follow_button_html( $artist_term->term_id, YFS_TAX_MUSICARTIST, false );
            }
            echo '</span>';
        }
    }

    /**
     * Display follow buttons for labels in the product loop.
     */
    public function display_follow_buttons_product_loop_label(){
        global $product;
        if ( ! $product || ! is_user_logged_in() ) return;

        $label_terms = wp_get_post_terms( $product->get_id(), YFS_TAX_MUSICLABEL );
         if ( ! empty( $label_terms ) && ! is_wp_error( $label_terms ) ) {
            echo '<span class="yfs-loop-follow-wrapper yfs-loop-label-follow">';
            foreach($label_terms as $label_term) {
                 echo $this->get_follow_button_html( $label_term->term_id, YFS_TAX_MUSICLABEL, false );
            }
            echo '</span>';
        }
    }
}