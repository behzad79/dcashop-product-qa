<?php

if (!defined('ABSPATH')) {
    exit;
}


class DCQA_Ajax
{


    public function __construct()
    {

        add_action(
            'wc_ajax_dcqa_submit_question',
            [$this,'submit_question']
        );

        add_action(
            'wc_ajax_dcqa_submit_answer',
            [$this,'submit_answer']
        );

        add_action(
            'wc_ajax_nopriv_dcqa_submit_answer',
            [$this,'submit_answer']
        );

    }



    public function submit_question()
    {


        check_ajax_referer(
            'dcqa',
            'nonce'
        );


        if (!is_user_logged_in()) {


            wp_send_json_error(
                [
                    'message'=>'ابتدا وارد حساب شوید'
                ]
            );


        }


        $content = isset($_POST['content'])
            ? sanitize_textarea_field($_POST['content'])
            : '';



        $product_id = isset($_POST['product_id'])
            ? intval($_POST['product_id'])
            : 0;



        if(empty($content)){


            wp_send_json_error(
                [
                    'message'=>'متن پرسش خالی است'
                ]
            );


        }



        global $wpdb;


        $table = $wpdb->prefix.'product_questions';



        /*
تعیین وضعیت پرسش
مدیر سایت مستقیم تایید شود
کاربر عادی نیاز به تایید دارد
*/

$status = 'pending';
$is_admin = false;

$current_user = wp_get_current_user();

if (
    in_array( 'administrator', $current_user->roles, true ) ||
    in_array( 'shop_manager', $current_user->roles, true ) ||
    in_array( 'editor', $current_user->roles, true )
) {

    $status = 'approved';

}


$wpdb->insert(

    $table,

    [

        'product_id' => $product_id,

        'parent_id' => 0,

        'user_id' => get_current_user_id(),

        'content' => $content,

        'status' => $status,

        'created_at' => current_time( 'mysql' )

    ],

    [

        '%d',
        '%d',
        '%d',
        '%s',
        '%s',
        '%s'

    ]

);


/*
پاک کردن کش محصول
*/

if ( function_exists( 'dcqa_clear_product_cache' ) ) {

    dcqa_clear_product_cache( $product_id );

}


/*
پیام مناسب
*/

if ( $status === 'approved' ) {

    wp_send_json_success(

        [
            'message' => 'پرسش شما ثبت شد.',
            'reload'  => true
        ]

    );

} else {

    wp_send_json_success(

        [
            'message' => 'پرسش شما ثبت شد و پس از بررسی نمایش داده می‌شود.',
            'reload'  => false
        ]

    );

}

    }




    public function submit_answer(){


        check_ajax_referer(
            'dcqa',
            'nonce'
        );


        if(!is_user_logged_in()){


            wp_send_json_error([

                'message'=>'ابتدا وارد حساب کاربری شوید.'

            ]);


        }



        global $wpdb;


        $table=$wpdb->prefix.'product_questions';



        $question_id=absint($_POST['question_id']);



        $content=wp_strip_all_tags(

            wp_unslash($_POST['content'])

        );



        if(empty($content)){


            wp_send_json_error([

                'message'=>'پاسخ را وارد کنید.'

            ]);


        }




        $product_id=$wpdb->get_var(

            $wpdb->prepare(

                "SELECT product_id
                FROM {$table}
                WHERE id=%d",

                $question_id

            )

        );




        /*
        تعیین وضعیت پاسخ
        مدیر سایت مستقیم تایید شود
        کاربر عادی نیاز به تایید دارد
        */


        $status = 'pending';
        $is_admin = false;


        $current_user = wp_get_current_user();


        if(
            in_array('administrator',$current_user->roles,true) ||
            in_array('shop_manager',$current_user->roles,true) ||
            in_array('editor',$current_user->roles,true)
        ){

            $status = 'approved';

        }




        $wpdb->insert(

    $table,

    [

        'product_id' => $product_id,

        'parent_id' => $question_id,

        'user_id' => get_current_user_id(),

        'content' => $content,

        'status' => $status,

        'created_at' => current_time( 'mysql' )

    ],

    [

        '%d',
        '%d',
        '%d',
        '%s',
        '%s',
        '%s'

    ]

);


/*
پاک کردن کش محصول
*/

if ( function_exists( 'dcqa_clear_product_cache' ) ) {

    dcqa_clear_product_cache( $product_id );

}



        if($status === 'approved'){


            wp_send_json_success(
        [
            'message' => 'پاسخ شما ثبت شد.',
            'reload'  => true
        ]
            );


        }else{


            wp_send_json_success(
        [
            'message' => 'پاسخ شما ثبت شد و پس از تایید نمایش داده می‌شود.',
            'reload'  => false
        ]
            );


        }



    }


}