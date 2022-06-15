<?php
namespace RmaWP\Sync;

class PropertyQueue {

    const TYPE_MANUAL = 'manual';
    const TYPE_AUTO = 'auto';
    const STATUS_PENDING = 'pending';
    const STATUS_CANCEL = 'cancel';
    const STATUS_FAIL = 'fail';
    const STATUS_DONE = 'done';

    static $table_name = 'rmawp_property_queue';

    static function insert($property_id, $jsonstring, $type, $status = PropertyQueue::STATUS_PENDING, $suburb, $agent_json) {
        global $wpdb;

        $wpdb->insert($wpdb->prefix.self::$table_name, [
            'property_id' => $property_id,
            'jsonstring' => $jsonstring,
            'type' => $type,
            'status' => $status,
            'suburb' => $suburb,
            'agent_json' => $agent_json
        ]);

        return $wpdb->insert_id;
    }

    static function update($row_id, $data){
        global $wpdb;

        $wpdb->update($wpdb->prefix.self::$table_name, $data, ['property_id' => $row_id]);
    }

    static function get($id){
        global $wpdb;

        $table_name = $wpdb->prefix.self::$table_name;

        $sql = $wpdb->prepare("SELECT * FROM {$table_name} WHERE property_id = %d", $id );

        return $wpdb->get_row($sql, ARRAY_A);
    }

    static function delete($row_id){
        global $wpdb;

        $table_name = $wpdb->prefix.self::$table_name;

        $wpdb->delete($table_name, ['id' => $row_id]);
    }
}