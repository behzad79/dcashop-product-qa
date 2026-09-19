<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DCQA_Answer {

public function __construct() {

    add_action(
        'admin_menu',
        array( $this, 'menu' )
    );

    add_action(
        'admin_menu',
        array( $this, 'register_edit_page' )
    );

    add_action(
        'admin_init',
        array( $this, 'save' )
    );

}


public function register_edit_page() {

    add_submenu_page(

        null,

        'ویرایش پاسخ',

        'ویرایش پاسخ',

        'manage_woocommerce',

        'dcqa-answer-edit',

        array( $this, 'edit_page' )

    );

}
	/*
	|--------------------------------------------------------------------------
	| منوی پاسخ
	|--------------------------------------------------------------------------
	*/

	public function menu() {

		/*
		 * صفحه پاسخ جدید
		 */
		add_submenu_page(
			null,
			'پاسخ به پرسش',
			'پاسخ به پرسش',
			'manage_woocommerce',
			'dcqa-answer',
			array( $this, 'page' )
		);

		/*
		 * صفحه ویرایش پاسخ
		 */
		add_submenu_page(
			null,
			'ویرایش پاسخ',
			'ویرایش پاسخ',
			'manage_woocommerce',
			'dcqa-edit-answer',
			array( $this, 'edit_page' )
		);

	}

	/*
	|--------------------------------------------------------------------------
	| صفحه ثبت پاسخ جدید
	|--------------------------------------------------------------------------
	*/

	public function page() {

		global $wpdb;

		$table = $wpdb->prefix . 'product_questions';

		$parent_id = isset( $_GET['id'] )
			? absint( $_GET['id'] )
			: 0;

		$question = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT *
				FROM {$table}
				WHERE id=%d
				",
				$parent_id
			)
		);

		if ( ! $question ) {

			wp_die( 'پرسش پیدا نشد.' );

		}

		?>

		<div class="wrap">

			<h1>
				پاسخ به پرسش
			</h1>

			<h3>

				<?php echo esc_html( $question->content ); ?>

			</h3>

			<form method="post">

				<?php wp_nonce_field( 'dcqa_answer' ); ?>

				<textarea
					name="answer"
					rows="8"
					style="width:100%;"
					placeholder="پاسخ دکاشاپ"
				></textarea>

				<br><br>

				<button
					class="button button-primary"
					name="dcqa_save_answer"
					type="submit"
				>

					ثبت پاسخ

				</button>

			</form>

		</div>

		<?php

	}

	/*
	|--------------------------------------------------------------------------
	| صفحه ویرایش پاسخ
	|--------------------------------------------------------------------------
	*/

	public function edit_page() {

		global $wpdb;

		$table = $wpdb->prefix . 'product_questions';

		$id = isset( $_GET['id'] )
			? absint( $_GET['id'] )
			: 0;

		if ( ! $id ) {

			wp_die( 'شناسه پاسخ نامعتبر است.' );

		}

		$answer = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT *
				FROM {$table}
				WHERE id=%d
				AND parent_id!=0
				",
				$id
			)
		);

		if ( ! $answer ) {

			wp_die( 'پاسخ پیدا نشد.' );

		}

		?>

		<div class="wrap">

			<h1>
				ویرایش پاسخ
			</h1>

			<table class="form-table">

				<tr>

					<th>
						پرسش
					</th>

					<td>

						<?php

						$question = $wpdb->get_row(
							$wpdb->prepare(
								"
								SELECT *
								FROM {$table}
								WHERE id=%d
								",
								$answer->parent_id
							)
						);

						echo esc_html(
							$question
								? $question->content
								: ''
						);

						?>

					</td>

				</tr>

			</table>

			<form method="post">

				<?php wp_nonce_field( 'dcqa_edit_answer' ); ?>

				<table class="form-table">

					<tr>

						<th>
							متن پاسخ
						</th>

						<td>

							<textarea
								name="content"
								rows="8"
								style="width:100%;"
							><?php echo esc_textarea( $answer->content ); ?></textarea>

						</td>

					</tr>

					<tr>

						<th>
							تاریخ
						</th>

						<td>

							<input
								type="datetime-local"
								name="created_at"
								value="<?php echo esc_attr(
									date(
										'Y-m-d\TH:i',
										strtotime( $answer->created_at )
									)
								); ?>"
							>

							<p class="description">

								تاریخ و ساعت پاسخ را می‌توانید تغییر دهید.

							</p>

						</td>

					</tr>

				</table>

				<button
					class="button button-primary"
					name="dcqa_update_answer"
					type="submit"
				>

					ذخیره تغییرات

				</button>

			</form>

		</div>

		<?php

	}

	/*
	|--------------------------------------------------------------------------
	| ذخیره پاسخ جدید و ویرایش پاسخ
	|--------------------------------------------------------------------------
	*/

	public function save() {

		/*
		|--------------------------------------------------------------------------
		| ثبت پاسخ جدید
		|--------------------------------------------------------------------------
		*/

		if ( isset( $_POST['dcqa_save_answer'] ) ) {

			check_admin_referer( 'dcqa_answer' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			global $wpdb;

			$table = $wpdb->prefix . 'product_questions';

			$parent_id = isset( $_GET['id'] )
				? absint( $_GET['id'] )
				: 0;

			$question = $wpdb->get_row(
				$wpdb->prepare(
					"
					SELECT *
					FROM {$table}
					WHERE id=%d
					",
					$parent_id
				)
			);

			if ( ! $question ) {
				return;
			}

			$content = isset( $_POST['answer'] )
				? sanitize_textarea_field(
					wp_unslash( $_POST['answer'] )
				)
				: '';

			if ( empty( $content ) ) {
				return;
			}

			$wpdb->insert(

				$table,

				array(

					'product_id' => $question->product_id,

					'parent_id' => $parent_id,

					'user_id' => 0,

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
			
			dcqa_clear_product_cache( $question->product_id );

			wp_safe_redirect(
				admin_url(
					'admin.php?page=dcqa-questions'
				)
			);

			exit;

		}

		/*
		|--------------------------------------------------------------------------
		| ویرایش پاسخ
		|--------------------------------------------------------------------------
		*/

		if ( isset( $_POST['dcqa_update_answer'] ) ) {

			check_admin_referer( 'dcqa_edit_answer' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
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

if ( ! $answer ) {
    return;
}

			$content = isset( $_POST['content'] )
				? sanitize_textarea_field(
					wp_unslash( $_POST['content'] )
				)
				: '';

			$created_at = isset( $_POST['created_at'] )
				? sanitize_text_field(
					wp_unslash( $_POST['created_at'] )
				)
				: '';

			/*
			|--------------------------------------------------------------------------
			| تبدیل تاریخ datetime-local
			|--------------------------------------------------------------------------
			*/

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
			| بروزرسانی پاسخ
			|--------------------------------------------------------------------------
			*/

			$wpdb->update(

				$table,

				array(

					'content' => $content,

					'created_at' => $created_at,

					'updated_at' => current_time( 'mysql' ),

				),

				array(

					'id' => $id,

				),

				array(

					'%s',
					'%s',
					'%s',

				),

				array(

					'%d',

				)

			);
			
			dcqa_clear_product_cache( $answer->product_id );

			wp_safe_redirect(
				admin_url(
					'admin.php?page=dcqa-questions'
				)
			);

			exit;

		}

	}

}