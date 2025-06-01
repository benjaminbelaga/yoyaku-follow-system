<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class YFS_Assets {

    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
    }

    public function enqueue_styles_scripts() {
        // Only enqueue if relevant
        if ( ! is_user_logged_in() && ! ( is_singular('product') || is_tax(YFS_TAX_MUSICLABEL) || is_tax(YFS_TAX_MUSICARTIST) ) ) {
            // Even if not logged in, we might want to show a login prompt, so styles/scripts might still be needed if buttons are visible to logged-out users.
            // For now, let's assume buttons are only functional (and perhaps visible) for logged-in users.
            // If you intend to show disabled buttons or login prompts to logged-out users, adjust this condition.
        }

        // Always enqueue if any of the conditions for showing buttons are met, or if user is logged in (for My Account page later)
        // For now, let's be broad and refine later if needed for performance.
        // The check `is_user_logged_in()` inside yfs-script.js will handle behavior.

        wp_enqueue_style(
            'yfs-style',
            YFS_PLUGIN_URL . 'assets/css/yfs-style.css',
            array(),
            YFS_VERSION
        );

        wp_enqueue_script(
            'yfs-script',
            YFS_PLUGIN_URL . 'assets/js/yfs-script.js',
            array( 'jquery' ),
            YFS_VERSION,
            true // In footer
        );

        $login_url = wp_login_url( get_permalink() ); // Default redirect to current page
        if ( class_exists('WooCommerce') && is_account_page() ) {
             $login_url = wc_get_page_permalink('myaccount'); // Or specific login page if set up
        }


        wp_localize_script(
            'yfs-script',
            'yfs_ajax_obj',
            array(
                'ajax_url'   => admin_url( 'admin-ajax.php' ),
                'nonce'      => wp_create_nonce( 'yfs_follow_nonce' ),
                'logged_in'  => is_user_logged_in(),
                'login_url'  => $login_url,
                'i18n'       => array(
                    'follow'          => __( 'Follow', 'yoyaku-follow-system' ),
                    'unfollow'        => __( 'Unfollow', 'yoyaku-follow-system' ),
                    'following'       => __( 'Following', 'yoyaku-follow-system' ),
                    'not_following'   => __( 'Not Following', 'yoyaku-follow-system' ), // Might not be used directly
                    'error'           => __( 'An error occurred. Please try again.', 'yoyaku-follow-system' ),
                    'login_prompt'    => __('Please login to follow.', 'yoyaku-follow-system'),
                    'loading'         => __('Loading...', 'yoyaku-follow-system'),
                ),
                // Assuming SVG sprites are in your theme: yoyaku/inc/template-actions.php
                // The JS will toggle class or xlink:href attribute.
                'icon_follow'    => 'icon-eye-subscribe', // xlink:href value for the follow icon
                'icon_following' => 'icon-eye-subscribed', // xlink:href value for the following icon
                'icon_spinner'   => 'icon-spinner-yfs' // Example, define this spinner in your theme's SVG sprite
            )
        );
    }
}
