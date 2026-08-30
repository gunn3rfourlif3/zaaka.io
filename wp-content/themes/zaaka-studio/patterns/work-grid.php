<?php
/**
 * Title: Work grid
 * Slug: zaaka/work-grid
 * Categories: zaaka
 * Description: Latest projects pulled from the Projects post type.
 */
?>
<!-- wp:group {"align":"full","className":"zk-work zk-light","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"1180px","wideSize":"1180px"}} -->
<div class="wp-block-group alignfull zk-work zk-light" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">

<!-- wp:group {"align":"wide","className":"zk-work__head zk-reveal","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"bottom","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
<div class="wp-block-group alignwide zk-work__head zk-reveal">
<!-- wp:group {"className":"zk-work__title","layout":{"type":"default"}} -->
<div class="wp-block-group zk-work__title">
<!-- wp:paragraph {"className":"is-style-eyebrow"} --><p class="is-style-eyebrow">Case studies</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"fontSize":"xxxl"} --><h2 class="wp-block-heading has-xxxl-font-size">Selected work</h2><!-- /wp:heading -->
</div>
<!-- /wp:group -->
<!-- wp:paragraph {"className":"zk-work__all"} --><p class="zk-work__all"><a href="/work/">All projects</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:query {"queryId":20,"query":{"perPage":4,"pages":1,"offset":0,"postType":"project","order":"desc","orderBy":"date","inherit":false},"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-query alignwide">
<!-- wp:post-template {"layout":{"type":"default"}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10"} /-->
<!-- wp:post-terms {"term":"discipline","className":"zk-meta","fontSize":"xs"} /-->
<!-- wp:post-title {"isLink":true,"fontSize":"xxl"} /-->
<!-- wp:post-excerpt {"excerptLength":22,"moreText":"View case study"} /-->
<!-- /wp:post-template -->
<!-- wp:query-no-results -->
<!-- wp:paragraph {"textColor":"muted"} --><p class="has-muted-color has-text-color">Add projects under <strong>Projects</strong> in the dashboard and they will appear here automatically.</p><!-- /wp:paragraph -->
<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->

</div>
<!-- /wp:group -->
