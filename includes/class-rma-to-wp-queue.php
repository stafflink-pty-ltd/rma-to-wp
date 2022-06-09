<?php
namespace RmaWP\Sync;

class Queue {

    const TYPE_MANUAL = 'manual';
    const TYPE_AUTO = 'auto';
    const STATUS_PENDING = 'pending';
    const STATUS_CANCEL = 'cancel';
    const STATUS_FAIL = 'fail';
    const STATUS_DONE = 'done';

    static $table_name = 'rmawp_queue';

    static function insert($review_id, $jsonstring, $type, $status = Queue::STATUS_PENDING, $agent_json) {
        global $wpdb;

        $wpdb->insert($wpdb->prefix.self::$table_name, [
            'review_id' => $review_id,
            'jsonstring' => $jsonstring,
            'type' => $type,
            'status' => $status,
            'agent_json' => $agent_json
        ]);

        return $wpdb->insert_id;
    }

    static function update($row_id, $data){
        global $wpdb;


        $wpdb->update($wpdb->prefix.self::$table_name, $data, ['review_id' => $row_id]);
    }

    static function get($id){
        global $wpdb;

        $table_name = $wpdb->prefix.self::$table_name;

        $sql = $wpdb->prepare("SELECT * FROM {$table_name} WHERE review_id = %d", $id );

        return $wpdb->get_row($sql, ARRAY_A);
    }

    static function delete($row_id){
        global $wpdb;

        $table_name = $wpdb->prefix.self::$table_name;

        $wpdb->delete($table_name, ['id' => $row_id]);
    }
}