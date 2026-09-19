<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DCQA_Schema {

	public function __construct() {

		add_action(
			'wp_head',
			array( $this, 'output_schema' ),
			20
		);

	}


	private function dcqa_schema_date( $date ) {

		if ( empty( $date ) ) {
			return null;
		}

		$timestamp = strtotime( $date );

		if ( $timestamp ) {

			return date(
				DATE_W3C,
				$timestamp
			);

		}

		return null;

	}



	private function get_author_name( $question ) {

		$name = '';

		if (
			isset( $question->display_name ) &&
			! empty(
				trim(
					$question->display_name
				)
			)
		) {

			$name = $question->display_name;

		}


		if (
			empty( $name ) &&
			function_exists( 'dcqa_get_user_name' )
		) {

			$name = dcqa_get_user_name(
				$question->user_id
			);

		}


		$name = wp_strip_all_tags(
			(string) $name
		);

		$name = trim( $name );


		if (
			empty( $name ) ||
			$name === 'کاربر دکاشاپ'
		) {

			$name = 'ناشناس';

		}


		return $name;

	}



	public function output_schema() {


		static $schema_output = false;


		if ( $schema_output ) {
			return;
		}


		if ( ! is_product() ) {
			return;
		}


		$schema_output = true;


		global $product, $wpdb;


		if (
			! $product ||
			! is_a(
				$product,
				'WC_Product'
			)
		) {

			return;

		}



		$product_id = $product->get_id();


		$table = $wpdb->prefix . 'product_questions';



		$questions = $wpdb->get_results(

			$wpdb->prepare(

				"
				SELECT *
				FROM {$table}
				WHERE product_id=%d
				AND parent_id=0
				AND status='approved'
				ORDER BY created_at ASC
				",

				$product_id

			)

		);



		if ( empty( $questions ) ) {
			return;
		}



		$main_entity = array();



		foreach ( $questions as $question ) {


			$question_text = wp_strip_all_tags(
				$question->content
			);


			$question_text = trim(
				$question_text
			);



			if ( empty( $question_text ) ) {
				continue;
			}



			$answers = $wpdb->get_results(

				$wpdb->prepare(

					"
					SELECT *
					FROM {$table}
					WHERE parent_id=%d
					AND status='approved'
					ORDER BY created_at ASC
					",

					$question->id

				)

			);



			if ( empty( $answers ) ) {
				continue;
			}



			$official_answer = null;



			foreach ( $answers as $answer ) {


				$user = get_userdata(
					$answer->user_id
				);


				if ( ! $user ) {
					continue;
				}



				$roles = (array) $user->roles;



				$is_official = (

					in_array(
						'administrator',
						$roles,
						true
					)

					||

					in_array(
						'shop_manager',
						$roles,
						true
					)

					||

					in_array(
						'editor',
						$roles,
						true
					)

				);



				if ( $is_official ) {

					$official_answer = $answer;

					break;

				}

			}



			if ( ! $official_answer ) {
				continue;
			}



			$answer_text = wp_strip_all_tags(
				$official_answer->content
			);


			$answer_text = trim(
				$answer_text
			);



			if ( empty( $answer_text ) ) {
				continue;
			}




			$main_entity[] = array(

				'@type' => 'Question',

				'name' => $question_text,


				'acceptedAnswer' => array(

					'@type' => 'Answer',

					'text' => $answer_text,


					'datePublished' =>
						$this->dcqa_schema_date(
							$official_answer->created_at
						),


					'author' => array(

						'@type' => 'Organization',

						'name' => 'دکاشاپ'

					)

				),


			);


		}



		if ( empty( $main_entity ) ) {
			return;
		}




		$schema = array(

			'@context' => 'https://schema.org',

			'@type' => 'FAQPage',

			'mainEntity' => $main_entity

		);



		echo "\n";

		echo '<script type="application/ld+json" class="dcqa-schema">';


		echo wp_json_encode(

			$schema,

			JSON_UNESCAPED_UNICODE |
			JSON_UNESCAPED_SLASHES

		);


		echo '</script>';

		echo "\n";


	}

}