<div class="single-review splide__slide">
	<div class="content">
		<img src="<?php echo $property_cover_image; ?>" class="review-image" />
		<div class="inner-content">
            <div class="agent-section">
				<div class="agent-left">
                    <img class="profile-photo" src="<?php echo get_the_post_thumbnail_url($agent[0]->ID); ?>"/>
					<p class="total-rating">
                        <img src="<?php echo $plugin_dir.'/media/star-white.png'; ?>">
                        </span><?php echo get_post_meta($agent[0]->ID, '_rmaAgent_OverallStars', true); ?></span>
                    </p>
				</div>
				<div class="agent-right">
					<h4 class="agent-name">
                        <a target="_blank" href="<?php echo get_post_meta($agent[0]->ID, '_rmaAgent_RmaAgentProfileUrl', true); ?>"/>
                            <?php echo get_post_meta($agent[0]->ID, '_rmaAgent_Name', true); ?>
                        </a>
                    </h4>
                    <p class="review-count">
                        <a href="<?php echo get_post_meta($agent[0]->ID, '_rmaAgent_RmaAgentProfileUrl', true); ?>" target="_blank">
                            <?php echo get_post_meta(get_the_ID(), '_rmaAgent_ReviewCount', true); ?> Reviews 
                        </a>
                    </p>
				</div>
			</div>
            
            <div class="inner-content-text">
                <div class="stars">
                    <?php echo $starlist; ?>
                </div>
                <a href="<?php echo get_post_meta(get_the_ID(), '_rmaReview_ReviewUrl', true); ?>" target="_blank"><?php the_title(); ?></a>
                <div class="review-description">
                    <?php the_content(); ?>
                </div>
                <p class="customer-details">
                    Review submitted by 
                    <span><?php echo get_post_meta(get_the_ID(), '_rmaReview_ReviewerName', true); ?> (<?php echo get_post_meta(get_the_ID(), '_rmaReview_ReviewerType', true); ?>)</span>
                    on <?php echo get_the_date(); ?>
                </p>
                <p class="verified-review"> This is a verified review, submitted by the customer directly involved in the property transaction </p>
                <p class="address">
                    <a href="<?php echo get_post_meta(get_the_ID(), '_rmaReview_ReviewUrl', true); ?>" target="_blank">
                        <?php echo get_post_meta(get_the_ID(), '_rmaReview_StreetAddress', true); ?>, 
                        <?php echo get_post_meta(get_the_ID(), '_rmaReview_Suburb', true); ?>, 
                        <?php echo get_post_meta(get_the_ID(), '_rmaReview_State', true); ?>, 
                        <?php echo get_post_meta(get_the_ID(), '_rmaReview_Postcode', true); ?>
                    </a>
                </p>
                <img loading="lazy" src="<?php echo get_post_meta(get_the_ID(), '_rmaReview_ReviewTrixelImgUrl', true); ?>"/>
            </div>	
        </div>
	</div>
</div>

