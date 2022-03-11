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

function get_reviews_from_api() {

    global $wpdb;

    $options = get_option('rmawp_options');
    $endpoint = 'https://developers.ratemyagent.com.au/agent/';
    $after = '/sales/reviews';
    $table_name = $wpdb->prefix.'rmawp_queue';

    $url = $endpoint.$options['rmawp_agent_id'].$after;

	rmawp_token_update();

    $headers = [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('rmawp_temp_token')
        ]
    ];

    $total = wp_remote_retrieve_body(wp_remote_get( $url, $headers ));
    $total = json_decode($total);
    $review_count = $total->Total;
    $skip = 0;
    $i = 0;

    $sync = new RmaWP\Sync\Queue();

    while ($review_count > 0 ) {
        
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

    }

    if($i >= 1 ) {
        error_log($i . ' reviews found. queued for processing.');
        return true;
    } else {
        error_log($i . ' reviews found.', 0);
        return false;
    }



    
    
    // if( ! is_array( $results ) || empty( $results ) ) {
    //     return false;
    // }

    // $current_page = $current_page + 10;
    // wp_remote_post( admin_url('admin-ajax.php?action=get_reviews_from_api'), [
    //     'blocking' => false, 
    //     'sslverify' => false,
    //     'body' => [
    //         'current_page' => $current_page,
    //         'review_count' => $review_count
    //     ]
    // ] );

}

function process_reviews() {
    
    global $wpdb;

    $reviews = $wpdb->get_results( $wpdb->prepare("SELECT *, review_id FROM wp_rmawp_queue WHERE status = 'pending' LIMIT 5") );

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
        update_post_meta($post_id, '_reviewAddress_meta_key', $json->StreetAddress.', '.$json->Suburb.' '.$json->State.' '.$json->Postcode);
        update_post_meta($post_id, '_reviewSubmittedBy_meta_key', $json->ReviewerName );
        update_post_meta($post_id, '_reviewRating_meta_key', $json->StarRating );
        update_post_meta($post_id, '_reviewImageURL_meta_key', $json->ReviewUrl );

            $data = [
                'post_id' => $post_id,
                'review_modtime' => $json->ReviewedOn,
                'status' => 'done'
            ];
            $wpdb->update($wpdb->prefix.'rmawp_queue', $data, ['review_id' => $review['review_id']]);

    }

}