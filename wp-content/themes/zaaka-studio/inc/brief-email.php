<?php
/**
 * The brief emails.
 *
 * Two go out per submission, from one template: a copy to the person who filled
 * it in, and a notification to the studio. The visitor's copy is not a nicety —
 * the confirmation screen promises it ("a copy is on its way to you"), and the
 * Discovery Sprint page makes a point of the brief being theirs to take
 * anywhere. A promise made on screen and not kept by the mailer is worse than
 * not making it.
 *
 * Written to email's constraints rather than the site's: tables for layout,
 * every style inline, no web fonts, no images. Images are the interesting one —
 * SVG does not render in most email clients and a remote PNG is blocked until
 * the reader clicks "show images", so the wordmark here is set as type. It
 * costs nothing and it always arrives.
 *
 * Both messages are sent multipart, HTML with a plain-text alternative. Some
 * people read mail as text, and a brief is information rather than decoration.
 *
 * @package Zaaka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ZAAKA_MAIL_VOID     = '#07080A';
const ZAAKA_MAIL_SURFACE  = '#171B23';
const ZAAKA_MAIL_HAIRLINE = '#2C3341';
const ZAAKA_MAIL_PAPER    = '#FFFFFF';
const ZAAKA_MAIL_MIST     = '#A7B0C0';
const ZAAKA_MAIL_VOLT     = '#CBF94C';
const ZAAKA_MAIL_FONT     = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif";

/**
 * Labels are written from the studio's side of the desk, which reads wrong in
 * the copy that goes back to the person who answered: "Their time per week" is
 * their own answer being described to them in the third person. Only the ones
 * that actually change are listed.
 */
function zaaka_brief_email_label( $key, $label, $is_client ) {
	if ( ! $is_client ) {
		return $label;
	}
	$theirs = array(
		'clienttime' => __( 'Your time each week', 'zaaka-studio' ),
		'owner'      => __( 'Who owns it today', 'zaaka-studio' ),
		'metric'     => __( 'What success looks like to you', 'zaaka-studio' ),
	);
	return isset( $theirs[ $key ] ) ? $theirs[ $key ] : $label;
}

/**
 * One answered question, as a labelled row.
 */
function zaaka_brief_email_row( $label, $value ) {
	return sprintf(
		'<tr><td style="padding:16px 0;border-bottom:1px solid %1$s;">'
		. '<div style="font-family:%2$s;font-size:11px;line-height:1.4;font-weight:700;'
		. 'letter-spacing:.12em;text-transform:uppercase;color:%3$s;padding-bottom:7px;">%4$s</div>'
		. '<div style="font-family:%2$s;font-size:16px;line-height:1.55;color:%5$s;">%6$s</div>'
		. '</td></tr>',
		ZAAKA_MAIL_HAIRLINE,
		ZAAKA_MAIL_FONT,
		ZAAKA_MAIL_MIST,
		esc_html( $label ),
		ZAAKA_MAIL_PAPER,
		nl2br( esc_html( $value ) )
	);
}

/**
 * A button that survives Outlook: a table cell with padding, not a styled <a>.
 */
function zaaka_brief_email_button( $url, $label ) {
	return sprintf(
		'<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:28px 0 4px;">'
		. '<tr><td bgcolor="%1$s" style="border-radius:999px;">'
		. '<a href="%2$s" style="display:inline-block;padding:15px 30px;font-family:%3$s;'
		. 'font-size:15px;font-weight:700;letter-spacing:-.01em;color:%4$s;text-decoration:none;'
		. 'border-radius:999px;">%5$s</a>'
		. '</td></tr></table>',
		ZAAKA_MAIL_VOLT,
		esc_url( $url ),
		ZAAKA_MAIL_FONT,
		ZAAKA_MAIL_VOID,
		esc_html( $label )
	);
}

/**
 * Build the message.
 *
 * @param array  $values   Cleaned submission.
 * @param int    $post_id  Stored brief, or 0.
 * @param string $audience 'client' or 'studio'.
 */
function zaaka_brief_email_html( $values, $post_id, $audience = 'client' ) {
	$is_client = ( 'client' === $audience );
	$who       = $values['company'] ? $values['company'] : $values['name'];

	$eyebrow = $is_client ? __( 'Your brief', 'zaaka-studio' ) : __( 'New brief', 'zaaka-studio' );
	$first   = trim( strtok( (string) $values['name'], ' ' ) );
	$title   = $is_client
		? ( $first
			/* translators: %s: the person's first name. */
			? sprintf( __( 'Here is your brief, %s.', 'zaaka-studio' ), $first )
			: __( 'Here is your brief.', 'zaaka-studio' ) )
		: ( $who ? $who : __( 'A new brief', 'zaaka-studio' ) );

	$intro = $is_client
		? __( 'Thank you — this is what you told us, in your own words. It is yours: take it to us, to another studio, or to your own team. We will read it properly and reply within one working day with what we think you are actually buying, which is occasionally different from what was asked for.', 'zaaka-studio' )
		: sprintf(
			/* translators: %s: submission time. */
			__( 'Received %s. Reply to this message and it goes straight back to them.', 'zaaka-studio' ),
			current_time( 'j F Y, H:i' )
		);

	$rows = '';
	foreach ( zaaka_brief_fields() as $key => $field ) {
		if ( '' === $values[ $key ] ) {
			continue;
		}
		// The studio's copy repeats their contact details at the end; the
		// visitor already knows their own name and email.
		if ( $is_client && in_array( $key, array( 'name', 'company', 'email' ), true ) ) {
			continue;
		}
		$rows .= zaaka_brief_email_row( zaaka_brief_email_label( $key, $field['label'], $is_client ), $values[ $key ] );
	}

	$cta = '';
	if ( $is_client ) {
		$cta = zaaka_brief_email_button( home_url( '/book/' ), __( 'Book an intro call', 'zaaka-studio' ) )
			. sprintf(
				'<p style="font-family:%1$s;font-size:14px;line-height:1.6;color:%2$s;margin:14px 0 0;">%3$s</p>',
				ZAAKA_MAIL_FONT,
				ZAAKA_MAIL_MIST,
				esc_html__( 'Or just reply to this email. A paragraph is a perfectly good start.', 'zaaka-studio' )
			);
	} elseif ( $post_id ) {
		$cta = zaaka_brief_email_button( get_edit_post_link( $post_id, '' ), __( 'Open it in the admin', 'zaaka-studio' ) );
	}

	$preheader = $is_client
		? __( 'A copy of the brief you just sent us.', 'zaaka-studio' )
		: sprintf( /* translators: %s: name. */ __( 'From %s', 'zaaka-studio' ), $who );

	ob_start();
	?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="dark">
<title><?php echo esc_html( $title ); ?></title>
</head>
<body style="margin:0;padding:0;background-color:<?php echo ZAAKA_MAIL_VOID; ?>;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;"><?php echo esc_html( $preheader ); ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:<?php echo ZAAKA_MAIL_VOID; ?>;">
<tr><td align="center" style="padding:32px 16px;">

<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;">

<tr><td style="padding-bottom:22px;">
	<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
	<td style="width:26px;"><div style="height:2px;background-color:<?php echo ZAAKA_MAIL_VOLT; ?>;font-size:0;line-height:0;">&nbsp;</div></td>
	<td style="padding-left:10px;font-family:<?php echo ZAAKA_MAIL_FONT; ?>;font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:<?php echo ZAAKA_MAIL_MIST; ?>;">Zaaka.io</td>
	</tr></table>
</td></tr>

<tr><td style="background-color:<?php echo ZAAKA_MAIL_SURFACE; ?>;border:1px solid <?php echo ZAAKA_MAIL_HAIRLINE; ?>;border-radius:14px;padding:34px 30px;">

	<div style="font-family:<?php echo ZAAKA_MAIL_FONT; ?>;font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:<?php echo ZAAKA_MAIL_VOLT; ?>;padding-bottom:12px;"><?php echo esc_html( $eyebrow ); ?></div>

	<h1 style="margin:0 0 14px;font-family:<?php echo ZAAKA_MAIL_FONT; ?>;font-size:30px;line-height:1.15;font-weight:700;letter-spacing:-.02em;color:<?php echo ZAAKA_MAIL_PAPER; ?>;"><?php echo esc_html( $title ); ?></h1>

	<p style="margin:0 0 6px;font-family:<?php echo ZAAKA_MAIL_FONT; ?>;font-size:15px;line-height:1.65;color:<?php echo ZAAKA_MAIL_MIST; ?>;"><?php echo esc_html( $intro ); ?></p>

	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:20px;"><?php echo $rows; // phpcs:ignore WordPress.Security.EscapeOutput ?></table>

	<?php echo $cta; // phpcs:ignore WordPress.Security.EscapeOutput ?>
</td></tr>

<?php if ( $is_client ) : ?>
<tr><td style="padding:22px 30px 0;font-family:<?php echo ZAAKA_MAIL_FONT; ?>;font-size:13px;line-height:1.7;color:<?php echo ZAAKA_MAIL_MIST; ?>;">
	<?php esc_html_e( 'Zaaka — a design and engineering studio in Johannesburg. We build the product, and the site that sells it.', 'zaaka-studio' ); ?><br>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:<?php echo ZAAKA_MAIL_VOLT; ?>;text-decoration:none;">zaaka.io</a>
	&nbsp;·&nbsp;
	<a href="mailto:<?php echo esc_attr( zaaka_brief_email() ); ?>" style="color:<?php echo ZAAKA_MAIL_VOLT; ?>;text-decoration:none;"><?php echo esc_html( zaaka_brief_email() ); ?></a>
</td></tr>
<?php endif; ?>

</table>
</td></tr></table>
</body></html>
	<?php
	return ob_get_clean();
}

/**
 * The plain-text alternative. Not a fallback nobody reads — it is what a text
 * client, a screen reader in text mode and most "quote the original" replies
 * will actually show.
 */
function zaaka_brief_email_text( $values, $post_id, $audience = 'client' ) {
	$is_client = ( 'client' === $audience );
	$out       = array();

	$out[] = $is_client ? 'YOUR BRIEF' : 'NEW BRIEF';
	$out[] = str_repeat( '=', 40 );
	$out[] = '';

	if ( $is_client ) {
		$out[] = 'This is what you told us, in your own words. It is yours to take anywhere.';
		$out[] = 'We reply to everything, usually within one working day.';
	} else {
		$out[] = 'Received ' . current_time( 'j F Y, H:i' ) . '. Reply to reach them directly.';
	}
	$out[] = '';

	foreach ( zaaka_brief_fields() as $key => $field ) {
		if ( '' === $values[ $key ] ) {
			continue;
		}
		if ( $is_client && in_array( $key, array( 'name', 'company', 'email' ), true ) ) {
			continue;
		}
		$out[] = strtoupper( zaaka_brief_email_label( $key, $field['label'], $is_client ) );
		$out[] = $values[ $key ];
		$out[] = '';
	}

	if ( $is_client ) {
		$out[] = 'Book an intro call: ' . home_url( '/book/' );
		$out[] = 'Or just reply to this email. A paragraph is a perfectly good start.';
		$out[] = '';
		$out[] = 'Zaaka — ' . home_url( '/' );
	} elseif ( $post_id ) {
		$out[] = 'In the admin: ' . get_edit_post_link( $post_id, '' );
	}

	return implode( "\n", $out );
}

/**
 * Send one multipart message.
 *
 * wp_mail sends whatever Content-Type it is given and nothing else, so the
 * plain-text part has to be attached to PHPMailer directly. The hook is removed
 * again immediately — left on, it would stamp this brief's text onto every
 * other email the site sends for the rest of the request.
 */
function zaaka_brief_send( $to, $subject, $html, $text, $headers = array() ) {
	$attach_text = static function ( $phpmailer ) use ( $text ) {
		$phpmailer->AltBody = $text;
	};

	add_action( 'phpmailer_init', $attach_text );
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	$sent      = wp_mail( $to, $subject, $html, $headers );
	remove_action( 'phpmailer_init', $attach_text );

	return $sent;
}
