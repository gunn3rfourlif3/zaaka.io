<?php
/**
 * /book/ — one stable address for booking a call.
 *
 * Every "Book an intro call" link on the site points at /book/, and this
 * redirects to whatever scheduling page is set under Settings → General →
 * Booking link. Changing tools later (Calendly today, something else next
 * year) is then one setting rather than a hunt through page content. With no
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
				'<input type="url" id="zaaka_booking_url" name="zaaka_booking_url" value="%s" class="regular-text code" placeholder="https://calendly.com/…">' .
				'<p class="description">%s</p>',
				esc_attr( get_option( 'zaaka_booking_url', '' ) ),
				esc_html__( 'Where /book/ sends people — your Calendly intro-call page. Leave empty to send them to /contact/.', 'zaaka-studio' )
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
