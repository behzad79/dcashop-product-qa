<?php

/*
Plugin Name: DcaShop Product Q&A
Plugin URI: https://github.com/behzad79/dcqa
Description: A custom product questions and answers plugin for WordPress and WooCommerce.
Version: 1.0.0
Author: Seyyed Behzad Mousaviyan
Author URI: https://github.com/behzad79
*/

if (!defined('ABSPATH')) exit;

define('DCQA_PATH', plugin_dir_path(__FILE__));
define('DCQA_URL', plugin_dir_url(__FILE__));
define( 'DCQA_VERSION', '1.0.0' );

require_once DCQA_PATH.'includes/class-db.php';
require_once DCQA_PATH.'includes/class-assets.php';
require_once DCQA_PATH.'includes/class-frontend.php';
require_once DCQA_PATH.'includes/functions.php';
require_once DCQA_PATH.'includes/class-ajax.php';
require_once DCQA_PATH.'includes/class-admin.php';
require_once DCQA_PATH.'includes/class-answer.php';
require_once DCQA_PATH . 'includes/helpers.php';
require_once DCQA_PATH . 'includes/class-schema.php';


register_activation_hook(__FILE__, ['DCQA_DB','install']);

new DCQA_Assets();
new DCQA_Frontend();
new DCQA_Ajax();
new DCQA_Admin();
new DCQA_Answer();
new DCQA_Schema();

function dcqa_clear_product_cache( $product_id ) {

    $product_id = absint( $product_id );

    if ( ! $product_id ) {
        return;
    }

    // پاک کردن کش WP Rocket برای همین محصول
    if ( function_exists( 'rocket_clean_post' ) ) {
        rocket_clean_post( $product_id );
    }

}

add_action( 'admin_enqueue_scripts', 'dcqa_admin_assets' );

function dcqa_admin_assets( $hook ) {

    if ( ! isset( $_GET['page'] ) ) {
        return;
    }

    if ( $_GET['page'] !== 'dcqa-questions' ) {
        return;
    }

    wp_enqueue_script(
        'dcqa-admin',
        DCQA_URL . 'assets/js/admin.js',
        array( 'jquery' ),
        DCQA_VERSION,
        true
    );

}

