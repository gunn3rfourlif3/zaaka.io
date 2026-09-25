<?php
/**
 * /brief/ — the Brief Builder.
 *
 * A stepped questionnaire that captures what a prospect is actually buying
 * before the first call. The stepping, the sliders and the self-composing brief
 * are all added by assets/js/brief.js; what is defined here is a plain form that
 * posts once and works with JavaScript off. That is deliberate, and it is the
 * reason there is no REST endpoint and no fetch anywhere in this feature: a
 * script that fails to load must not be able to swallow a lead.
 *
 * Submissions are stored as a private `brief` post, not just emailed. Outbound
 * mail from shared hosting fails quietly often enough that email-only storage
 * loses briefs without anyone noticing.
 *
 * @package Zaaka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The fields the form collects, and how each one is cleaned.
 *
 * Kept as one list so the form, the stored record and the notification email
 * cannot drift apart — adding a question means adding one line here.
 */
function zaaka_brief_fields() {
	return array(
		'route'      => array( 'label' => 'What it is',            'type' => 'text' ),
		'buildtype'  => array( 'label' => 'What form it takes',    'type' => 'text' ),
		'industry'   => array( 'label' => 'Industry',              'type' => 'text' ),
		'problem'    => array( 'label' => 'The problem now',       'type' => 'textarea' ),
		'donothing'  => array( 'label' => 'If nothing is done',    'type' => 'text' ),
		'audience'   => array( 'label' => 'Who it is for',         'type' => 'text' ),
		'count'      => array( 'label' => 'Roughly how many',      'type' => 'text' ),
		'trend'      => array( 'label' => 'Growing or flat',       'type' => 'text' ),
		'money'      => array( 'label' => 'How it earns',          'type' => 'text' ),
		'metric'     => array( 'label' => 'What success looks like','type' => 'text' ),
		'url'        => array( 'label' => 'What exists',           'type' => 'text' ),
		'owner'      => array( 'label' => 'Who owns it today',     'type' => 'text' ),
		'scope'      => array( 'label' => 'First release',         'type' => 'text' ),
		'timeline'   => array( 'label' => 'Live by',               'type' => 'text' ),
		'datereal'   => array( 'label' => 'Is the date real',      'type' => 'text' ),
		'budget'     => array( 'label' => 'Budget',                'type' => 'text' ),
		'decider'    => array( 'label' => 'Who signs off',         'type' => 'text' ),
		'clienttime' => array( 'label' => 'Their time per week',   'type' => 'text' ),
		'name'       => array( 'label' => 'Name',                  'type' => 'text' ),
		'company'    => array( 'label' => 'Company',               'type' => 'text' ),
		'email'      => array( 'label' => 'Email',                 'type' => 'email' ),
	);
}

/**
 * The brief record. Private, because these contain a stranger's budget and are
 * nobody's business but the studio's — `public => false` keeps them out of the
 * archive, the sitemap, search and the REST API in one move.
 */
function zaaka_register_brief() {
	register_post_type(
		'brief',
		array(
			'labels'          => array(
				'name'          => __( 'Briefs', 'zaaka-studio' ),
				'singular_name' => __( 'Brief', 'zaaka-studio' ),
				'all_items'     => __( 'All briefs', 'zaaka-studio' ),
				'menu_name'     => __( 'Briefs', 'zaaka-studio' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-clipboard',
			'menu_position'   => 21,
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'supports'        => array( 'title', 'custom-fields' ),
			'has_archive'     => false,
			'rewrite'         => false,
			'can_export'      => true,
		)
	);
}
add_action( 'init', 'zaaka_register_brief' );

/**
 * Where the notification goes. Follows the zaaka_booking_url precedent: one
 * setting under Settings → General rather than a constant somebody has to edit
 * a theme file to change.
 */
function zaaka_brief_setting() {
	register_setting(
		'general',
		'zaaka_brief_email',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => '',
			'show_in_rest'      => false,
		)
	);

	add_settings_field(
		'zaaka_brief_email',
		'<label for="zaaka_brief_email">' . esc_html__( 'Brief notifications', 'zaaka-studio' ) . '</label>',
		function () {
			printf(
				'<input type="email" id="zaaka_brief_email" name="zaaka_brief_email" value="%s" class="regular-text code" placeholder="hello@zaaka.io">' .
				'<p class="description">%s</p>',
				esc_attr( get_option( 'zaaka_brief_email', '' ) ),
				esc_html__( 'Where a completed brief is emailed. Empty falls back to the site admin address. Every brief is stored under Briefs either way.', 'zaaka-studio' )
			);
		},
		'general'
	);
}
add_action( 'admin_init', 'zaaka_brief_setting' );

function zaaka_brief_email() {
	$email = (string) get_option( 'zaaka_brief_email', '' );
	return $email ? $email : get_option( 'admin_email' );
}

/**
 * Handle the submission.
 *
 * Posts to admin-post.php rather than to the page itself, so the form keeps
 * working regardless of which page the pattern is placed on, and so a
 * submission never lands mid-render of a template.
 */
function zaaka_brief_submit() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/brief/' );

	if ( ! isset( $_POST['zaaka_brief_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['zaaka_brief_nonce'] ) ), 'zaaka_brief' ) ) {
		wp_safe_redirect( add_query_arg( 'brief', 'expired', $back ) . '#brief' );
		exit;
	}

	// Honeypot. A field no human sees and every naive bot fills in. Silently
	// pretend it worked — telling a bot it failed only teaches it.
	if ( ! empty( $_POST['zaaka_website'] ) ) {
		wp_safe_redirect( add_query_arg( 'brief', 'sent', $back ) . '#brief' );
		exit;
	}

	// Time on form. Nobody completes ten honest steps in four seconds.
	$started = isset( $_POST['zaaka_started'] ) ? absint( $_POST['zaaka_started'] ) : 0;
	if ( $started && ( time() - $started ) < 4 ) {
		wp_safe_redirect( add_query_arg( 'brief', 'sent', $back ) . '#brief' );
		exit;
	}

	$values = array();
	foreach ( zaaka_brief_fields() as $key => $field ) {
		$raw = isset( $_POST[ 'zk_' . $key ] ) ? wp_unslash( $_POST[ 'zk_' . $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( 'scope' === $key && is_array( $raw ) ) {
			$parts = array();
			foreach ( $raw as $item => $state ) {
				$state = sanitize_text_field( $state );
				if ( 'must' === $state || 'later' === $state ) {
					$parts[] = sanitize_text_field( $item ) . ' (' . $state . ')';
				}
			}
			$values[ $key ] = implode( ', ', $parts );
			continue;
		}

		$raw = is_array( $raw ) ? '' : $raw;

		if ( 'email' === $field['type'] ) {
			$values[ $key ] = sanitize_email( $raw );
		} elseif ( 'textarea' === $field['type'] ) {
			$values[ $key ] = sanitize_textarea_field( $raw );
		} else {
			$values[ $key ] = sanitize_text_field( $raw );
		}
	}

	// An email is the only field worth insisting on — without it there is no
	// way to reply, which makes the rest of the brief academic.
	if ( ! is_email( $values['email'] ) ) {
		wp_safe_redirect( add_query_arg( 'brief', 'email', $back ) . '#brief' );
		exit;
	}

	$title = $values['company'] ? $values['company'] : ( $values['name'] ? $values['name'] : $values['email'] );
	if ( $values['route'] ) {
		$title .= ' — ' . $values['route'];
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'brief',
			'post_status' => 'private',
			'post_title'  => wp_trim_words( $title, 14, '' ),
		),
		true
	);

	if ( ! is_wp_error( $post_id ) ) {
		foreach ( $values as $key => $value ) {
			update_post_meta( $post_id, 'zaaka_' . $key, $value );
		}
		update_post_meta( $post_id, 'zaaka_received', current_time( 'mysql' ) );
	}

	zaaka_brief_notify( $values, is_wp_error( $post_id ) ? 0 : $post_id );

	wp_safe_redirect( add_query_arg( 'brief', 'sent', $back ) . '#brief' );
	exit;
}
add_action( 'admin_post_nopriv_zaaka_brief', 'zaaka_brief_submit' );
add_action( 'admin_post_zaaka_brief', 'zaaka_brief_submit' );

/**
 * Send the two messages: the visitor's copy, then the studio's notification.
 *
 * The visitor's copy goes first and on purpose. If the host throttles or drops
 * the second one, the message that was promised on screen is the one that got
 * out; the studio's copy has the stored brief in the admin behind it either way.
 */
function zaaka_brief_notify( $values, $post_id ) {
	$who    = $values['company'] ? $values['company'] : $values['name'];
	$report = array();

	/**
	 * Filter whether the person who filled in the brief is sent their own copy.
	 * Turning this off leaves the confirmation screen promising something that
	 * does not arrive, so change the copy too if you use it.
	 */
	if ( apply_filters( 'zaaka_brief_send_client_copy', true, $values ) ) {
		zaaka_mail_last_error( '' );
		$ok = zaaka_brief_send(
			$values['email'],
			__( 'Your brief — Zaaka', 'zaaka-studio' ),
			zaaka_brief_email_html( $values, $post_id, 'client' ),
			zaaka_brief_email_text( $values, $post_id, 'client' ),
			array( 'Reply-To: ' . zaaka_brief_email() )
		);
		$report['client'] = $ok ? 'sent' : ( 'failed: ' . zaaka_mail_last_error() );
	}

	zaaka_mail_last_error( '' );
	$ok = zaaka_brief_send(
		zaaka_brief_email(),
		sprintf(
			/* translators: %s: company or person's name. */
			__( 'Brief — %s', 'zaaka-studio' ),
			$who
		),
		zaaka_brief_email_html( $values, $post_id, 'studio' ),
		zaaka_brief_email_text( $values, $post_id, 'studio' ),
		array( 'Reply-To: ' . $values['email'] )
	);
	$report['studio'] = $ok ? 'sent' : ( 'failed: ' . zaaka_mail_last_error() );

	// Written onto the brief so a silent drop is visible where the brief is
	// read, rather than only in an inbox that never fills up.
	if ( $post_id ) {
		update_post_meta( $post_id, 'zaaka_mail_report', $report );
	}
}

/**
 * Show the stored fields on the brief edit screen.
 *
 * `custom-fields` support alone renders them, but in an unordered list with raw
 * meta keys, which is unreadable when you are trying to answer someone. This is
 * the same data in the order the questions were asked.
 */
function zaaka_brief_metabox() {
	add_meta_box(
		'zaaka-brief',
		__( 'The brief', 'zaaka-studio' ),
		function ( $post ) {
			echo '<table class="widefat striped"><tbody>';
			foreach ( zaaka_brief_fields() as $key => $field ) {
				$value = (string) get_post_meta( $post->ID, 'zaaka_' . $key, true );
				if ( '' === $value ) {
					continue;
				}
				printf(
					'<tr><th scope="row" style="width:12rem">%s</th><td>%s</td></tr>',
					esc_html( $field['label'] ),
					nl2br( esc_html( $value ) )
				);
			}
			echo '</tbody></table>';

			$report = get_post_meta( $post->ID, 'zaaka_mail_report', true );
			if ( is_array( $report ) ) {
				echo '<p style="margin-top:1rem"><strong>' . esc_html__( 'Email', 'zaaka-studio' ) . '</strong><br>';
				foreach ( $report as $who => $state ) {
					$ok = ( 'sent' === $state );
					printf(
						'<span style="color:%s">%s</span> &mdash; %s<br>',
						$ok ? '#007017' : '#b32d2e',
						esc_html( 'client' === $who ? __( 'Copy to them', 'zaaka-studio' ) : __( 'Notification to us', 'zaaka-studio' ) ),
						esc_html( $state )
					);
				}
				echo '</p>';
			}
		},
		'brief',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'zaaka_brief_metabox' );

/**
 * Load the upgrade layer only where the form actually is.
 *
 * Keyed on the shortcode rather than a page slug: the form can be placed on any
 * page, and tying the script to /brief/ would mean a form that silently stops
 * stepping the moment somebody moves it.
 */
function zaaka_brief_assets() {
	if ( ! is_singular() ) {
		return;
	}
	$post = get_post();
	if ( ! $post || ! has_shortcode( (string) $post->post_content, 'zaaka_brief' ) ) {
		return;
	}

	wp_enqueue_script(
		'zaaka-brief',
		get_template_directory_uri() . '/assets/js/brief.js',
		array(),
		ZAAKA_VERSION,
		array( 'strategy' => 'defer', 'in_footer' => true )
	);
}
add_action( 'wp_enqueue_scripts', 'zaaka_brief_assets' );
