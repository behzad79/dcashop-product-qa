<?php

if (!defined('ABSPATH')) {
    exit;
}

class DCQA_Frontend
{

    public function __construct()
    {
        add_shortcode(
            'dcashop_product_qa',
            [$this, 'shortcode']
        );
    }

    public function shortcode()
    {
        if (!is_product()) {
            return '';
        }

        global $product;

        if (!$product) {
            return '';
        }

        ob_start();

        $product_id = $product->get_id();

        include DCQA_PATH . 'templates/questions.php';

        return ob_get_clean();
    }

}