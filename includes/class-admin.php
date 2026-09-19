<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DCQA_Admin {

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'actions' ) );
		add_action( 'admin_menu', array( $this, 'register_edit_page' ) );
		add_action( 'admin_init', array($this, 'save_edit'));
		add_action( 'admin_menu', array( $this, 'menu_pending_count' ));
        add_action( 'wp_dashboard_setup', array( $this, 'dashboard_pending_widget'));
        add_action(    'wp_ajax_dcqa_admin_reply',  array( $this, 'ajax_admin_reply' ));

	}

	public function menu() {

		add_menu_page(
			'پرسش و پاسخ محصولات',
			'پرسش و پاسخ',
			'manage_woocommerce',
			'dcqa-questions',
			array( $this, 'page' ),
			'dashicons-format-chat',
			56
		);

	}
	
	
	/*
|--------------------------------------------------------------------------
| تعداد پرسش و پاسخ‌های در انتظار تایید
|--------------------------------------------------------------------------
*/

public function get_pending_count() {

    global $wpdb;

    $table = $wpdb->prefix . 'product_questions';

    return (int) $wpdb->get_var(
        "
        SELECT COUNT(*)
        FROM {$table}
        WHERE status = 'pending'
        "
    );

}


/*
|--------------------------------------------------------------------------
| نمایش تعداد در منوی پرسش و پاسخ
|--------------------------------------------------------------------------
*/

public function menu_pending_count() {

    global $menu;

    $count = $this->get_pending_count();

    if ( ! $count ) {
        return;
    }

    foreach ( $menu as $key => $item ) {

        if (
            isset( $item[2] )
            &&
            $item[2] === 'dcqa-questions'
        ) {

            $menu[$key][0] .=
                ' <span class="awaiting-mod count-' .
                esc_attr( $count ) .
                '"><span class="pending-count">' .
                esc_html( $count ) .
                '</span></span>';

            break;

        }

    }

}


/*
|--------------------------------------------------------------------------
| باکس در پیشخوان وردپرس
|--------------------------------------------------------------------------
*/

public function dashboard_pending_widget() {

    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    $count = $this->get_pending_count();

    wp_add_dashboard_widget(

        'dcqa_pending_widget',

        'پرسش و پاسخ در انتظار بررسی',

        function() use ( $count ) {

            if ( $count > 0 ) {

                echo '<p style="font-size:16px;">';

                echo 'تعداد ';

                echo '<strong style="color:#d63638;font-size:22px;">';

                echo esc_html( $count );

                echo '</strong>';

                echo ' پرسش یا پاسخ در انتظار بررسی است.';

                echo '</p>';

                echo '<p>';

                echo '<a class="button button-primary" href="' .
                    esc_url(
                        admin_url(
                            'admin.php?page=dcqa-questions'
                        )
                    ) .
                    '">';

                echo 'مشاهده پرسش‌ها';

                echo '</a>';

                echo '</p>';

            } else {

                echo '<p>';

                echo 'در حال حاضر پرسش یا پاسخ جدیدی در انتظار بررسی نیست.';

                echo '</p>';

            }

        }

    );

}

public function actions() {

    if (
        ! isset( $_GET['page'] ) ||
        $_GET['page'] !== 'dcqa-questions'
    ) {
        return;
    }

    if (
        ! isset( $_GET['action'] ) ||
        ! isset( $_GET['id'] )
    ) {
        return;
    }

    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    global $wpdb;

    $table = $wpdb->prefix . 'product_questions';

    $id = absint( $_GET['id'] );

    /*
    |--------------------------------------------------------------------------
    | اطلاعات آیتم
    |--------------------------------------------------------------------------
    */

    $item = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT *
            FROM {$table}
            WHERE id=%d
            ",
            $id
        )
    );

    if ( ! $item ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | تایید
    |--------------------------------------------------------------------------
    */

    if ( $_GET['action'] === 'approve' ) {

        $result = $wpdb->update(

            $table,

            array(

                'status'     => 'approved',

                'updated_at' => current_time( 'mysql' ),

            ),

            array(

                'id' => $id,

            ),

            array(

                '%s',

                '%s',

            ),

            array(

                '%d',

            )

        );


        /*
        |--------------------------------------------------------------
        | پاک کردن کش محصول
        |--------------------------------------------------------------
        */

        if ( $result !== false ) {

            dcqa_clear_product_cache(
                (int) $item->product_id
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | حذف
    |--------------------------------------------------------------------------
    */

    elseif ( $_GET['action'] === 'delete' ) {


        /*
        |--------------------------------------------------------------
        | حذف تمام پاسخ‌های این پرسش
        |--------------------------------------------------------------
        */

        $wpdb->delete(

            $table,

            array(

                'parent_id' => $id,

            ),

            array(

                '%d',

            )

        );


        /*
        |--------------------------------------------------------------
        | حذف خود پرسش / پاسخ
        |--------------------------------------------------------------
        */

        $wpdb->delete(

            $table,

            array(

                'id' => $id,

            ),

            array(

                '%d',

            )

        );


        /*
        |--------------------------------------------------------------
        | پاک کردن کش محصول
        |--------------------------------------------------------------
        */

        dcqa_clear_product_cache(
            (int) $item->product_id
        );

    }


    /*
    |--------------------------------------------------------------------------
    | بازگشت
    |--------------------------------------------------------------------------
    */

    wp_safe_redirect(

        admin_url(
            'admin.php?page=dcqa-questions'
        )

    );

    exit;

}


public function pagination( $current_page, $total_pages, $total_questions ) {

    if ( $total_pages <= 1 ) {
        return;
    }

    ?>

    <div class="tablenav-pages">

        <span class="displaying-num">

            <?php echo esc_html( $total_questions ); ?> مورد

        </span>

        <span class="pagination-links">

            <?php

            // صفحه اول
            if ( $current_page <= 1 ) :

                ?>

                <span
                    class="tablenav-pages-navspan button disabled"
                    aria-hidden="true"
                >
                    «
                </span>

                <span
                    class="tablenav-pages-navspan button disabled"
                    aria-hidden="true"
                >
                    ‹
                </span>

            <?php else : ?>

                <a
                    class="first-page button"
                    href="<?php echo esc_url(
                        add_query_arg(
                            'paged',
                            1
                        )
                    ); ?>"
                >

                    <span class="screen-reader-text">
                        برگه اول
                    </span>

                    <span aria-hidden="true">
                        «
                    </span>

                </a>

                <a
                    class="prev-page button"
                    href="<?php echo esc_url(
                        add_query_arg(
                            'paged',
                            $current_page - 1
                        )
                    ); ?>"
                >

                    <span class="screen-reader-text">
                        برگه قبلی
                    </span>

                    <span aria-hidden="true">
                        ‹
                    </span>

                </a>

            <?php endif; ?>


            <span class="paging-input">

                <label
                    for="current-page-selector"
                    class="screen-reader-text"
                >
                    برگهٔ فعلی
                </label>

                <input
                    class="current-page"
                    id="current-page-selector"
                    type="text"
                    name="paged"
                    value="<?php echo esc_attr( $current_page ); ?>"
                    size="2"
                    aria-describedby="table-paging"
                >

                <span class="tablenav-paging-text">

                    از

                    <span class="total-pages">
                        <?php echo esc_html( $total_pages ); ?>
                    </span>

                </span>

            </span>


            <?php

            // صفحه بعد
            if ( $current_page >= $total_pages ) :

                ?>

                <span
                    class="tablenav-pages-navspan button disabled"
                    aria-hidden="true"
                >
                    ›
                </span>

                <span
                    class="tablenav-pages-navspan button disabled"
                    aria-hidden="true"
                >
                    »
                </span>

            <?php else : ?>

                <a
                    class="next-page button"
                    href="<?php echo esc_url(
                        add_query_arg(
                            'paged',
                            $current_page + 1
                        )
                    ); ?>"
                >

                    <span class="screen-reader-text">
                        برگه بعدی
                    </span>

                    <span aria-hidden="true">
                        ›
                    </span>

                </a>

                <a
                    class="last-page button"
                    href="<?php echo esc_url(
                        add_query_arg(
                            'paged',
                            $total_pages
                        )
                    ); ?>"
                >

                    <span class="screen-reader-text">
                        برگه آخر
                    </span>

                    <span aria-hidden="true">
                        »
                    </span>

                </a>

            <?php endif; ?>

        </span>

    </div>

    <?php
}

	public function page() {

		global $wpdb;

		$table = $wpdb->prefix . 'product_questions';

// صفحه‌بندی
$per_page = 10;

$current_page = isset( $_GET['paged'] )
    ? max( 1, absint( $_GET['paged'] ) )
    : 1;

$offset = ( $current_page - 1 ) * $per_page;


// تعداد کل پرسش‌های اصلی
$total_questions = (int) $wpdb->get_var(
    "
    SELECT COUNT(*)
    FROM {$table}
    WHERE parent_id = 0
    "
);


// دریافت پرسش‌های همین صفحه
$questions = $wpdb->get_results(
    $wpdb->prepare(
        "
        SELECT *
        FROM {$table}
        WHERE parent_id = 0
        ORDER BY created_at DESC
        LIMIT %d OFFSET %d
        ",
        $per_page,
        $offset
    )
);


// تعداد صفحات
$total_pages = ceil( $total_questions / $per_page );

		?>

		<div class="wrap">

			<h1>پرسش و پاسخ محصولات</h1>
			
			<div class="tablenav top">

    <?php
    $this->pagination(
        $current_page,
        $total_pages,
        $total_questions
    );
    ?>

</div>

			<table class="widefat striped">

				<thead>

					<tr>

						<th width="22%">محصول</th>

						<th width="12%">نویسنده</th>

						<th>متن</th>

						<th width="10%">وضعیت</th>

						<th width="8%">پاسخ‌ها</th>

						<th width="13%">تاریخ</th>

						<th width="18%">عملیات</th>

					</tr>

				</thead>

				<tbody>

				<?php if ( $questions ) : ?>

					<?php foreach ( $questions as $q ) : ?>

						<?php

				    	$user_name = ! empty( $q->display_name )
    ? $q->display_name
    : dcqa_get_user_name( $q->user_id );

						$answers_count = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT COUNT(*)
								FROM {$table}
								WHERE parent_id=%d",
								$q->id
							)
						);
						$answers = $wpdb->get_results(
                            $wpdb->prepare(
                               "SELECT *
                                FROM {$table}
                                WHERE parent_id=%d
                                ORDER BY created_at ASC",
                                $q->id
                            )
                        );

						?>

						<tr>

							<td>

								<a href="<?php echo esc_url( get_edit_post_link( $q->product_id ) ); ?>">

									<?php echo esc_html( get_the_title( $q->product_id ) ); ?>

								</a>

							</td>

							<td>

								<?php echo esc_html( $user_name ); ?>

							</td>

							<td style="min-width:350px;">

    <div class="dcqa-admin-question-text">

        <?php

        echo nl2br(
            esc_html($q->content)
        );

        ?>

    </div>


    <?php if ( $answers ) : ?>


        <div class="dcqa-admin-answers">


            <strong>
                پاسخ‌ها:
            </strong>



            <?php foreach ( $answers as $answer ) : ?>


                <div class="dcqa-admin-answer">


                    <p>

                        <strong>

                            <?php

                            echo esc_html(
    ! empty( $answer->display_name )
        ? $answer->display_name
        : dcqa_get_user_name( $answer->user_id )
);

                            ?>

                        </strong>

                        :

                        <?php

                        echo nl2br(
                            esc_html(
                                $answer->content
                            )
                        );

                        ?>

                    </p>


                    <small>

                        وضعیت:

                        <?php

                        if ( $answer->status === 'approved' ) {

                            echo ' تایید شده ';

                        } else {

                            echo ' در انتظار تایید ';

                        }

                        ?>

                    </small>
                    
                    <div class="dcqa-answer-actions">


<?php if ( $answer->status !== 'approved' ) : ?>


<a class="button button-primary"
href="<?php echo esc_url(
    admin_url(
        'admin.php?page=dcqa-questions&action=approve&id=' . $answer->id
    )
); ?>">

    تایید

</a>




<?php endif; ?>


<a class="button button-link-delete"
onclick="return confirm('آیا از حذف این پاسخ مطمئن هستید؟');"
href="<?php echo esc_url(
    admin_url(
        'admin.php?page=dcqa-questions&action=delete&id=' . $answer->id
    )
); ?>">

    حذف

</a>

<a class="button"
href="<?php echo esc_url(
    admin_url(
        'admin.php?page=dcqa-edit&id=' . $answer->id
    )
); ?>">

ویرایش

</a>

</div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</td>

							<td>

								<?php
								
								if($q->status=='approved'): ?>

                                <span class="dcqa-status approved">

                                تایید شده

                               </span>

                               <?php else: ?>

                               <span class="dcqa-status pending">

                                در انتظار تایید

                               </span>

                            <?php endif; ?>

							</td>

							<td>

								<?php echo intval( $answers_count ); ?>

							</td>

							<td>

								<?php echo esc_html( dcqa_get_date($q->created_at) ); ?>

							</td>

	<td>

    <button
        type="button"
        class="button dcqa-admin-reply-btn"
        data-question="<?php echo esc_attr( $q->id ); ?>"
    >
        پاسخ
    </button>

    <?php if ( $q->status === 'pending' ) : ?>

        <a class="button button-primary"
            href="<?php echo esc_url(
                admin_url(
                    'admin.php?page=dcqa-questions&action=approve&id=' . $q->id
                )
            ); ?>">

            تایید

        </a>

    <?php endif; ?>


								<a class="button"
									href="<?php echo esc_url( admin_url( 'admin.php?page=dcqa-edit&id=' . $q->id ) ); ?>">

									ویرایش

								</a>


								<a class="button button-link-delete"
									onclick="return confirm('آیا از حذف این پرسش مطمئن هستید؟');"
									href="<?php echo esc_url( admin_url( 'admin.php?page=dcqa-questions&action=delete&id=' . $q->id ) ); ?>">

									حذف

								</a>

							</td>

						</tr>
						
						<tr
    class="dcqa-admin-reply-row"
    id="dcqa-reply-<?php echo esc_attr( $q->id ); ?>"
    style="display:none;"
>
    <td colspan="8">

        <div class="dcqa-admin-reply-box">

            <strong>
                پاسخ به این پرسش
            </strong>
            
            <input
               type="hidden"
               class="dcqa-admin-reply-nonce"
               value="<?php echo esc_attr( wp_create_nonce( 'dcqa_admin_reply' ) ); ?>"
>

            <textarea
                class="dcqa-admin-reply-content"
                rows="5"
                style="width:100%; margin-top:10px;"
                placeholder="پاسخ خود را بنویسید..."
            ></textarea>

            <div style="margin-top:10px;">

                <button
                    type="button"
                    class="button button-primary dcqa-admin-reply-submit"
                    data-question="<?php echo esc_attr( $q->id ); ?>"
                >
                    پاسخ
                </button>

                <button
                    type="button"
                    class="button dcqa-admin-reply-cancel"
                >
                    لغو
                </button>

                <span
                    class="dcqa-admin-reply-message"
                    style="margin-right:10px;"
                ></span>

            </div>

        </div>

    </td>
</tr>

					<?php endforeach; ?>

				<?php else : ?>

					<tr>

						<td colspan="8">

							هنوز هیچ پرسشی ثبت نشده است.

						</td>

					</tr>

				<?php endif; ?>

				</tbody>

			</table>
			
<div class="tablenav bottom">

    <?php
    $this->pagination(
        $current_page,
        $total_pages,
        $total_questions
    );
    ?>

</div>

		</div>

		<?php

	}
	public function register_edit_page(){

    add_submenu_page(

        null,

        'ویرایش پرسش و پاسخ',

        'ویرایش',

        'manage_woocommerce',

        'dcqa-edit',

        array($this,'edit_page')

    );

}
public function edit_page(){

    global $wpdb;

    $table = $wpdb->prefix . 'product_questions';

    $id = isset($_GET['id'])
        ? absint($_GET['id'])
        : 0;

    if(!$id){
        return;
    }

    $item = $wpdb->get_row(

        $wpdb->prepare(

            "SELECT *
            FROM {$table}
            WHERE id=%d",

            $id

        )

    );

    if(!$item){
        return;
    }

    ?>

    <div class="wrap">

        <h1>
            ویرایش پرسش و پاسخ
        </h1>

        <form method="post">

            <?php wp_nonce_field('dcqa_edit','dcqa_edit_nonce'); ?>
            
            
            
            <!-- نام نمایش‌دهنده -->

<label for="dcqa_display_name">

    <strong>
        نام نمایش‌دهنده
    </strong>

</label>

<br><br>

<input
    type="text"
    id="dcqa_display_name"
    name="dcqa_display_name"
    value="<?php echo esc_attr( $item->display_name ); ?>"
    style="width:600px;"
    placeholder="مثلاً دکاشاپ"
>

<p class="description">

    اگر خالی باشد، نام کاربر طبق تنظیمات فعلی نمایش داده می‌شود.

</p>

<br><br>


            <!-- متن -->

            <textarea
                name="dcqa_content"
                style="width:600px;height:200px;"
            ><?php echo esc_textarea($item->content); ?></textarea>


            <br><br>


            <!-- تاریخ -->

            <label for="dcqa_created_at">

                <strong>
                    تاریخ و ساعت
                </strong>

            </label>

            <br><br>

            <input
                type="datetime-local"
                id="dcqa_created_at"
                name="dcqa_created_at"
                value="<?php

                    echo esc_attr(

                        date(
                            'Y-m-d\TH:i',
                            strtotime($item->created_at)
                        )

                    );

                ?>"
                style="width:250px;"
            >

            <p class="description">

                تاریخ و ساعت ثبت این پرسش یا پاسخ را تغییر دهید.

            </p>


            <br><br>


            <button
                class="button button-primary"
                name="dcqa_save_edit"
                type="submit"
            >

                ذخیره تغییرات

            </button>

        </form>

    </div>

    <?php

}

public function save_edit() {

    if ( ! isset( $_POST['dcqa_save_edit'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    if (
        ! isset( $_POST['dcqa_edit_nonce'] ) ||
        ! wp_verify_nonce(
            $_POST['dcqa_edit_nonce'],
            'dcqa_edit'
        )
    ) {
        return;
    }

    global $wpdb;

    $table = $wpdb->prefix . 'product_questions';

    $id = isset( $_GET['id'] )
        ? absint( $_GET['id'] )
        : 0;

    if ( ! $id ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | گرفتن اطلاعات فعلی
    |--------------------------------------------------------------------------
    */

    $item = $wpdb->get_row(

        $wpdb->prepare(

            "
            SELECT *
            FROM {$table}
            WHERE id=%d
            ",

            $id

        )

    );

    if ( ! $item ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | متن
    |--------------------------------------------------------------------------
    */

    $content = isset( $_POST['dcqa_content'] )

        ? sanitize_textarea_field(
            wp_unslash(
                $_POST['dcqa_content']
            )
        )

        : '';
        
        
    /*
|--------------------------------------------------------------------------
| نام نمایش‌دهنده
|--------------------------------------------------------------------------
*/

$display_name = isset( $_POST['dcqa_display_name'] )

    ? sanitize_text_field(
        wp_unslash(
            $_POST['dcqa_display_name']
        )
    )

    : '';


    /*
    |--------------------------------------------------------------------------
    | تاریخ
    |--------------------------------------------------------------------------
    */

    $created_at = isset( $_POST['dcqa_created_at'] )

        ? sanitize_text_field(
            wp_unslash(
                $_POST['dcqa_created_at']
            )
        )

        : '';


    if ( $created_at ) {

        $created_at = str_replace(
            'T',
            ' ',
            $created_at
        );

        $timestamp = strtotime( $created_at );

        if ( $timestamp ) {

            $created_at = date(
                'Y-m-d H:i:s',
                $timestamp
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | ذخیره تغییرات
    |--------------------------------------------------------------------------
    */

    $result = $wpdb->update(

    $table,

    array(

        'display_name' => $display_name,

        'content'      => $content,

        'created_at'   => $created_at,

        'updated_at'   => current_time( 'mysql' ),

    ),

    array(

        'id' => $id,

    ),

    array(

        '%s',

        '%s',

        '%s',

        '%s',

    ),

    array(

        '%d',

    )

);


    /*
    |--------------------------------------------------------------------------
    | پاک کردن کش محصول
    |--------------------------------------------------------------------------
    */

    if ( $result !== false ) {

        dcqa_clear_product_cache(
            (int) $item->product_id
        );

    }


    /*
    |--------------------------------------------------------------------------
    | برگشت به لیست
    |--------------------------------------------------------------------------
    */

    wp_safe_redirect(

        admin_url(
            'admin.php?page=dcqa-questions'
        )

    );

    exit;

}

public function ajax_admin_reply() {

    /*
    |--------------------------------------------------------------------------
    | بررسی دسترسی
    |--------------------------------------------------------------------------
    */

    if ( ! current_user_can( 'manage_woocommerce' ) ) {

        wp_send_json_error(
            array(
                'message' => 'شما اجازه انجام این کار را ندارید.'
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | بررسی nonce
    |--------------------------------------------------------------------------
    */

    if (
        ! isset( $_POST['nonce'] ) ||
        ! wp_verify_nonce(
            $_POST['nonce'],
            'dcqa_admin_reply'
        )
    ) {

        wp_send_json_error(
            array(
                'message' => 'درخواست نامعتبر است.'
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | شناسه پرسش
    |--------------------------------------------------------------------------
    */

    $question_id = isset( $_POST['question_id'] )
        ? absint( $_POST['question_id'] )
        : 0;


    if ( ! $question_id ) {

        wp_send_json_error(
            array(
                'message' => 'شناسه پرسش نامعتبر است.'
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | متن پاسخ
    |--------------------------------------------------------------------------
    */

    $content = isset( $_POST['content'] )
        ? sanitize_textarea_field(
            wp_unslash(
                $_POST['content']
            )
        )
        : '';


    if ( empty( $content ) ) {

        wp_send_json_error(
            array(
                'message' => 'متن پاسخ خالی است.'
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | گرفتن پرسش
    |--------------------------------------------------------------------------
    */

    global $wpdb;

    $table = $wpdb->prefix . 'product_questions';


    $question = $wpdb->get_row(

        $wpdb->prepare(

            "
            SELECT *
            FROM {$table}
            WHERE id=%d
            AND parent_id=0
            ",

            $question_id

        )

    );


    if ( ! $question ) {

        wp_send_json_error(
            array(
                'message' => 'پرسش پیدا نشد.'
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ثبت پاسخ ادمین
    |--------------------------------------------------------------------------
    */

    $result = $wpdb->insert(

        $table,

        array(

            'product_id' => $question->product_id,

            'parent_id' => $question_id,

            'user_id' => get_current_user_id(),

            'content' => $content,

            'status' => 'approved',

            'created_at' => current_time( 'mysql' ),

        ),

        array(

            '%d',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',

        )

    );


    if ( $result === false ) {

        wp_send_json_error(
            array(
                'message' => 'ثبت پاسخ انجام نشد.'
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | شناسه پاسخ جدید
    |--------------------------------------------------------------------------
    */

    $answer_id = $wpdb->insert_id;


    /*
    |--------------------------------------------------------------------------
    | پاک کردن کش محصول
    |--------------------------------------------------------------------------
    */

    dcqa_clear_product_cache(
        (int) $question->product_id
    );


    /*
    |--------------------------------------------------------------------------
    | نام نویسنده
    |--------------------------------------------------------------------------
    */

    $user_name = dcqa_get_user_name(
	$q->user_id,
	$q->display_name
     );


    /*
    |--------------------------------------------------------------------------
    | پاسخ HTML برای نمایش فوری
    |--------------------------------------------------------------------------
    */

    ob_start();

    ?>

    <div
        class="dcqa-admin-answer"
        data-answer-id="<?php echo esc_attr( $answer_id ); ?>"
    >

        <p>

            <strong>

                <?php echo esc_html( $user_name ); ?>

            </strong>

            :

            <?php

            echo nl2br(
                esc_html( $content )
            );

            ?>

        </p>


        <small>

            وضعیت:

            تایید شده

        </small>

    </div>

    <?php

    $html = ob_get_clean();


    /*
    |--------------------------------------------------------------------------
    | پاسخ موفق
    |--------------------------------------------------------------------------
    */

    wp_send_json_success(

        array(

            'message' => 'پاسخ با موفقیت ثبت شد.',

            'html' => $html,

            'answer_id' => $answer_id,

        )

    );

}

}