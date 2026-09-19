<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DCQA_Edit {

	public function __construct() {

		add_action(
			'admin_menu',
			array( $this, 'menu' )
		);

		add_action(
			'admin_init',
			array( $this, 'save' )
		);

	}

	/*
	|--------------------------------------------------------------------------
	| منوی ویرایش
	|--------------------------------------------------------------------------
	*/

	public function menu() {

		add_submenu_page(
			null,
			'ویرایش پرسش',
			'ویرایش پرسش',
			'manage_woocommerce',
			'dcqa-edit',
			array( $this, 'page' )
		);

	}

	/*
	|--------------------------------------------------------------------------
	| صفحه ویرایش
	|--------------------------------------------------------------------------
	*/

	public function page() {

		global $wpdb;

		$table = $wpdb->prefix . 'product_questions';

		$id = isset( $_GET['id'] )
			? absint( $_GET['id'] )
			: 0;

		if ( ! $id ) {
			wp_die( 'شناسه پرسش نامعتبر است.' );
		}

		$question = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT *
				FROM {$table}
				WHERE id=%d",
				$id
			)
		);

		if ( ! $question ) {
			wp_die( 'پرسش پیدا نشد.' );
		}

		?>

		<div class="wrap">

			<h1>
				ویرایش پرسش
			</h1>

			<form method="post">

				<?php wp_nonce_field( 'dcqa_edit' ); ?>

				<table class="form-table">
                    
					<tr>

						<th>
							محصول
						</th>

						<td>

							<?php

							echo esc_html(
								get_the_title(
									$question->product_id
								)
							);

							?>

						</td>

					</tr>


					<tr>

						<th>
							متن
						</th>

						<td>

							<textarea
								name="content"
								rows="8"
								style="width:100%;"
							><?php echo esc_textarea( $question->content ); ?></textarea>

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
										strtotime( $question->created_at )
									)
								); ?>"
							>

							<p class="description">

								تاریخ و ساعت ثبت پرسش را تغییر دهید.

							</p>

						</td>

					</tr>

				</table>


				<button
					class="button button-primary"
					name="dcqa_save"
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
	| ذخیره تغییرات
	|--------------------------------------------------------------------------
	*/

	public function save() {

		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		if ( $_GET['page'] !== 'dcqa-edit' ) {
			return;
		}

		if ( ! isset( $_POST['dcqa_save'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( 'dcqa_edit' );

		global $wpdb;

		$table = $wpdb->prefix . 'product_questions';

		$id = isset( $_GET['id'] )
			? absint( $_GET['id'] )
			: 0;

		if ( ! $id ) {
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
		|----------------------------------------------------------------------
		| تبدیل datetime-local به فرمت دیتابیس
		|----------------------------------------------------------------------
		*/

		if ( $created_at ) {

			$created_at = str_replace(
				'T',
				' ',
				$created_at
			);

			$created_at = date(
				'Y-m-d H:i:s',
				strtotime( $created_at )
			);

		}

		/*
		|----------------------------------------------------------------------
		| بروزرسانی
		|----------------------------------------------------------------------
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

		wp_safe_redirect(

			admin_url(
				'admin.php?page=dcqa-questions'
			)

		);

		exit;

	}

}