<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Schedule {
    public static function register_ajax() {
        add_action( 'wp_ajax_tb_get_slots', [ __CLASS__, 'ajax_get_slots' ] );
        add_action( 'wp_ajax_nopriv_tb_get_slots', [ __CLASS__, 'ajax_get_slots' ] );
    }

    public static function ajax_get_slots() {
        $tour_id = isset( $_GET['tour_id'] ) ? intval( $_GET['tour_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $date = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! $tour_id || ! $date ) { wp_send_json_error( [ 'message' => __( 'Missing params', 'tours-booking' ) ] ); }
        $slots = self::get_available_slots( $tour_id, $date );
        wp_send_json_success( [ 'slots' => $slots ] );
    }

    public static function get_available_slots( $tour_id, $date_ymd ) {
        $duration = (int) get_post_meta( $tour_id, 'tb_duration_minutes', true );
        $buffer_before = (int) get_post_meta( $tour_id, 'tb_buffer_before', true );
        $buffer_after = (int) get_post_meta( $tour_id, 'tb_buffer_after', true );
        if ( $duration <= 0 ) { $duration = 60; }
        $slot_length = $duration + $buffer_before + $buffer_after;

        // Working hours per guide (simple default 09:00-17:00 if no custom hours)
        $assigned_guides = (array) get_post_meta( $tour_id, 'tb_assigned_guides', true );
        if ( empty( $assigned_guides ) ) { return []; }

        $day_of_week = strtolower( date( 'D', strtotime( $date_ymd ) ) );
        $work_from = '09:00';
        $work_to = '17:00';

        $day_slots = [];
        $start_ts = strtotime( $date_ymd . ' ' . $work_from );
        $end_ts = strtotime( $date_ymd . ' ' . $work_to );
        for ( $ts = $start_ts; $ts + ( $duration * 60 ) <= $end_ts; $ts += $slot_length * 60 ) {
            $slot_start = date( 'Y-m-d H:i', $ts );
            $slot_end = date( 'Y-m-d H:i', $ts + ( $duration * 60 ) );
            if ( self::is_slot_available( $tour_id, $assigned_guides, $slot_start, $slot_end ) ) {
                $day_slots[] = [ 'start' => $slot_start, 'end' => $slot_end ];
            }
        }
        return $day_slots;
    }

    private static function is_slot_available( $tour_id, $guide_ids, $start, $end ) {
        // Check existing bookings for the service overlapping the slot
        $overlap = self::has_overlapping_booking( $tour_id, $start, $end );
        if ( $overlap ) { return false; }
        // Optionally check Google Calendar busy
        $busy_dates = get_transient( 'tb_gcal_busy_dates' );
        $date = substr( $start, 0, 10 );
        if ( is_array( $busy_dates ) && in_array( $date, $busy_dates, true ) ) { return false; }
        return true;
    }

    private static function has_overlapping_booking( $tour_id, $start, $end ) {
        global $wpdb;
        $meta_key1 = 'tb_booking_start';
        $meta_key2 = 'tb_booking_end';
        $start = sanitize_text_field( $start );
        $end = sanitize_text_field( $end );
        // Overlap query: (start < slot_end) AND (end > slot_start)
        $sql = $wpdb->prepare(
            "SELECT pm1.post_id FROM {$wpdb->postmeta} pm1
             JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
             JOIN {$wpdb->postmeta} pm3 ON pm1.post_id = pm3.post_id
             JOIN {$wpdb->posts} p ON p.ID = pm1.post_id AND p.post_type = 'tb_booking' AND p.post_status = 'publish'
             WHERE pm3.meta_key = 'tb_tour_id' AND pm3.meta_value = %d
               AND pm1.meta_key = %s AND pm2.meta_key = %s
               AND pm1.meta_value < %s AND pm2.meta_value > %s
             LIMIT 1",
            $tour_id, $meta_key1, $meta_key2, $end, $start
        );
        $found = $wpdb->get_var( $sql );
        return ! empty( $found );
    }
}