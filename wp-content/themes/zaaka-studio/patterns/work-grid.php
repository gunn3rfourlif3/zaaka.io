<?php
/**
 * Title: Work grid
 * Slug: zaaka/work-grid
 * Categories: zaaka
 * Description: Latest projects pulled from the Projects post type.
 */
?>
<!-- wp:group {"align":"full","className":"zk-work zk-light","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1180px","wideSize":"1180px"}} -->
<div class="wp-block-group alignfull zk-work zk-light" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--50)">

<!-- wp:group {"align":"wide","className":"zk-reveal","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"bottom","flexWrap":"wrap"}} -->
<div class="wp-block-group alignwide zk-reveal">
<!-- wp:heading {"level":2,"fontSize":"xxxl"} --><h2 class="wp-block-heading has-xxxl-font-size">Selected work</h2><!-- /wp:heading -->
<!-- wp:paragraph {"fontSize":"sm"} --><p class="has-sm-font-size"><a href="/work/">All projects</a></p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:query {"queryId":20,"query":{"perPage":4,"pages":1,"offset":0,"postType":"project","order":"desc","orderBy":"date","inherit":false},"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
<div class="wp-block-query alignwide" style="margin-top:var(--wp--preset--spacing--40)">
<!-- wp:post-template {"layout":{"type":"grid","minimumColumnWidth":"20rem"},"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|40","left":"var:preset|spacing|40"}}}} -->
<!-- wp:post-featured-image {"isLink":true,"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} /-->
<!-- wp:post-terms {"term":"discipline","className":"zk-meta","fontSize":"xs"} /-->
<!-- wp:post-title {"isLink":true,"fontSize":"xl"} /-->
<!-- wp:post-excerpt {"excerptLength":24,"moreText":"Read"} /-->
<!-- /wp:post-template -->
<!-- wp:query-no-results -->
<!-- wp:paragraph {"textColor":"muted"} --><p class="has-muted-color has-text-color">Add projects under <strong>Projects</strong> in the dashboard and they will appear here automatically.</p><!-- /wp:paragraph -->
<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->

</div>
<!-- /wp:group -->
