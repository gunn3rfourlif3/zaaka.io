<?php
/**
 * /book/ — one stable address for booking a call.
 *
 * Every "Book an intro call" link on the site points at /book/, and this
 * redirects to whatever scheduling page is set under Settings → General →
 * Booking link. Changing tools later is then one setting rather than a hunt
 * through page content — which is the whole point: scheduling tools expire,
 * get acquired and change their pricing, and nine pages link to /book/. With no
 * link set, /book/ falls back to the contact page, so the button is never a
 * dead end.
 *
 * @package Zaaka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function zaaka_booking_setting() {
	register_setting(
		'general',
		'zaaka_booking_url',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
			'show_in_rest'      => false,
		)
	);

	add_settings_field(
		'zaaka_booking_url',
		'<label for="zaaka_booking_url">' . esc_html__( 'Booking link', 'zaaka-studio' ) . '</label>',
		function () {
			printf(
				'<input type="url" id="zaaka_booking_url" name="zaaka_booking_url" value="%s" class="regular-text code" placeholder="https://cal.com/…">' .
				'<p class="description">%s</p>',
				esc_attr( get_option( 'zaaka_booking_url', '' ) ),
				esc_html__( 'Where /book/ sends people — your scheduler’s intro-call page. Leave it empty and /book/ falls back to /contact/, which is a working page rather than a dead link. Clear it the day a trial ends: an expired booking page is worse than no booking page.', 'zaaka-studio' )
			);
		},
		'general'
	);
}
add_action( 'admin_init', 'zaaka_booking_setting' );

/**
 * The URL a booking link should reach right now.
 */
function zaaka_booking_url() {
	$url = (string) get_option( 'zaaka_booking_url', '' );
	return $url ? $url : home_url( '/contact/' );
}

/**
 * Serve /book/ before WordPress decides it is a 404. Matching the request path
 * directly avoids a rewrite rule, which would need a permalink flush to take
 * effect — a step that is easy to forget on deploy.
 */
function zaaka_booking_redirect() {
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	if ( $home && str_starts_with( $path, $home . '/' ) ) {
		$path = substr( $path, strlen( $home ) + 1 );
	}

	if ( 'book' !== $path ) {
		return;
	}

	// 302, not 301: the destination is a setting and is expected to change.
	wp_redirect( zaaka_booking_url(), 302, 'Zaaka' ); // phpcs:ignore WordPress.Security.SafeRedirect -- external scheduler by design.
	exit;
}
add_action( 'template_redirect', 'zaaka_booking_redirect', 1 );

/**
 * Say so in the admin when there is no booking link.
 *
 * An empty setting is a quiet state: every "Book an intro call" button still
 * works, it just lands on /contact/ instead. That is the right fallback and
 * exactly the wrong thing to forget about for a month.
 */
function zaaka_booking_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( get_option( 'zaaka_booking_url', '' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'options-general' ), true ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
		esc_html__( 'No booking link is set, so every "Book an intro call" button currently lands on the contact page.', 'zaaka-studio' ),
		esc_url( admin_url( 'options-general.php#zaaka_booking_url' ) ),
		esc_html__( 'Set one', 'zaaka-studio' )
	);
}
add_action( 'admin_notices', 'zaaka_booking_notice' );
