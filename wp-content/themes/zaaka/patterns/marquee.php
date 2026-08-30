<?php
/**
 * Title: Capability marquee
 * Slug: zaaka/marquee
 * Categories: zaaka
 * Description: Scrolling band of capabilities.
 */
$zaaka_items = array(
	'Web apps', 'SaaS platforms', 'WordPress', 'Design systems', 'E-commerce',
	'APIs &amp; integrations', 'Mobile', 'Payments', 'Dashboards', 'Automation',
	'Brand &amp; identity', 'Technical SEO',
);
$zaaka_run = '';
foreach ( $zaaka_items as $zaaka_item ) {
	$zaaka_run .= '<span class="zk-marquee__item">' . $zaaka_item . '</span>';
}
?>
<!-- wp:group {"align":"full","className":"zk-dark","backgroundColor":"ink","style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}},"border":{"top":{"color":"var:preset|color|hairline","width":"1px"},"bottom":{"color":"var:preset|color|hairline","width":"1px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull zk-dark has-ink-background-color has-background" style="border-top-color:var(--wp--preset--color--hairline);border-top-width:1px;border-bottom-color:var(--wp--preset--color--hairline);border-bottom-width:1px;padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)">
<!-- wp:html -->
<div class="zk-marquee" aria-hidden="true">
	<div class="zk-marquee__track"><?php echo $zaaka_run . $zaaka_run; // phpcs:ignore ?></div>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
