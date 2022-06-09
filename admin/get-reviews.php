<?php

/**
 *  Insert an image into WordPress as Attachment from URL.
 *  Since 0.0.1
 *  
 *  @param string $image_url    A URL to an image to import.
 *  @return int $attach_id      The newly created Attachments' WP ID.
 */
function upload_image($image_url) {

    $image_name = basename( $image_url );
    $upload_dir       = wp_upload_dir(); // Set upload folder
    $arrContextOptions=array(
        "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ),
    );  
    
    $image_data       = @file_get_contents($image_url, false, stream_context_create($arrContextOptions)); // Get image data
    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
    $filename         = basename( $unique_file_name ); // Create image file name

    if( wp_mkdir_p( $upload_dir['path'] ) ) {
      $file = $upload_dir['path'] . '/' . $filename;
    } else {
      $file = $upload_dir['basedir'] . '/' . $filename;
    }

    file_put_contents( $file, $image_data );
    $wp_filetype = 'jpg';
    $attachment = array(
        'post_mime_type' => 'image/jpeg',
        'post_title'     => sanitize_file_name( $filename ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );


    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

    wp_update_attachment_metadata( $attach_id, $attach_data );

    return $attach_id;
}

/**
 *  Update the Rate My Agent Bearer token if the current one reaches a certain age.
 *  Since 0.0.1
 *  
 *  @return bool 
 */
function rmawp_token_update() {

	$time_last_set = get_option('rmawp_temp_token_age');

	if (time() > ($time_last_set + 3600)) {

		$options = get_option('rmawp_options');

		if(empty($options['rmawp_client_id']) || empty($options['rmawp_secret_key'])) {
			error_log('Client & Secret key not set.', 0);
			return;
		}

		$args = [
			'audience' => 'developer.api',
			'headers' => ['Content-Type: application/x-www-form-urlencoded'],
			'body' => [
				'client_id' => $options['rmawp_client_id'],
				'client_secret' => $options['rmawp_secret_key'],
				'grant_type' => 'client_credentials'
			]
		];
		$url = 'https://identity.ratemyagent.com/connect/token';

        $result = wp_remote_post($url, $args);

		$result = json_decode($result['body']);

		update_option('rmawp_temp_token', $result->access_token);
		update_option('rmawp_temp_token_age', time());

	}

    return;

}

/**
 *  I don't quite remember why this is here, but don't delete it.
 *  Since 0.0.1
 *  
 *  @param string $url      A URL to an image to import.
 *  @return string $id      An ID of some sort.
 */
function explode_id($url) {
    $parse = parse_url($url);
    $id = explode('/', $parse['path']);
    $id = $id[count($id)-1];
    return $id;
}

/**
 *  Get all existing reviews using Bearer token and prime them in the DB to be imported.
 *  Since 0.0.1
 *  
 *  @return bool            Always returns false or runs forever.
 */
function get_reviews_from_api() {

    global $wpdb;
    rmawp_token_update();

    $skip = ( !empty( $_POST['skip'] ) ) ? $_POST['skip'] : 0;

    $options = get_option('rmawp_options');
    $endpoint = 'https://developers.ratemyagent.com.au/agency/';
    $after = '/sales/reviews';
    $table_name = $wpdb->prefix.'rmawp_queue';

    $url = $endpoint.$options['rmawp_agent_id'].$after;

    $headers = [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('rmawp_temp_token')
        ]
    ];

    if(!empty($_POST['review_count']) ) {
        $review_count = $_POST['review_count'];
    } else {
        $total = wp_remote_retrieve_body(wp_remote_get( $url, $headers ));
        $total = json_decode($total);
        $review_count = $total->Total;
    }

    $i = 0;

    $sync = new RmaWP\Sync\Queue();

    $results = json_decode(wp_remote_retrieve_body(wp_remote_get( $url.'?skip='.$skip.'&take=10', $headers )));
    $count = count($results->Results);

    foreach( $results->Results as $result ) {

        $review_id = explode_id($result->ReviewUrl);
        $json = (array) $result;
        $json_response = json_encode($json);
        $like = '%' . $wpdb->esc_like( $review_id ) . '%';
        $result = $wpdb->get_row("SELECT * FROM {$table_name} WHERE review_id LIKE '%".$review_id."%' ");
        
        if( !empty($json['Agent']->AgentCode ) ) {
            $agent_profile_endpoint = 'https://developers.ratemyagent.com.au/agent/'.$json['Agent']->AgentCode.'/profile';
            $agent_json = wp_remote_retrieve_body(wp_remote_get( $agent_profile_endpoint, $headers ));
        }

        if ( is_null($result) ) {
            $sync->insert($review_id, $json_response, 'review', 'pending', $agent_json);
            $i++;
        }

    }

    $review_count = $review_count - $count;
    $skip = $skip + $count;

    error_log('Priming Reviews: '.$review_count. ' remaining...', 0);
    if ($review_count == 317) { //set to 0 on production. Set to 307 for testing so we don't import too many.
        error_log('All reviews primed in DB. Starting post generation...', 0);
        process_reviews();
        return false;
    }

    wp_remote_post( admin_url('admin-ajax.php?action=get_reviews_from_api'), [
        'blocking' => false, 
        'sslverify' => false,
        'body' => [
            'skip' => $skip,
            'review_count' => $review_count
        ]
    ]);

}
add_action('wp_ajax_nopriv_get_reviews_from_api', 'get_reviews_from_api');
add_action('wp_ajax_get_reviews_from_api', 'get_reviews_from_api');

/**
 *  Processes primed reviews from the DB. Imports into a custom post type.
 *  Doesn't accept any parameters.
 *  Since 0.0.1
 *  
 *  @return bool     Only ever returns false. Runs recursively until false.
 */
function process_reviews() {
    
    global $wpdb;

    $reviews = $wpdb->get_results($wpdb->prepare("SELECT *, review_id FROM wp_rmawp_queue WHERE status = 'pending' LIMIT 5") );

    if( empty( $reviews ) ) return false;

    foreach( $reviews as $review ) {

        $review = (array) $review;
        $json = json_decode( $review['jsonstring'] );
        $agent_json = json_decode( $review['agent_json'] );
        $attach_id = upload_image($json->PropertyCoverImage);
        $agent_profile_photo = upload_image($agent_json->Branding->Photo);
        error_log(print_r($agent_json->Branding, true), );

        $meta = [];
        $agent_meta = [];

        if( !empty($agent_json) ) {

            $existing_agent = get_page_by_title($agent_json->Name, 'OBJECT', 'rma-agents');

            if( $existing_agent == null ) {

                error_log('Agent does not exist. Creating Agent...', 0);

                foreach( $agent_json as $key => $value ) { 
                    if( is_object($value)) {
                        foreach($value as $key => $subarray) {
                            $agent_meta['_rmaAgent_'.$key] = $subarray;
                        }
                    } else {
                        $agent_meta['_rmaAgent_'.$key] = $value; 
                    } 
                }

                $agent_post_id = wp_insert_post([
                    'post_type' => 'rma-agents',
                    'post_title'    => $agent_json->Name,
                    'post_content'  => $agent_json->Summary,
                    'post_status'   => 'publish',
                    'post_author'   => 1,
                    'meta_input' => $agent_meta,
                    '_thumbnail_id' => $agent_profile_photo
                ]);
            } else {
                error_log('Agent Already exists. Skipping...', 0);
            }

        }

        // Add identifier to key and change objects to array's and remove sub arrays
        foreach( $json as $key => $value ) { 
            if( is_object($value)) {
                foreach($value as $key => $subarray) {
                    $meta['_rmaReview_'.$key] = $subarray;
                }
            } else {
                $meta['_rmaReview_'.$key] = $value; 
            } 
        }

        $post_id = wp_insert_post([
            'post_type' => 'rma-reviews',
            'post_title'    => $json->Title,
            'post_content'  => $json->Description,
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_date' => $json->ReviewedOn,
            'meta_input' => $meta,
            '_thumbnail_id' => $attach_id
        ]);

        $taxonomies = [
            'rma_agent' => $json->Agent->Name,
            'review_type' => $json->ReviewerType
        ];

        foreach ( $taxonomies as $key => $tax ) {
            $term = get_term_by('name', $tax, $key);
            if( $term != false ) {
                wp_set_object_terms($post_id, $term->term_id, $key);
            } else {
                $term = wp_insert_term($tax, $key);
                wp_set_object_terms($post_id, $term['term_id'], $key);
            }
        }

        // Update the queue so we don't accidentally reimport it.
        $wpdb->update(
            $wpdb->prefix.'rmawp_queue', 
            ['post_id' => $post_id, 'review_modtime' => $json->ReviewedOn, 'status' => 'done'],
            ['review_id' => $review['review_id']]
        );

    }

    error_log('round of reviews imported...doing another 5', 0);
    wp_remote_post( admin_url('admin-ajax.php?action=process_reviews'), [
        'blocking' => false, 
        'sslverify' => false
    ]);

}
add_action('wp_ajax_nopriv_process_reviews', 'process_reviews');
add_action('wp_ajax_process_reviews', 'process_reviews');

/**
 * Making this work automatically
 * 
 * 1.) get_reviews_from_api() Gets ALL reviews. Imports only new reviews. 
 *              Get current reviews and if they don't exist in the request, delete from wp
 *              Add a cron so it updates every 24 hours
 * 
 * 2.) process_reviews() Gets reviews from db and imports them into a post.
 *      TODO: 
 *              store json in a field just in case new fields are added to the api
 * 
 *  ISSUES:
 *      API doesn't have:
 *          Unique ID's
 *          Last modified time doesn't exist (Does it need to? do reviews ever update?)
 *          Does expiry of review matter?
 *          pagination (have to use skip + take)
 * 
 *              
 */