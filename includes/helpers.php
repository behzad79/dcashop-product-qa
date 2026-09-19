<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * تاریخ شمسی
 */
function dcqa_get_date( $datetime ) {

	$timestamp = strtotime( $datetime );

	// اگر ووکامرس فارسی یا افزونه مشابه تابع jdate را داشته باشد
	if ( function_exists( 'jdate' ) ) {
		return jdate( 'j F Y', $timestamp );
	}

	// اگر تابع wp_date موجود باشد
	if ( function_exists( 'wp_date' ) ) {
		return wp_date(
			'j F Y',
			$timestamp,
			wp_timezone()
		);
	}

	return date_i18n(
		'j F Y',
		$timestamp
	);

}


/**
 * نام نمایشی کاربر
 */
function dcqa_get_user_name( $user_id, $custom_name = '' ) {

	/*
	|--------------------------------------------------------------------------
	| نام اختصاصی ثبت‌شده برای همین پرسش / پاسخ
	|--------------------------------------------------------------------------
	*/

	$custom_name = trim( $custom_name );

	if ( $custom_name !== '' ) {
		return $custom_name;
	}


	/*
	|--------------------------------------------------------------------------
	| کاربر سیستمی / بدون کاربر
	|--------------------------------------------------------------------------
	*/

	if ( intval( $user_id ) === 0 ) {
		return 'دکاشاپ';
	}


	$user = get_userdata( $user_id );

	if ( ! $user ) {
		return 'کاربر دکاشاپ';
	}


	/*
	|--------------------------------------------------------------------------
	| نقش‌های مدیریتی
	|--------------------------------------------------------------------------
	*/

	$roles = (array) $user->roles;

	if (
		in_array( 'administrator', $roles, true ) ||
		in_array( 'shop_manager', $roles, true ) ||
		in_array( 'editor', $roles, true )
	) {
		return 'دکاشاپ';
	}


	/*
	|--------------------------------------------------------------------------
	| نام کاربر
	|--------------------------------------------------------------------------
	*/

	$first = trim( $user->first_name );
	$last  = trim( $user->last_name );

	if ( $first || $last ) {
		return trim( $first . ' ' . $last );
	}


	return 'کاربر دکاشاپ';

}