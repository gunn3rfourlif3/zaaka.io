<?php
/**
 * Mail: make failures visible, and give the site a way to send properly.
 *
 * wp_mail() hands off to PHP's mail(), which on shared hosting frequently
 * accepts a message and drops it — no exception, no error, nothing in the
 * admin. A brief could be submitted, stored, and silently never reach anyone.
 * That is what happened here: the notification has existed since 1.6.0 and not
 * one has ever arrived.
 *
 * Three things live here.
 *
 * 1. Failures are captured and written onto the brief, so the admin says
 *    whether the mail left rather than leaving you to infer it from an inbox.
 * 2. Tools → Brief mail sends a test on demand and reports exactly what
 *    PHPMailer said, which separates "mail is broken" from "the form is broken".
 * 3. Optional authenticated SMTP, configured by constants in wp-config.php.
 *    That is the actual fix on this host: mail then leaves from the real
 *    mailbox, so SPF and DKIM align and it stops being treated as forgery.
 *
 * No credentials live in this file or anywhere else in the repo.
 *
 * @package Zaaka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remember the last mail error for the rest of the request.
 */
function zaaka_mail_last_error( $set = null ) {
	static $error = '';
	if ( null !== $set ) {
		$error = (string) $set;
	}
	return $error;
}

function zaaka_mail_record_failure( $wp_error ) {
	zaaka_mail_last_error( $wp_error->get_error_message() );
}
add_action( 'wp_mail_failed', 'zaaka_mail_record_failure' );

/**
 * Send through an authenticated mailbox when the site is configured for it.
 *
 * Put these in wp-config.php, above the "stop editing" line — never in the
 * theme, which is in version control and ships as a zip:
 *
 *   define( 'ZAAKA_SMTP_HOST', 'mail.zaaka.io' );
 *   define( 'ZAAKA_SMTP_PORT', 465 );
 *   define( 'ZAAKA_SMTP_SECURE', 'ssl' );      // 'ssl' for 465, 'tls' for 587
 *   define( 'ZAAKA_SMTP_USER', 'hello@zaaka.io' );
 *   define( 'ZAAKA_SMTP_PASS', 'the mailbox password' );
 *
 * With none of them set, nothing changes and mail goes out however it does now.
 */
function zaaka_mail_smtp( $phpmailer ) {
	if ( ! defined( 'ZAAKA_SMTP_HOST' ) || ! defined( 'ZAAKA_SMTP_USER' ) || ! defined( 'ZAAKA_SMTP_PASS' ) ) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host       = ZAAKA_SMTP_HOST;
	$phpmailer->SMTPAuth   = true;
	$phpmailer->Username   = ZAAKA_SMTP_USER;
	$phpmailer->Password   = ZAAKA_SMTP_PASS;
	$phpmailer->Port       = defined( 'ZAAKA_SMTP_PORT' ) ? (int) ZAAKA_SMTP_PORT : 465;
	$phpmailer->SMTPSecure = defined( 'ZAAKA_SMTP_SECURE' ) ? ZAAKA_SMTP_SECURE : 'ssl';

	// The envelope sender must be the authenticated mailbox or the receiving
	// server sees a mismatch between who sent it and who it claims to be from,
	// which is the definition of a failed SPF check.
	$phpmailer->setFrom( ZAAKA_SMTP_USER, get_bloginfo( 'name' ), false );
	$phpmailer->Sender = ZAAKA_SMTP_USER;
}
add_action( 'phpmailer_init', 'zaaka_mail_smtp', 5 );

function zaaka_mail_is_smtp() {
	return defined( 'ZAAKA_SMTP_HOST' ) && defined( 'ZAAKA_SMTP_USER' ) && defined( 'ZAAKA_SMTP_PASS' );
}

/**
 * Tools → Brief mail.
 */
function zaaka_mail_menu() {
	add_management_page(
		__( 'Brief mail', 'zaaka-studio' ),
		__( 'Brief mail', 'zaaka-studio' ),
		'manage_options',
		'zaaka-mail',
		'zaaka_mail_page'
	);
}
add_action( 'admin_menu', 'zaaka_mail_menu' );

function zaaka_mail_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$result = null;
	if ( isset( $_POST['zaaka_mail_test'] ) && check_admin_referer( 'zaaka_mail_test' ) ) {
		$to = sanitize_email( wp_unslash( $_POST['zaaka_mail_to'] ?? '' ) );
		if ( ! is_email( $to ) ) {
			$to = zaaka_brief_email();
		}
		zaaka_mail_last_error( '' );
		$sent   = wp_mail(
			$to,
			__( 'Zaaka mail test', 'zaaka-studio' ),
			"This is a test from Tools \u{2192} Brief mail.\n\nIf it arrived, wp_mail() works and the brief will reach you.",
			array( 'Content-Type: text/plain; charset=UTF-8' )
		);
		$result = array( 'to' => $to, 'sent' => $sent, 'error' => zaaka_mail_last_error() );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Brief mail', 'zaaka-studio' ); ?></h1>

		<?php if ( $result ) : ?>
			<div class="notice notice-<?php echo $result['sent'] ? 'success' : 'error'; ?>">
				<p>
					<strong><?php echo $result['sent']
						? esc_html__( 'wp_mail() accepted the message.', 'zaaka-studio' )
						: esc_html__( 'wp_mail() refused the message.', 'zaaka-studio' ); ?></strong>
					<?php printf( esc_html__( 'Sent to %s.', 'zaaka-studio' ), '<code>' . esc_html( $result['to'] ) . '</code>' ); // phpcs:ignore ?>
				</p>
				<?php if ( $result['error'] ) : ?>
					<p><?php esc_html_e( 'PHPMailer said:', 'zaaka-studio' ); ?>
						<code><?php echo esc_html( $result['error'] ); ?></code></p>
				<?php endif; ?>
				<?php if ( $result['sent'] ) : ?>
					<p class="description"><?php esc_html_e( 'Accepted is not the same as delivered. If nothing arrives — check spam first — the host took the message and dropped it, which is what SMTP below fixes.', 'zaaka-studio' ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<table class="widefat striped" style="max-width:52rem">
			<tbody>
			<tr><th style="width:16rem"><?php esc_html_e( 'Sending method', 'zaaka-studio' ); ?></th>
				<td><?php echo zaaka_mail_is_smtp()
					? '<strong>' . esc_html__( 'Authenticated SMTP', 'zaaka-studio' ) . '</strong> — ' . esc_html( ZAAKA_SMTP_USER ) . ' via ' . esc_html( ZAAKA_SMTP_HOST )
					: '<strong>' . esc_html__( 'PHP mail()', 'zaaka-studio' ) . '</strong> — ' . esc_html__( 'the host default, and the usual reason nothing arrives', 'zaaka-studio' ); ?></td></tr>
			<tr><th><?php esc_html_e( 'Briefs go to', 'zaaka-studio' ); ?></th>
				<td><code><?php echo esc_html( zaaka_brief_email() ); ?></code></td></tr>
			<tr><th><?php esc_html_e( 'Briefs stored', 'zaaka-studio' ); ?></th>
				<td><?php
					$count = wp_count_posts( 'brief' );
					printf( esc_html__( '%d received', 'zaaka-studio' ), (int) ( $count->private ?? 0 ) + (int) ( $count->publish ?? 0 ) );
				?> — <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=brief' ) ); ?>"><?php esc_html_e( 'open them', 'zaaka-studio' ); ?></a>
				<p class="description"><?php esc_html_e( 'If this number went up when you tested, the form works and only the mail is failing.', 'zaaka-studio' ); ?></p></td></tr>
			</tbody>
		</table>

		<form method="post" style="margin-top:1.5rem">
			<?php wp_nonce_field( 'zaaka_mail_test' ); ?>
			<p>
				<label for="zaaka_mail_to"><?php esc_html_e( 'Send a test to', 'zaaka-studio' ); ?></label><br>
				<input type="email" id="zaaka_mail_to" name="zaaka_mail_to" class="regular-text code"
					value="<?php echo esc_attr( zaaka_brief_email() ); ?>">
			</p>
			<p><button type="submit" name="zaaka_mail_test" value="1" class="button button-primary">
				<?php esc_html_e( 'Send a test', 'zaaka-studio' ); ?></button></p>
		</form>

		<?php if ( ! zaaka_mail_is_smtp() ) : ?>
		<h2><?php esc_html_e( 'Sending properly', 'zaaka-studio' ); ?></h2>
		<p><?php esc_html_e( 'Add these to wp-config.php, above the "stop editing" line, using a real cPanel mailbox. Mail then leaves from that mailbox, so SPF and DKIM line up and it stops looking like forgery. Never put them in the theme.', 'zaaka-studio' ); ?></p>
		<pre style="background:#1d2327;color:#f0f0f1;padding:1rem;max-width:52rem;overflow:auto">define( 'ZAAKA_SMTP_HOST', 'mail.zaaka.io' );
define( 'ZAAKA_SMTP_PORT', 465 );
define( 'ZAAKA_SMTP_SECURE', 'ssl' );
define( 'ZAAKA_SMTP_USER', 'hello@zaaka.io' );
define( 'ZAAKA_SMTP_PASS', '&lt;the mailbox password&gt;' );</pre>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Who the mail is from.
 *
 * WordPress defaults to "WordPress" <wordpress@yourdomain>, which is what the
 * brief was arriving as. The name is the site's own, and the address is the
 * mailbox briefs already go to — so a reply from a recipient reaches a real
 * inbox, and the sending domain matches the From domain, which is what SPF
 * checks. With SMTP configured, zaaka_mail_smtp() sets the authenticated
 * mailbox instead and wins, because it runs on phpmailer_init after these.
 */
function zaaka_mail_from_name() {
	$name = get_bloginfo( 'name' );
	return $name ? $name : 'Zaaka.io';
}
add_filter( 'wp_mail_from_name', 'zaaka_mail_from_name' );

function zaaka_mail_from( $from ) {
	$address = zaaka_brief_email();
	return is_email( $address ) ? $address : $from;
}
add_filter( 'wp_mail_from', 'zaaka_mail_from' );
