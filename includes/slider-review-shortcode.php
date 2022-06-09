<?php

/**
 * Outputs the HTML and the JS file which then triggers the API requests.
 * The js file triggers rmaa_get_custom_data();.
 */
add_shortcode('rmawp', 'rma_wp_slider_shortcode');
function rma_wp_slider_shortcode($attr) {
	
	$args = shortcode_atts([
        'min_rating' => 4,
        'agent_name' => '',
		'id' => get_option('rmawp_agent_id'),
		'sale_type' => 'sales',
        'property_type' => '',
        'suburb' => '',
        'state' => '',
        'postcode' => '',
        'take' => 15,
	], $attr );

    $args = [
        'post_type' => 'rma-reviews',
        'orderby' => 'date',
        'order' => 'DESC',
        'posts_per_page' => $args['take']
    ];

	$query = new WP_Query($args);

    if ($query->have_posts()) {

        $plugin_dir = WP_PLUGIN_URL . '/rma-to-wp';

        ob_start();

        echo '<div id="rmar-container" class="splide"> <div class="splide__track"> <div class="splide__list">';

        while ($query->have_posts()) { 
            
            $query->the_post();

            $agent = get_posts([
                'post_type' => 'rma-agents', 
                'meta_key' => '_rmaAgent_AgentCode',
                'posts_per_page' => 1,
                'meta_value' => get_post_meta(get_the_ID(), '_rmaReview_AgentCode', true)
            ]);

            $stars = get_post_meta(get_the_ID(), '_rmaReview_StarRating', true);
            $stars = (int)$stars;

            $i = 0;
            $starlist = '';
            while ($i < $stars) {
                $starlist .= '<img class="star" src="'.$plugin_dir.'/media/star.png">';
                $i++;
            }
    
            if (has_post_thumbnail()) {
                $property_cover_image = get_the_post_thumbnail_url(get_the_ID(), 'property-grid');
            } else {
                $property_cover_image = plugin_dir_url( __FILE__ ) . '/media/placeholder.jpg';
            }

            include 'templates/slider-card.php';

        }

        echo '</div></div></div>';

        $reviews = ob_get_contents();
        ob_end_clean();
        wp_reset_postdata();

        return $reviews;

    }


    wp_enqueue_script( 'rmaa-slider-shortcode-js' );
	wp_localize_script( 'rmaa-slider-shortcode-js', 'rmaa_object', $js_array );
}