<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

global $wpdb;
$wpdb_collate = $wpdb->collate;

$table_name = $wpdb->prefix . 'rmawp_queue';
$sql =
    "CREATE TABLE {$table_name} (
         `id` int(10) NOT NULL AUTO_INCREMENT,
          `review_id` text NOT NULL,
          `post_id` int(10) NULL,
          `review_modtime` int(20) NULL,
          `jsonstring` longtext NULL,
          `agent_json` longtext NULL,
          `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `type` enum('review','sold', 'forsale') DEFAULT NULL,
          `status` enum('cancel', 'fail', 'pending','done') DEFAULT NULL,
          `status_message` text NULL, 
         PRIMARY KEY  (id)
         )
         COLLATE {$wpdb_collate}";

dbDelta( $sql );