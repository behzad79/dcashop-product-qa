<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class DCQA_Answer_Admin {


	public function __construct() {


		add_action(
			'admin_menu',
			[ $this, 'menu' ]
		);


		add_action(
			'admin_init',
			[ $this, 'actions' ]
		);


	}



	public function menu() {


		add_submenu_page(

			'dcqa-questions',

			'پاسخ‌های پرسش و پاسخ',

			'پاسخ‌ها',

			'manage_woocommerce',

			'dcqa-answers',

			[ $this, 'page' ]

		);


	}




	public function actions() {


		if (
			! isset($_GET['page'])
			||
			$_GET['page'] !== 'dcqa-answers'
		) {

			return;

		}



		if (
			! isset($_GET['action'])
			||
			! isset($_GET['id'])
		) {

			return;

		}



		global $wpdb;


		$table = $wpdb->prefix . 'product_questions';


		$id = absint($_GET['id']);



		switch($_GET['action']) {



			case 'approve':


				$wpdb->update(

					$table,

					[
						'status' => 'approved',
						'updated_at' => current_time('mysql')
					],

					[
						'id'=>$id
					],

					[
						'%s',
						'%s'
					],

					[
						'%d'
					]

				);


			break;




			case 'delete':


				$answer = $wpdb->get_row(
    $wpdb->prepare(
        "
        SELECT product_id
        FROM {$table}
        WHERE id=%d
        ",
        $id
    )
);

if ( $answer ) {

    $wpdb->delete(
        $table,
        array(
            'id' => $id
        ),
        array(
            '%d'
        )
    );

    dcqa_clear_product_cache( $answer->product_id );
}


			break;


		}



		wp_redirect(

			admin_url(
				'admin.php?page=dcqa-answers'
			)

		);


		exit;


	}





	public function page() {


		global $wpdb;


		$table = $wpdb->prefix . 'product_questions';



		$answers = $wpdb->get_results(

			"
			SELECT *
			FROM {$table}
			WHERE parent_id > 0
			ORDER BY created_at DESC
			"

		);



		?>


		<div class="wrap">


			<h1>
				پاسخ‌های پرسش و پاسخ
			</h1>



			<table class="widefat striped">


				<thead>


					<tr>

						<th>
							محصول
						</th>


						<th>
							نویسنده
						</th>


						<th>
							متن پاسخ
						</th>


						<th>
							وضعیت
						</th>


						<th>
							تاریخ
						</th>


						<th>
							عملیات
						</th>


					</tr>


				</thead>




				<tbody>



				<?php if($answers): ?>



					<?php foreach($answers as $answer): ?>



					<tr>



						<td>


							<a href="<?php echo esc_url(get_edit_post_link($answer->product_id)); ?>">


								<?php echo esc_html(get_the_title($answer->product_id)); ?>


							</a>


						</td>




						<td>


							<?php

							echo esc_html(
								dcqa_get_user_name(
									$answer->user_id
								)
							);

							?>


						</td>




						<td style="min-width:300px;">


							<?php

							echo nl2br(
								esc_html(
									$answer->content
								)
							);

							?>


						</td>




						<td>


							<?php if($answer->status === 'approved'): ?>


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


							<?php

							echo esc_html(
								dcqa_get_date(
									$answer->created_at
								)
							);

							?>


						</td>




						<td>

    <?php if($answer->status !== 'approved'): ?>

        <a
            class="button button-primary"
            href="<?php echo esc_url(
                admin_url(
                    'admin.php?page=dcqa-answers&action=approve&id=' . $answer->id
                )
            ); ?>"
        >

            تایید

        </a>

    <?php endif; ?>


    <a
        class="button"
        href="<?php echo esc_url(
            admin_url(
                'admin.php?page=dcqa-edit-answer&id=' . $answer->id
            )
        ); ?>"
    >

        ویرایش

    </a>


    <a
        class="button button-link-delete"
        onclick="return confirm('حذف شود؟');"
        href="<?php echo esc_url(
            admin_url(
                'admin.php?page=dcqa-answers&action=delete&id=' . $answer->id
            )
        ); ?>"
    >

        حذف

    </a>

</td>



					</tr>




					<?php endforeach; ?>



				<?php else: ?>


					<tr>

						<td colspan="6">

							هنوز پاسخی ثبت نشده است.

						</td>

					</tr>


				<?php endif; ?>



				</tbody>



			</table>



		</div>



		<?php


	}


}