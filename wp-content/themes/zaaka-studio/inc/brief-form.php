<?php
/**
 * The Brief Builder form markup.
 *
 * Rendered by a shortcode rather than pasted into the page as raw HTML. The
 * theme's static pages already suffer from copy living in the database and
 * drifting from content/pages/*.html; a form with nineteen fields and a nonce
 * is the last thing that should be editable by accident in the block editor.
 * One [zaaka_brief] in the page, the markup versioned here.
 *
 * Every control is a real control. Single-choice groups are radios inside a
 * label, so the chip styling is `:has(:checked)` and keyboard support is free;
 * scope, time and budget are selects that assets/js/brief.js upgrades into
 * chips and sliders. With JavaScript off every step is visible and the whole
 * thing submits in one POST.
 *
 * @package Zaaka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A single-choice chip group.
 *
 * @param string $name    Field name, without the zk_ prefix.
 * @param array  $options value => label.
 * @param string $legend  Visible question for the group.
 */
function zaaka_brief_chips( $name, $options, $legend = '' ) {
	$out = '<fieldset class="zk-chipset">';
	if ( $legend ) {
		$out .= '<legend class="zk-sub">' . esc_html( $legend ) . '</legend>';
	}
	$out .= '<div class="zk-brief-chips">';
	foreach ( $options as $value => $label ) {
		$out .= sprintf(
			'<label class="zk-chip"><input type="radio" name="zk_%1$s" value="%2$s" class="zk-sr"> <span>%3$s</span></label>',
			esc_attr( $name ),
			esc_attr( $value ),
			esc_html( $label )
		);
	}
	return $out . '</div></fieldset>';
}

/**
 * A select. JS turns the two scaled ones (time, budget) into sliders; without
 * it they stay perfectly usable selects, which is the whole point.
 */
function zaaka_brief_select( $name, $label, $options, $default = '' ) {
	$out = sprintf(
		'<label class="zk-field"><span class="zk-label">%s</span><select class="zk-input" name="zk_%s" id="zk-%s">',
		esc_html( $label ),
		esc_attr( $name ),
		esc_attr( $name )
	);
	foreach ( $options as $value => $text ) {
		$out .= sprintf(
			'<option value="%s"%s>%s</option>',
			esc_attr( $value ),
			selected( $value, $default, false ),
			esc_html( $text )
		);
	}
	return $out . '</select></label>';
}

function zaaka_brief_text( $name, $label, $placeholder = '', $type = 'text' ) {
	return sprintf(
		'<label class="zk-field"><span class="zk-label">%s</span><input class="zk-input" type="%s" name="zk_%s" id="zk-%s" placeholder="%s"></label>',
		esc_html( $label ),
		esc_attr( $type ),
		esc_attr( $name ),
		esc_attr( $name ),
		esc_attr( $placeholder )
	);
}

/**
 * Scope options per route. The generic list is what renders server-side and
 * what a no-JavaScript visitor answers; brief.js swaps in the route-specific
 * list once a route is chosen.
 */
function zaaka_brief_scope_options() {
	return array(
		'product'   => array( 'Accounts & sign-in', 'Payments or billing', 'Dashboard & reporting', 'Notifications', 'Admin back office', 'Mobile app', 'Public API', 'Search' ),
		'site'      => array( 'Content pages', 'Insights or blog', 'Lead capture', 'Online store', 'Bookings', 'Multi-language', 'CMS training', 'Technical SEO' ),
		'rescue'    => array( 'Speed', 'Move hosting', 'Security fixes', 'Redesign', 'Rebuild on a new stack', 'Content migration', 'Accessibility', 'Handover & docs' ),
		'integrate' => array( 'Payment gateway', 'Accounting (Xero/Sage)', 'CRM', 'Email & marketing', 'Calendar', 'ID verification', 'Document generation', 'Reporting pipeline' ),
		'generic'   => array( 'Accounts & sign-in', 'Payments or billing', 'Content pages', 'Reporting', 'Integrations', 'Mobile app', 'Admin back office', 'Technical SEO' ),
	);
}

/**
 * The form.
 */
function zaaka_brief_form() {
	$status = isset( $_GET['brief'] ) ? sanitize_key( wp_unslash( $_GET['brief'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

	ob_start();

	if ( 'sent' === $status ) {
		?>
		<div class="zk-brief-wrap zk-dark"><div class="zk-brief-done" id="brief">
			<p class="is-style-eyebrow"><?php esc_html_e( 'Received', 'zaaka-studio' ); ?></p>
			<h2 class="zk-q"><?php esc_html_e( 'That is your brief, and it is with us.', 'zaaka-studio' ); ?></h2>
			<p class="zk-help">
				<?php esc_html_e( 'We read these properly and reply within one working day — with what we think you are actually buying, which is occasionally different from what you asked for. A copy is on its way to you either way; it is yours to take anywhere.', 'zaaka-studio' ); ?>
			</p>
			<p><a class="zk-btn" href="<?php echo esc_url( home_url( '/book/' ) ); ?>"><?php esc_html_e( 'Book the call now', 'zaaka-studio' ); ?></a></p>
		</div></div>
		<?php
		return ob_get_clean();
	}

	$routes = array(
		'product'   => array( 'A new product or platform', 'Does not exist yet. Might not have a spec yet either.' ),
		'site'      => array( 'A site that sells something that exists', 'The business is real; the website is not pulling its weight.' ),
		'rescue'    => array( 'Something built that has stopped keeping up', 'Slow, broken, insecure, or outgrown by the business.' ),
		'integrate' => array( 'Systems that do not talk to each other', 'An integration, an automation, or a spreadsheet doing a system\'s job.' ),
		'unsure'    => array( 'Not sure yet', 'Perfectly good answer. We will ask the general version.' ),
	);

	$scope = zaaka_brief_scope_options();
	?>
	<div class="zk-brief-wrap zk-dark" id="brief">

		<?php if ( 'email' === $status ) : ?>
			<p class="zk-notice" role="alert"><?php esc_html_e( 'We need an email address to reply to — everything else is optional.', 'zaaka-studio' ); ?></p>
		<?php elseif ( 'expired' === $status ) : ?>
			<p class="zk-notice" role="alert"><?php esc_html_e( 'That form had been open a while and the security token expired. Nothing was lost — send it again.', 'zaaka-studio' ); ?></p>
		<?php endif; ?>

		<div class="zk-progress" aria-hidden="true"><div class="zk-progress__bar"></div></div>

		<form class="zk-brief-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="zaaka_brief">
			<input type="hidden" name="zaaka_started" value="<?php echo esc_attr( time() ); ?>">
			<?php wp_nonce_field( 'zaaka_brief', 'zaaka_brief_nonce' ); ?>

			<p class="zk-hp" aria-hidden="true">
				<label><?php esc_html_e( 'Leave this field empty', 'zaaka-studio' ); ?>
					<input type="text" name="zaaka_website" tabindex="-1" autocomplete="off"></label>
			</p>

			<div class="zk-brief-grid">
				<div class="zk-brief-main">

					<fieldset class="zk-brief-step" data-step="1">
						<legend class="zk-q"><?php esc_html_e( 'What are we building?', 'zaaka-studio' ); ?></legend>
						<p class="zk-help"><?php esc_html_e( 'Pick the closest one. It decides which of the next questions you actually see — most people skip about a third of them.', 'zaaka-studio' ); ?></p>
						<div class="zk-routes">
							<?php $n = 0; foreach ( $routes as $key => $r ) : $n++; ?>
								<label class="zk-route">
									<input type="radio" name="zk_route" value="<?php echo esc_attr( $r[0] ); ?>" data-route="<?php echo esc_attr( $key ); ?>" class="zk-sr">
									<span class="zk-route__n"><?php echo esc_html( str_pad( (string) $n, 2, '0', STR_PAD_LEFT ) ); ?></span>
									<span><span class="zk-route__t"><?php echo esc_html( $r[0] ); ?></span>
									<span class="zk-route__d"><?php echo esc_html( $r[1] ); ?></span></span>
								</label>
							<?php endforeach; ?>
						</div>
					</fieldset>

					<fieldset class="zk-brief-step" data-step="2">
						<legend class="zk-q"><?php esc_html_e( 'What is going wrong now?', 'zaaka-studio' ); ?></legend>
						<p class="zk-help"><?php esc_html_e( 'The problem, not the solution. What is actually happening today is more useful to us than what you think should be built.', 'zaaka-studio' ); ?></p>
						<label class="zk-field"><span class="zk-label"><?php esc_html_e( 'The problem', 'zaaka-studio' ); ?></span>
							<textarea class="zk-textarea" name="zk_problem" id="zk-problem" rows="4" placeholder="<?php esc_attr_e( 'Orders come in by WhatsApp and get copied into a spreadsheet by hand. We lose about one a week.', 'zaaka-studio' ); ?>"></textarea></label>
						<?php
						echo zaaka_brief_chips( // phpcs:ignore WordPress.Security.EscapeOutput
							'donothing',
							array(
								'it gets materially worse'      => 'It gets materially worse',
								'we cope, but it costs us'      => 'We cope, but it costs us',
								'we lose a contract or a client'=> 'We lose a contract or a client',
								'honestly, not much'            => 'Honestly, not much',
							),
							__( 'Leave it alone for twelve months. What happens?', 'zaaka-studio' )
						);
						?>
					</fieldset>

					<fieldset class="zk-brief-step" data-step="3">
						<legend class="zk-q"><?php esc_html_e( 'Who is it for?', 'zaaka-studio' ); ?></legend>
						<p class="zk-help"><?php esc_html_e( 'Who sits in front of this thing, and roughly how many of them.', 'zaaka-studio' ); ?></p>
						<?php
						echo zaaka_brief_chips( // phpcs:ignore WordPress.Security.EscapeOutput
							'audience',
							array(
								'your own team'    => 'Our own team',
								'your customers'   => 'Our customers',
								'other businesses' => 'Other businesses',
								'the public'       => 'The public',
							)
						);
						?>
						<div class="zk-row">
							<?php
							echo zaaka_brief_text( 'count', __( 'Roughly how many', 'zaaka-studio' ), '400' ); // phpcs:ignore WordPress.Security.EscapeOutput
							echo zaaka_brief_text( 'trend', __( 'Growing, flat or shrinking?', 'zaaka-studio' ), __( 'Growing, about 15% a year', 'zaaka-studio' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
							?>
						</div>
					</fieldset>

					<fieldset class="zk-brief-step" data-step="4">
						<legend class="zk-q"><?php esc_html_e( 'How does this make or save money?', 'zaaka-studio' ); ?></legend>
						<p class="zk-help"><?php esc_html_e( 'The question most briefs never answer. It changes what we would build first more than any feature list does.', 'zaaka-studio' ); ?></p>
						<?php
						echo zaaka_brief_chips( // phpcs:ignore WordPress.Security.EscapeOutput
							'money',
							array(
								'a subscription'                 => 'A subscription',
								'a cut of each transaction'      => 'A cut of each transaction',
								'one-off sales'                  => 'One-off sales',
								'leads it brings in'             => 'Leads it brings in',
								'internal time it gives back'    => 'Internal time it gives back',
								'nothing directly'               => 'Nothing directly',
							)
						);
						echo zaaka_brief_text( 'metric', __( 'What number would tell you it worked?', 'zaaka-studio' ), __( '200 paying users — or 10 hours a week back', 'zaaka-studio' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</fieldset>

					<fieldset class="zk-brief-step" data-step="5">
						<legend class="zk-q"><?php esc_html_e( 'What exists already?', 'zaaka-studio' ); ?></legend>
						<p class="zk-help"><?php esc_html_e( 'Including the parts you are not proud of. A spreadsheet doing a system\'s job counts, and tells us a lot.', 'zaaka-studio' ); ?></p>
						<?php
						echo zaaka_brief_text( 'url', __( 'Site or system, if there is one', 'zaaka-studio' ), 'example.co.za' ); // phpcs:ignore WordPress.Security.EscapeOutput
						echo zaaka_brief_chips( // phpcs:ignore WordPress.Security.EscapeOutput
							'owner',
							array(
								'you own all of it outright'                => 'We own all of it',
								'the previous developer still holds it'     => 'Our old developer holds it',
								'ownership is unclear'                      => 'Honestly, not sure',
								'nothing exists yet'                        => 'Nothing exists yet',
							),
							__( 'Who holds the domain, the hosting and the code today?', 'zaaka-studio' )
						);
						?>
					</fieldset>

					<fieldset class="zk-brief-step" data-step="6">
						<legend class="zk-q"><?php esc_html_e( 'What has to be in the first release?', 'zaaka-studio' ); ?></legend>
						<p class="zk-help"><?php esc_html_e( 'Anything you leave as not needed, we will assume is out of scope for now.', 'zaaka-studio' ); ?></p>
						<div class="zk-scope" data-scope-generic="<?php echo esc_attr( wp_json_encode( $scope ) ); ?>">
							<?php foreach ( $scope['generic'] as $item ) : ?>
								<label class="zk-field zk-scope__row">
									<span class="zk-label"><?php echo esc_html( $item ); ?></span>
									<select class="zk-input" name="zk_scope[<?php echo esc_attr( $item ); ?>]">
										<option value=""><?php esc_html_e( 'Not needed', 'zaaka-studio' ); ?></option>
										<option value="must"><?php esc_html_e( 'Must have', 'zaaka-studio' ); ?></option>
										<option value="later"><?php esc_html_e( 'Can wait', 'zaaka-studio' ); ?></option>
									</select>
								</label>
							<?php endforeach; ?>
						</div>
					</fieldset>

					<fieldset class="zk-brief-step" data-step="7">
						<legend class="zk-q"><?php esc_html_e( 'When does it need to be live?', 'zaaka-studio' ); ?></legend>
						<p class="zk-help"><?php esc_html_e( 'We will tell you straight away what we think of the date.', 'zaaka-studio' ); ?></p>
						<?php
						echo zaaka_brief_select( // phpcs:ignore WordPress.Security.EscapeOutput
							'timeline',
							__( 'Live by', 'zaaka-studio' ),
							array(
								'about four weeks'      => '4 weeks',
								'about two months'      => 'About 2 months',
								'about three months'    => 'About 3 months',
								'about six months'      => 'About 6 months',
								'nine months or more'   => '9 months or more',
								'no fixed date'         => 'No fixed date',
							),
							'about three months'
						);
						?>
						<div class="zk-react" data-react="timeline" aria-live="polite"></div>
						<?php
						echo zaaka_brief_chips( // phpcs:ignore WordPress.Security.EscapeOutput
							'datereal',
							array(
								'the date is real — something depends on it' => 'Real — something depends on it',
								'the date is aspirational'                   => 'Aspirational',
							),
							__( 'Is that date real, or aspirational?', 'zaaka-studio' )
						);
						?>
					</fieldset>

					<fieldset class="zk-brief-step" data-step="8">
						<legend class="zk-q"><?php esc_html_e( 'What is the budget?', 'zaaka-studio' ); ?></legend>
						<p class="zk-help"><?php esc_html_e( 'Not so we can spend it. So we can tell you honestly whether the thing you have described can be built for it.', 'zaaka-studio' ); ?></p>
						<?php
						echo zaaka_brief_select( // phpcs:ignore WordPress.Security.EscapeOutput
							'budget',
							__( 'Budget band', 'zaaka-studio' ),
							array(
								'under R50,000'           => 'Under R50,000',
								'R50,000 to R150,000'     => 'R50,000 – R150,000',
								'R150,000 to R400,000'    => 'R150,000 – R400,000',
								'over R400,000'           => 'R400,000+',
								'not yet decided'         => 'No idea yet',
							),
							'R150,000 to R400,000'
						);
						?>
						<div class="zk-react" data-react="budget" aria-live="polite"></div>
					</fieldset>

					<fieldset class="zk-brief-step" data-step="9">
						<legend class="zk-q"><?php esc_html_e( 'Who signs it off?', 'zaaka-studio' ); ?></legend>
						<p class="zk-help"><?php esc_html_e( 'The single best predictor of whether a project goes smoothly. We ask everyone.', 'zaaka-studio' ); ?></p>
						<?php
						echo zaaka_brief_chips( // phpcs:ignore WordPress.Security.EscapeOutput
							'decider',
							array(
								'you decide alone'              => 'Me, alone',
								'you and one partner decide'    => 'Me and one partner',
								'a committee decides'           => 'A committee',
								'it needs board sign-off'       => 'It needs board sign-off',
							)
						);
						echo zaaka_brief_chips( // phpcs:ignore WordPress.Security.EscapeOutput
							'clienttime',
							array(
								'a couple of hours a week'  => 'A couple of hours',
								'about a day a week'        => 'About a day',
								'several days a week'       => 'Several days',
							),
							__( 'How much of your own time can you give it each week?', 'zaaka-studio' )
						);
						?>
						<div class="zk-react" data-react="clienttime" aria-live="polite"></div>
					</fieldset>

					<fieldset class="zk-brief-step" data-step="10">
						<legend class="zk-q"><?php esc_html_e( 'Last bit — who are you?', 'zaaka-studio' ); ?></legend>
						<p class="zk-help"><?php esc_html_e( 'We reply to everything, usually within one working day.', 'zaaka-studio' ); ?></p>
						<div class="zk-row">
							<?php
							echo zaaka_brief_text( 'name', __( 'Your name', 'zaaka-studio' ), 'Thandi Mokoena' ); // phpcs:ignore WordPress.Security.EscapeOutput
							echo zaaka_brief_text( 'company', __( 'Company', 'zaaka-studio' ), 'Mokoena Property Group' ); // phpcs:ignore WordPress.Security.EscapeOutput
							?>
						</div>
						<?php echo zaaka_brief_text( 'email', __( 'Email', 'zaaka-studio' ), 'thandi@example.co.za', 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<p class="zk-fineprint"><?php esc_html_e( 'The only field we insist on. Everything above is optional — a half-finished brief is still worth sending.', 'zaaka-studio' ); ?></p>
					</fieldset>

					<div class="zk-nav">
						<button type="button" class="zk-btn zk-btn--ghost" data-nav="back" hidden><?php esc_html_e( 'Back', 'zaaka-studio' ); ?></button>
						<button type="button" class="zk-btn" data-nav="next" hidden><?php esc_html_e( 'Next', 'zaaka-studio' ); ?></button>
						<button type="submit" class="zk-btn" data-nav="submit"><?php esc_html_e( 'Send the brief', 'zaaka-studio' ); ?></button>
						<button type="button" class="zk-skip" data-nav="skip" hidden><?php esc_html_e( 'Skip this one', 'zaaka-studio' ); ?></button>
					</div>
				</div>

				<aside class="zk-brief-panel" aria-label="<?php esc_attr_e( 'Your brief, so far', 'zaaka-studio' ); ?>" hidden></aside>
			</div>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'zaaka_brief', 'zaaka_brief_form' );

/**
 * A pattern that inserts the shortcode, so the form can be placed from the
 * inserter without anyone having to remember its name.
 */
function zaaka_brief_pattern() {
	register_block_pattern(
		'zaaka/brief',
		array(
			'title'      => __( 'Brief Builder form', 'zaaka-studio' ),
			'categories' => array( 'zaaka' ),
			'content'    => '<!-- wp:shortcode -->[zaaka_brief]<!-- /wp:shortcode -->',
		)
	);
}
add_action( 'init', 'zaaka_brief_pattern', 20 );
