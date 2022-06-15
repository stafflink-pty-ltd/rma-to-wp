<?php

/**
 *  Get all existing properties using Bearer token and prime them in the DB to be imported.
 *  Since 0.0.1
 *  
 *  @return bool            Always returns false or runs forever.
 */
function get_properties_from_api() {

    global $wpdb;
    rmawp_token_update();

    $skip = ( !empty( $_POST['skip'] ) ) ? $_POST['skip'] : 0;

    $options = get_option('rmawp_options');
    $endpoint = 'https://developers.ratemyagent.com.au/agency/';
    $after = '/sales/listings';
    $table_name = $wpdb->prefix.'rmawp_property_queue';

    $url = $endpoint.$options['rmawp_agent_id'].$after;

    $headers = [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . get_option('rmawp_temp_token')
        ]
    ];

    if(!empty($_POST['property_count']) ) {
        $property_count = $_POST['property_count'];
    } else {
        $total = wp_remote_retrieve_body(wp_remote_get( $url, $headers ));
        $total = json_decode($total);
        $property_count = $total->Total;
    }

    $i = 0;

    $sync = new RmaWP\Sync\PropertyQueue();

    $results = json_decode(wp_remote_retrieve_body(wp_remote_get( $url.'?skip='.$skip.'&take=10', $headers )));

    $count = count($results->Results);

    foreach( $results->Results as $result ) {

        $property_id = explode_id($result->ListingUrl);
        $json = (array) $result;
        $json_response = json_encode($json);
        $like = '%' . $wpdb->esc_like( $property_id ) . '%';
        $result = $wpdb->get_row("SELECT * FROM {$table_name} WHERE property_id LIKE '%".$property_id."%' ");

        foreach ($json['Agents'] as $agent) {
            if( !empty($agent ) ) {
                $agent_profile_endpoint = 'https://developers.ratemyagent.com.au/agent/'.$agent->AgentCode.'/profile';
                $agent_json = wp_remote_retrieve_body(wp_remote_get( $agent_profile_endpoint, $headers ));
            }
        }

        if ( is_null($result) ) {
            $sync->insert($property_id, $json_response, 'property', 'pending', '', $agent_json);
            $i++;
        }

    }

    $property_count = $property_count - $count;
    $skip = $skip + $count;

    error_log('Priming properties: '.$property_count. ' remaining...', 0);
    if ($property_count == 317) { //set to 0 on production. Set to 307 for testing so we don't import too many.
        error_log('All properties primed in DB. Starting post generation...', 0);
        process_properties();
        return false;
    }

    wp_remote_post( admin_url('admin-ajax.php?action=get_properties_from_api'), [
        'blocking' => false, 
        'sslverify' => false,
        'body' => [
            'skip' => $skip,
            'property_count' => $property_count
        ]
    ]);

}
add_action('wp_ajax_nopriv_get_properties_from_api', 'get_properties_from_api');
add_action('wp_ajax_get_properties_from_api', 'get_properties_from_api');

/**
 *  Processes primed reviews from the DB. Imports into a custom post type.
 *  Doesn't accept any parameters.
 *  Since 0.0.1
 *  
 *  @return bool     Only ever returns false. Runs recursively until false.
 */
function process_properties() {
    
    global $wpdb;

    $properties = $wpdb->get_results($wpdb->prepare("SELECT *, property_id FROM wp_rmawp_property_queue WHERE status = 'pending' LIMIT 5") );

    if( empty( $properties ) ) return false;

    foreach( $properties as $property ) {

        $property = (array) $property;
        $json = json_decode( $property['jsonstring'] );
        $agent_json = json_decode( $property['agent_json'] );
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
            'post_type'     => 'rma-properties',
            'post_title'    => $json->Title,
            'post_content'  => $json->Description,
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_date'     => date('m/d/Y h:i:s a', time()),
            'meta_input'    => $meta,
            '_thumbnail_id' => $attach_id
        ]);

        $taxonomies = [
            'rma_agent' => $json->Agent->Name,
            'type' => $json->PropertyType
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
            $wpdb->prefix.'rmawp_property_queue', 
            ['post_id' => $post_id, 'property_modtime' => date('m/d/Y h:i:s a', time()), 'status' => 'done'],
            ['property_id' => $property['property_id']]
        );

    }

    error_log('round of properties imported...doing another 5', 0);
    wp_remote_post( admin_url('admin-ajax.php?action=process_properties'), [
        'blocking' => false, 
        'sslverify' => false
    ]);

}
add_action('wp_ajax_nopriv_process_properties', 'process_properties');
add_action('wp_ajax_process_properties', 'process_properties');

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
 *          Does expiry of property matter?
 *          pagination (have to use skip + take)
 * 
 *              
 */