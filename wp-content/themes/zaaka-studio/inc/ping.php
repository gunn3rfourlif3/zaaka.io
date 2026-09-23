<?php
/**
 * The standing invitation — a floating call to action pointing at /brief/.
 *
 * It sits where a chat widget would, because that is where people look for a
 * way to start a conversation. It is not a chat widget: it is a link, it opens
 * nothing, and it loads no third-party script. The studio replies to briefs
 * within a working day, which is an honest promise; a bot pretending to be
 * available now would not be.
 *
 * Printed on wp_footer rather than in a template part so it is not editable in
 * the site editor, where it would end up "Customised" in the database and stop
 * reflecting this file.
 *
 * @package Zaaka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Should the invitation appear on this request?
 *
 * Hidden anywhere it would be asking for something the page already asks for:
 * the brief itself, and Contact, which leads with the same button. Keyed on the
 * shortcode rather than the slug so that moving or renaming the brief page does
 * not leave a button floating over the form it points at.
 */
function zaaka_ping_should_show() {
	if ( is_admin() || is_feed() || is_embed() ) {
		return false;
	}

	if ( is_page( 'contact' ) ) {
		return false;
	}

	$post = get_post();
	if ( $post && has_shortcode( (string) $post->post_content, 'zaaka_brief' ) ) {
		return false;
	}

	/**
	 * Filter whether the floating brief invitation renders.
	 *
	 * @param bool $show Whether to show it.
	 */
	return (bool) apply_filters( 'zaaka_show_ping', true );
}

/**
 * Print it.
 *
 * A plain anchor: it works with CSS disabled, with JavaScript disabled, and it
 * is in the tab order in its natural place at the end of the document.
 */
function zaaka_ping() {
	if ( ! zaaka_ping_should_show() ) {
		return;
	}
	?>
	<a class="zk-ping" href="<?php echo esc_url( home_url( '/brief/' ) ); ?>">
		<span class="zk-ping__dot" aria-hidden="true"></span>
		<span class="zk-ping__label"><?php esc_html_e( 'Start a brief', 'zaaka-studio' ); ?></span>
		<span class="screen-reader-text"><?php esc_html_e( '— ten questions, about five minutes', 'zaaka-studio' ); ?></span>
	</a>
	<?php
}
add_action( 'wp_footer', 'zaaka_ping', 20 );
