<?php
/**
 * Title: Logo
 * Slug: zaaka/logo
 * Categories: zaaka
 * Description: The Zaaka wordmark, linked home.
 * Inserter: no
 */
?>
<!-- wp:html -->
<a class="zk-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
	<?php echo zaaka_svg( 'logo-wordmark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<span class="screen-reader-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?> — home</span>
</a>
<!-- /wp:html -->
