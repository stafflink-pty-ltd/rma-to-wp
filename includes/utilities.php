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