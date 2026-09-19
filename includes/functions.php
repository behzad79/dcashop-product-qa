<?php

if (!defined('ABSPATH')) {
    exit;
}

add_filter('woocommerce_login_redirect', function ($redirect, $user) {

    if (!empty($_REQUEST['redirect_to'])) {
        return esc_url_raw(wp_unslash($_REQUEST['redirect_to']));
    }

    return $redirect;

}, 10, 2);