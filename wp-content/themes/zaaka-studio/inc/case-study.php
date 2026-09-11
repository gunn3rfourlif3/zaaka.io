<?php
/**
 * Case-study summary and client testimonial.
 *
 * The case studies are written for the person who will maintain the system.
 * The person deciding whether to hire the studio needs the short version
 * first: what was wrong, what we did, what changed, and ideally someone else
 * saying so. These two blocks carry that, filled from fields on the project.
 *
 * Both render nothing when their fields are empty, so a project without a
 * testimonial simply has no testimonial rather than an empty frame. Nothing
 * here should ever be filled with placeholder or invented copy.
 *
 * @package Zaaka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fields added on top of the ones registered in functions.php. Registered the
 * same way, so they are available to the REST API and the block editor.
 */
function zaaka_case_fields() {
	return array(
		'zaaka_problem'    => __( 'The problem', 'zaaka-studio' ),
		'zaaka_results'    => __( 'Result figures', 'zaaka-studio' ),
		'zaaka_quote'      => __( 'Testimonial quote', 'zaaka-studio' ),
		'zaaka_quote_name' => __( 'Testimonial name', 'zaaka-studio' ),
		'zaaka_quote_role' => __( 'Testimonial role and company', 'zaaka-studio' ),
	);
}

function zaaka_register_case_meta() {
	foreach ( zaaka_case_fields() as $key => $label ) {
		register_post_meta(
			'project',
			$key,
			array(
				'type'          => 'string',
				'description'   => $label,
				'single'        => true,
				'default'       => '',
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'zaaka_register_case_meta' );

/* ------------------------------------------------------------ meta box -- */

/**
 * A plain meta box rather than a sidebar plugin: it needs no build step, and it
 * puts every field that drives the summary in one labelled place instead of
 * behind the editor's Custom Fields preference.
 */
function zaaka_case_meta_box() {
	add_meta_box(
		'zaaka-case-summary',
		__( 'Case study summary', 'zaaka-studio' ),
		'zaaka_case_meta_box_html',
		'project',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'zaaka_case_meta_box' );

function zaaka_case_meta_box_html( $post ) {
	wp_nonce_field( 'zaaka_case_summary', 'zaaka_case_summary_nonce' );

	$rows = array(
		'zaaka_problem'    => array( 'textarea', __( 'The problem', 'zaaka-studio' ), __( 'One or two sentences a non-technical buyer would recognise.', 'zaaka-studio' ) ),
		'zaaka_role'       => array( 'text', __( 'What we did', 'zaaka-studio' ), __( 'Our role, e.g. "Product, design, engineering and infrastructure".', 'zaaka-studio' ) ),
		'zaaka_outcome'    => array( 'textarea', __( 'The result', 'zaaka-studio' ), __( 'What changed. Only what can be defended.', 'zaaka-studio' ) ),
		'zaaka_results'    => array( 'textarea', __( 'Result figures (optional)', 'zaaka-studio' ), __( 'One per line: figure | label — e.g. "340 | units under management". Real numbers only; leave empty rather than estimate.', 'zaaka-studio' ) ),
		'zaaka_quote'      => array( 'textarea', __( 'Testimonial quote (optional)', 'zaaka-studio' ), __( 'Exact words, with the person\'s permission. Hidden until filled.', 'zaaka-studio' ) ),
		'zaaka_quote_name' => array( 'text', __( 'Testimonial name', 'zaaka-studio' ), '' ),
		'zaaka_quote_role' => array( 'text', __( 'Testimonial role and company', 'zaaka-studio' ), '' ),
	);

	echo '<div class="zaaka-case-fields">';
	foreach ( $rows as $key => $row ) {
		list( $type, $label, $help ) = $row;
		$value = get_post_meta( $post->ID, $key, true );
		printf( '<p><label for="%1$s"><strong>%2$s</strong></label><br>', esc_attr( $key ), esc_html( $label ) );
		if ( 'textarea' === $type ) {
			printf( '<textarea id="%1$s" name="%1$s" rows="3" class="widefat">%2$s</textarea>', esc_attr( $key ), esc_textarea( $value ) );
		} else {
			printf( '<input type="text" id="%1$s" name="%1$s" value="%2$s" class="widefat">', esc_attr( $key ), esc_attr( $value ) );
		}
		if ( $help ) {
			printf( '<span class="description">%s</span>', esc_html( $help ) );
		}
		echo '</p>';
	}
	echo '</div>';
}

function zaaka_case_meta_save( $post_id ) {
	if ( ! isset( $_POST['zaaka_case_summary_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['zaaka_case_summary_nonce'] ), 'zaaka_case_summary' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$multiline = array( 'zaaka_problem', 'zaaka_outcome', 'zaaka_results', 'zaaka_quote' );
	$keys      = array( 'zaaka_problem', 'zaaka_role', 'zaaka_outcome', 'zaaka_results', 'zaaka_quote', 'zaaka_quote_name', 'zaaka_quote_role' );

	foreach ( $keys as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw   = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$value = in_array( $key, $multiline, true ) ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		update_post_meta( $post_id, $key, $value );
	}
}
add_action( 'save_post_project', 'zaaka_case_meta_save' );

/* -------------------------------------------------------------- blocks -- */

/**
 * Two dynamic blocks placed in single-project.html. Server-rendered, so the
 * front end needs no JavaScript; the editor script only gives them a preview.
 */
function zaaka_register_case_blocks() {
	wp_register_script(
		'zaaka-case-blocks',
		get_template_directory_uri() . '/assets/js/case-blocks.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-server-side-render' ),
		ZAAKA_VERSION,
		true
	);

	register_block_type(
		'zaaka/case-summary',
		array(
			'api_version'     => 3,
			'title'           => __( 'Case study summary', 'zaaka-studio' ),
			'category'        => 'theme',
			'editor_script'   => 'zaaka-case-blocks',
			'uses_context'    => array( 'postId' ),
			'supports'        => array( 'html' => false ),
			'render_callback' => 'zaaka_render_case_summary',
		)
	);

	register_block_type(
		'zaaka/case-quote',
		array(
			'api_version'     => 3,
			'title'           => __( 'Case study testimonial', 'zaaka-studio' ),
			'category'        => 'theme',
			'editor_script'   => 'zaaka-case-blocks',
			'uses_context'    => array( 'postId' ),
			'supports'        => array( 'html' => false ),
			'render_callback' => 'zaaka_render_case_quote',
		)
	);
}
add_action( 'init', 'zaaka_register_case_blocks' );

/**
 * Resolve which project a block is rendering for: block context first (the
 * query loop and the editor preview), then the queried object.
 */
function zaaka_case_post_id( $block ) {
	if ( $block instanceof WP_Block && ! empty( $block->context['postId'] ) ) {
		return (int) $block->context['postId'];
	}
	if ( is_singular() ) {
		return (int) get_queried_object_id();
	}
	return (int) get_the_ID(); // The editor preview, via the block renderer's post_id.
}

/**
 * Parse "figure | label" lines. Lines without a separator are skipped rather
 * than guessed at.
 */
function zaaka_case_figures( $raw ) {
	$figures = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( 2 === count( $parts ) && '' !== $parts[0] && '' !== $parts[1] ) {
			$figures[] = $parts;
		}
	}
	return array_slice( $figures, 0, 4 );
}

function zaaka_render_case_summary( $attributes, $content, $block ) {
	$id = zaaka_case_post_id( $block );
	if ( ! $id || 'project' !== get_post_type( $id ) ) {
		return '';
	}

	$cells = array_filter(
		array(
			__( 'The problem', 'zaaka-studio' ) => get_post_meta( $id, 'zaaka_problem', true ),
			__( 'What we did', 'zaaka-studio' ) => get_post_meta( $id, 'zaaka_role', true ),
			__( 'The result', 'zaaka-studio' )  => get_post_meta( $id, 'zaaka_outcome', true ),
		),
		'strlen'
	);
	$figures = zaaka_case_figures( get_post_meta( $id, 'zaaka_results', true ) );

	if ( ! $cells && ! $figures ) {
		return '';
	}

	$html = '<section class="zk-summary alignwide" aria-labelledby="zk-summary-title">';
	$html .= '<h2 id="zk-summary-title" class="screen-reader-text">' . esc_html__( 'At a glance', 'zaaka-studio' ) . '</h2>';

	if ( $cells ) {
		$html .= '<dl class="zk-summary__grid">';
		foreach ( $cells as $label => $text ) {
			$html .= '<div class="zk-summary__cell"><dt class="zk-meta">' . esc_html( $label ) . '</dt><dd>' . esc_html( $text ) . '</dd></div>';
		}
		$html .= '</dl>';
	}

	if ( $figures ) {
		$html .= '<ul class="zk-summary__figures" role="list">';
		foreach ( $figures as $f ) {
			$html .= '<li><span class="zk-summary__figure">' . esc_html( $f[0] ) . '</span><span class="zk-summary__label">' . esc_html( $f[1] ) . '</span></li>';
		}
		$html .= '</ul>';
	}

	return $html . '</section>';
}

function zaaka_render_case_quote( $attributes, $content, $block ) {
	$id = zaaka_case_post_id( $block );
	if ( ! $id || 'project' !== get_post_type( $id ) ) {
		return '';
	}

	$quote = trim( (string) get_post_meta( $id, 'zaaka_quote', true ) );
	$name  = trim( (string) get_post_meta( $id, 'zaaka_quote_name', true ) );
	$role  = trim( (string) get_post_meta( $id, 'zaaka_quote_role', true ) );

	// A quote without a name is not a testimonial. Render neither.
	if ( '' === $quote || '' === $name ) {
		return '';
	}

	$cite = '<span class="zk-quote__name">' . esc_html( $name ) . '</span>';
	if ( $role ) {
		$cite .= '<span class="zk-quote__role">' . esc_html( $role ) . '</span>';
	}

	return '<figure class="zk-quote alignwide"><blockquote><p>' . nl2br( esc_html( $quote ) ) . '</p></blockquote>' .
		'<figcaption>' . $cite . '</figcaption></figure>';
}
