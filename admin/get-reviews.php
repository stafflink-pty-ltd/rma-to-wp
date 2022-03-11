<?php

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

// add_action('wp_ajax_nopriv_get_reviews_from_api', 'get_reviews_from_api');
// add_action('wp_ajax_get_reviews_from_api', 'get_reviews_from_api');
function get_reviews_from_api($api = 'https://developers.ratemyagent.com.au/agent/ab259/sales/reviews') {

	rmawp_token_update();

    $headers = [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('rmawp_temp_token')
        ]
    ];
    // $file = get_stylesheet_directory() . '/report.txt';
    // $current_page = ( !empty($_POST['current_page']) ) ? $_POST['current_page'] : 0;
    // $api = $api . '?take=40';


    // Get count
    $total = wp_remote_retrieve_body(wp_remote_get( $api, $headers ));
    $total = json_decode($total);
    $review_count = $total->Total;

    $skip = 0;
    $all_reviews = [];
    while ($review_count > 0 ) {
        $results = wp_remote_retrieve_body(wp_remote_get( $api.'?skip='.$skip.'&take=10', $headers ));
        $results = json_decode($results);

        foreach($results->Results as $result) {
            $all_reviews[] = $result;
        }
        

        $review_count = $review_count - 10;
        $skip = $skip + 10;
        
    }

    return $all_reviews;
    // file_put_contents($file, "Current Page: " . $results. "\n\n", FILE_APPEND);
    
    
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

