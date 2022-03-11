<?php

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

function explode_id($url) {
    $parse = parse_url($url);
    $id = explode('/', $parse['path']);
    $id = $id[count($id)-1];
    return $id;
}

add_action('wp_ajax_nopriv_get_reviews_from_api', 'get_reviews_from_api');
add_action('wp_ajax_get_reviews_from_api', 'get_reviews_from_api');
function get_reviews_from_api() {

    global $wpdb;
    rmawp_token_update();

    $skip = ( !empty( $_POST['skip'] ) ) ? $_POST['skip'] : 0;

    $options = get_option('rmawp_options');
    $endpoint = 'https://developers.ratemyagent.com.au/agent/';
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
        $json = json_encode($json);
        $like = '%' . $wpdb->esc_like( $review_id ) . '%';
        $result = $wpdb->get_row("SELECT * FROM {$table_name} WHERE review_id LIKE '%".$review_id."%' ");

        if ( is_null($result) ) {
            $sync->insert($review_id, $json, 'review', 'pending');
            $i++;
        }

    }

    $review_count = $review_count - $count;
    $skip = $skip + $count;

    error_log($review_count, 0);
    if ($review_count == 0) {
        error_log('All reviews accounted for. Starting post generation...', 0);
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

add_action('wp_ajax_nopriv_process_reviews', 'process_reviews');
add_action('wp_ajax_process_reviews', 'process_reviews');
function process_reviews() {
    
    global $wpdb;

    $reviews = $wpdb->get_results( $wpdb->prepare("SELECT *, review_id FROM wp_rmawp_queue WHERE status = 'pending' LIMIT 5") );

    if( empty($reviews) ) {
        error_log('all reviews imported.');
        return false;
    }

    foreach($reviews as $review ) {
        $review = (array) $review;

        $json = json_decode($review['jsonstring']);

        $review_data = [
            'post_type' => 'rma-reviews',
            'post_title'    => $json->Title,
            'post_content'  => $json->Description,
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_date' => $json->ReviewedOn
        ];

        $post_id = wp_insert_post( $review_data );
        $attach_id = upload_image($json->PropertyCoverImage);
        set_post_thumbnail( $post_id, $attach_id );

        $review_meta = [
            '_reviewAddress_meta_key' => $json->StreetAddress.', '.$json->Suburb.' '.$json->State.' '.$json->Postcode,
            '_reviewSubmittedBy_meta_key' => $json->ReviewerName,
            '_reviewRating_meta_key' => $json->StarRating,
            '_reviewImageURL_meta_key' => $json->ReviewUrl,
            '_reviewID_meta_key' => $review['review_id']
        ];

        foreach( $review_meta as $key => $meta ) {
            update_post_meta($post_id, $key, $meta);
        }

        $data = [
            'post_id' => $post_id,
            'review_modtime' => $json->ReviewedOn,
            'status' => 'done'
        ];
        $wpdb->update($wpdb->prefix.'rmawp_queue', $data, ['review_id' => $review['review_id']]);
        error_log('review_id '.$post_id.' updated.', 0);
    }

    error_log('round of reviews imported...doing another 5', 0);
    wp_remote_post( admin_url('admin-ajax.php?action=process_reviews'), [
        'blocking' => false, 
        'sslverify' => false
    ]);


}

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