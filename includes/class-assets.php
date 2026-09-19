<?php

if (!defined('ABSPATH')) {
    exit;
}

class DCQA_Assets
{

    public function __construct()
{
    add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
}
    public function admin_assets() {

    wp_enqueue_style(
        'dcqa-admin',
        DCQA_URL . 'assets/css/admin.css',
        [],
        DCQA_VERSION
    );

}

    public function enqueue()
    {

        if (!is_product()) {
            return;
        }

        wp_enqueue_style(
            'dcqa-style',
            DCQA_URL . 'assets/css/style.css',
            [],
            '1.0'
        );

        wp_enqueue_script(
            'dcqa-script',
            DCQA_URL . 'assets/js/main.js',
            ['jquery'],
            '1.0',
            true
        );
        wp_localize_script(
           'dcqa-script',
           'dcqa',
    [

        'logged_in' => is_user_logged_in(),

        'ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),

        'nonce' => wp_create_nonce('dcqa'),

        'product_id' => get_the_ID(),

    ]
);
    }

}