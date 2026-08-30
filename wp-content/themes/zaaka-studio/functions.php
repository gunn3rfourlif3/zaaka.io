<?php
/**
 * Zaaka — block theme functions.
 *
 * Deliberately small. Everything that can be expressed in theme.json or block
 * markup is expressed there, so the site stays editable without a developer.
 *
 * @package Zaaka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ZAAKA_VERSION', '1.4.2' );

/**
 * Theme supports.
 */
function zaaka_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary', 'zaaka-studio' ),
			'footer'  => __( 'Footer', 'zaaka-studio' ),
		)
	);
}
add_action( 'after_setup_theme', 'zaaka_setup' );

/**
 * Front-end assets. One small stylesheet; no JavaScript framework, no jQuery.
 */
function zaaka_assets() {
	wp_enqueue_style(
		'zaaka',
		get_template_directory_uri() . '/assets/css/theme.css',
		array(),
		ZAAKA_VERSION
	);

	wp_enqueue_script(
		'zaaka',
		get_template_directory_uri() . '/assets/js/zaaka.js',
		array(),
		ZAAKA_VERSION,
		array( 'strategy' => 'defer', 'in_footer' => false )
	);
}
add_action( 'wp_enqueue_scripts', 'zaaka_assets' );

/**
 * Keep the current case study out of its own "Keep reading" list.
 *
 * A core query loop with inherit:false does not know what page it is on, so on
 * a single project the related list happily includes the project you are
 * already reading. There is no UI for this — it has to be a query filter.
 */
function zaaka_exclude_current_project( $query, $block ) {
	if ( ! is_singular( 'project' ) ) {
		return $query;
	}
	$post_type = $query['post_type'] ?? '';
	if ( 'project' !== $post_type && ! ( is_array( $post_type ) && in_array( 'project', $post_type, true ) ) ) {
		return $query;
	}
	$excluded            = isset( $query['post__not_in'] ) ? (array) $query['post__not_in'] : array();
	$excluded[]          = get_queried_object_id();
	$query['post__not_in'] = array_unique( $excluded );

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'zaaka_exclude_current_project', 10, 2 );

/**
 * Drop the inline SVG duotone filters WordPress prints on every request. Small,
 * but free bytes.
 *
 * Nothing else is dequeued here: the global stylesheet generated from theme.json
 * carries every colour, font-size and spacing custom property the theme depends
 * on, and removing it strips the design system.
 */
function zaaka_trim_head() {
	remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
	remove_action( 'in_admin_header', 'wp_global_styles_render_svg_filters' );
}
add_action( 'wp_enqueue_scripts', 'zaaka_trim_head', 20 );

/**
 * Project custom post type — the case-study archive.
 */
function zaaka_register_project() {
	register_post_type(
		'project',
		array(
			'labels'        => array(
				'name'               => __( 'Projects', 'zaaka-studio' ),
				'singular_name'      => __( 'Project', 'zaaka-studio' ),
				'add_new_item'       => __( 'Add project', 'zaaka-studio' ),
				'edit_item'          => __( 'Edit project', 'zaaka-studio' ),
				'all_items'          => __( 'All projects', 'zaaka-studio' ),
				'menu_name'          => __( 'Projects', 'zaaka-studio' ),
			),
			'public'        => true,
			'has_archive'   => 'work',
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 20,
			'rewrite'       => array( 'slug' => 'work', 'with_front' => false ),
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes' ),
			'show_in_rest'  => true,
			'template'      => array(
				array( 'core/paragraph', array( 'placeholder' => 'One line on what this project is.' ) ),
			),
		)
	);

	register_taxonomy(
		'discipline',
		array( 'project' ),
		array(
			'labels'            => array(
				'name'          => __( 'Disciplines', 'zaaka-studio' ),
				'singular_name' => __( 'Discipline', 'zaaka-studio' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'work/discipline' ),
		)
	);

	register_taxonomy(
		'sector',
		array( 'project' ),
		array(
			'labels'            => array(
				'name'          => __( 'Sectors', 'zaaka-studio' ),
				'singular_name' => __( 'Sector', 'zaaka-studio' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'work/sector' ),
		)
	);
}
add_action( 'init', 'zaaka_register_project' );

/**
 * Project meta, exposed to the block editor and the REST API so the fields can
 * be edited in the sidebar and rendered by blocks without a plugin.
 */
function zaaka_register_project_meta() {
	$fields = array(
		'zaaka_client'   => __( 'Client', 'zaaka-studio' ),
		'zaaka_role'     => __( 'Our role', 'zaaka-studio' ),
		'zaaka_year'     => __( 'Year', 'zaaka-studio' ),
		'zaaka_stack'    => __( 'Stack', 'zaaka-studio' ),
		'zaaka_url'      => __( 'Live URL', 'zaaka-studio' ),
		'zaaka_outcome'  => __( 'Headline outcome', 'zaaka-studio' ),
		'zaaka_status'   => __( 'Status', 'zaaka-studio' ),
	);

	foreach ( $fields as $key => $label ) {
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
add_action( 'init', 'zaaka_register_project_meta' );

/**
 * Inline an SVG from assets/img. Inlining rather than <img> means the mark
 * inherits `currentColor`, so one file serves the dark header and the light
 * sections, and it costs no extra request.
 *
 * @param string $name File name without extension.
 * @return string Sanitised SVG markup, or an empty string if missing.
 */
function zaaka_svg( $name ) {
	static $cache = array();

	$name = preg_replace( '/[^a-z0-9\-]/', '', (string) $name );
	if ( isset( $cache[ $name ] ) ) {
		return $cache[ $name ];
	}

	$file = get_theme_file_path( "assets/img/{$name}.svg" );
	if ( ! $name || ! file_exists( $file ) ) {
		return '';
	}

	$svg = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$cache[ $name ] = wp_kses( $svg, zaaka_svg_allowed_tags() );

	return $cache[ $name ];
}

/**
 * The SVG subset the theme's own marks need. Keeps wp_kses from stripping them
 * while still refusing script, style and event attributes.
 */
function zaaka_svg_allowed_tags() {
	return array(
		'svg'  => array(
			'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true,
			'fill' => true, 'x' => true, 'y' => true, 'overflow' => true,
			'role' => true, 'focusable' => true, 'aria-hidden' => true, 'aria-label' => true,
			'class' => true,
		),
		'g'    => array( 'fill' => true, 'transform' => true ),
		'path' => array( 'd' => true, 'fill' => true, 'fill-rule' => true, 'clip-rule' => true ),
		'rect' => array( 'width' => true, 'height' => true, 'rx' => true, 'fill' => true, 'x' => true, 'y' => true ),
	);
}

/**
 * Favicons. Skipped entirely if a Site Icon has been set in Settings, so the
 * admin choice always wins.
 */
function zaaka_favicons() {
	if ( has_site_icon() ) {
		return;
	}

	$img = get_theme_file_uri( 'assets/img' );

	printf( '<link rel="icon" href="%s" sizes="any">' . "\n", esc_url( $img . '/favicon-32.png' ) );
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( $img . '/favicon.svg' ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( $img . '/apple-touch-icon.png' ) );
	printf( '<meta name="theme-color" content="%s">' . "\n", '#07080A' );
}
add_action( 'wp_head', 'zaaka_favicons', 5 );

/**
 * Pattern category so the studio patterns group together in the inserter.
 */
function zaaka_pattern_categories() {
	register_block_pattern_category(
		'zaaka',
		array( 'label' => __( 'Zaaka', 'zaaka-studio' ) )
	);
}
add_action( 'init', 'zaaka_pattern_categories' );

/**
 * Block styles used by the patterns. Registering them means an editor can apply
 * the same treatments from the sidebar rather than pasting classes.
 */
function zaaka_block_styles() {
	register_block_style(
		'core/group',
		array(
			'name'  => 'card',
			'label' => __( 'Card', 'zaaka-studio' ),
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'rule-top',
			'label' => __( 'Rule above', 'zaaka-studio' ),
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'panel',
			'label' => __( 'Panel (dark)', 'zaaka-studio' ),
		)
	);
	register_block_style(
		'core/button',
		array(
			'name'  => 'ghost',
			'label' => __( 'Ghost', 'zaaka-studio' ),
		)
	);
	register_block_style(
		'core/list',
		array(
			'name'  => 'ticks',
			'label' => __( 'Ticks', 'zaaka-studio' ),
		)
	);
	register_block_style(
		'core/paragraph',
		array(
			'name'  => 'eyebrow',
			'label' => __( 'Eyebrow', 'zaaka-studio' ),
		)
	);
	register_block_style(
		'core/heading',
		array(
			'name'  => 'measure',
			'label' => __( 'Narrow measure', 'zaaka-studio' ),
		)
	);
}
add_action( 'init', 'zaaka_block_styles' );

/**
 * Make the project archive show everything; the grid is small and paginating
 * a studio's back catalogue helps nobody.
 */
function zaaka_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( is_post_type_archive( 'project' ) || is_tax( array( 'discipline', 'sector' ) ) ) {
		$query->set( 'posts_per_page', 24 );
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
	}
}
add_action( 'pre_get_posts', 'zaaka_archive_query' );

/**
 * Organization and WebSite schema. Editable values live in the customiser-free
 * site settings, so nothing here is hard-coded to one set of details.
 */
function zaaka_schema() {
	if ( ! is_front_page() ) {
		return;
	}

	$schema = array(
		'@context' => 'https://schema.org',
		'@graph'   => array(
			array(
				'@type'       => 'Organization',
				'@id'         => home_url( '/#organization' ),
				'name'        => get_bloginfo( 'name' ),
				'url'         => home_url( '/' ),
				'description' => get_bloginfo( 'description' ),
			),
			array(
				'@type'     => 'WebSite',
				'@id'       => home_url( '/#website' ),
				'url'       => home_url( '/' ),
				'name'      => get_bloginfo( 'name' ),
				'publisher' => array( '@id' => home_url( '/#organization' ) ),
			),
		),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
}
add_action( 'wp_head', 'zaaka_schema', 20 );

/**
 * A skip link that actually goes somewhere. WCAG 2.4.1.
 */
function zaaka_skip_link() {
	echo '<a class="zaaka-skip-link screen-reader-text" href="#wp--skip-link--target">' .
		esc_html__( 'Skip to content', 'zaaka-studio' ) . '</a>';
}
add_action( 'wp_body_open', 'zaaka_skip_link', 1 );
