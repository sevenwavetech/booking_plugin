<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Bookings {
    public static function register_default_statuses() {
        $defaults = [
            'pending' => __( 'Pending', 'tours-booking' ),
            'approved' => __( 'Approved', 'tours-booking' ),
            'canceled' => __( 'Canceled', 'tours-booking' ),
        ];
        foreach ( $defaults as $slug => $label ) {
            if ( ! term_exists( $slug, 'tb_booking_status' ) ) {
                wp_insert_term( $label, 'tb_booking_status', [ 'slug' => $slug ] );
            }
        }
    }

    public static function handle_booking_submission() {
        check_ajax_referer( 'tb_booking_nonce', 'security' );

        $tour_id = isset( $_POST['tb_tour_id'] ) ? intval( $_POST['tb_tour_id'] ) : 0;
        $booking_date = isset( $_POST['tb_booking_date'] ) ? sanitize_text_field( wp_unslash( $_POST['tb_booking_date'] ) ) : '';
        $booking_start = isset( $_POST['tb_booking_start'] ) ? sanitize_text_field( wp_unslash( $_POST['tb_booking_start'] ) ) : '';
        $client_name = isset( $_POST['tb_client_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tb_client_name'] ) ) : '';
        $client_email = isset( $_POST['tb_client_email'] ) ? sanitize_email( wp_unslash( $_POST['tb_client_email'] ) ) : '';
        $client_phone = isset( $_POST['tb_client_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['tb_client_phone'] ) ) : '';
        $participants = isset( $_POST['tb_participants'] ) ? intval( $_POST['tb_participants'] ) : 0;

        if ( ! $tour_id || ! $booking_date || ! $booking_start || ! $client_name || ! is_email( $client_email ) || $participants < 1 ) {
            wp_send_json_error( [ 'message' => __( 'Please fill in all required fields.', 'tours-booking' ) ] );
        }

        // Prevent double booking if Google Calendar shows busy
        $busy = get_transient( 'tb_gcal_busy_dates' );
        if ( is_array( $busy ) && in_array( $booking_date, $busy, true ) ) {
            wp_send_json_error( [ 'message' => __( 'Selected date is not available. Please choose another date.', 'tours-booking' ) ] );
        }

        // Compute end time from service duration
        $duration = (int) get_post_meta( $tour_id, 'tb_duration_minutes', true );
        if ( $duration <= 0 ) { $duration = 60; }
        $start_ts = strtotime( $booking_start );
        if ( ! $start_ts ) {
            wp_send_json_error( [ 'message' => __( 'Invalid start time.', 'tours-booking' ) ] );
        }
        $booking_end = date( 'Y-m-d H:i', $start_ts + ( $duration * 60 ) );

        // Overlap check with existing bookings
        if ( class_exists( 'TB_Schedule' ) && method_exists( 'TB_Schedule', 'get_available_slots' ) ) {
            // Use the same overlap logic by probing a direct check
            $overlap = self::has_overlap( $tour_id, $booking_start, $booking_end );
            if ( $overlap ) {
                wp_send_json_error( [ 'message' => __( 'Selected time is not available.', 'tours-booking' ) ] );
            }
        }

        $custom_fields = get_option( TB_Settings::OPTION_CUSTOM_FIELDS, [] );
        $cf_values = [];
        foreach ( $custom_fields as $field ) {
            $fid = $field['id'];
            if ( 'file' === $field['type'] ) {
                $file_key = 'cf_file_' . $fid;
                if ( isset( $_FILES[ $file_key ] ) && ( $_FILES[ $file_key ]['error'] === UPLOAD_ERR_OK ) ) {
                    $upload = TB_Uploads::handle_private_upload( $_FILES[ $file_key ], $field );
                    if ( is_wp_error( $upload ) ) {
                        wp_send_json_error( [ 'message' => $upload->get_error_message() ] );
                    } else {
                        $cf_values[ $fid ] = $upload['file'];
                    }
                } elseif ( ! empty( $field['required'] ) ) {
                    wp_send_json_error( [ 'message' => __( 'File upload required.', 'tours-booking' ) ] );
                }
            } else {
                $val = isset( $_POST['cf'][ $fid ] ) ? wp_unslash( $_POST['cf'][ $fid ] ) : '';
                $val = is_array( $val ) ? '' : sanitize_text_field( $val );
                if ( ! $val && ! empty( $field['required'] ) ) {
                    wp_send_json_error( [ 'message' => sprintf( __( '%s is required.', 'tours-booking' ), $field['label'] ) ] );
                }
                $cf_values[ $fid ] = $val;
            }
        }

        $settings = TB_Settings::get_settings();
        $status_slug = $settings['default_status'] ?: 'pending';

        $post_id = wp_insert_post( [
            'post_type' => 'tb_booking',
            'post_status' => 'publish',
            'post_title' => sprintf( __( 'Booking for %s - %s', 'tours-booking' ), get_the_title( $tour_id ), $client_name ),
        ], true );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Could not create booking.', 'tours-booking' ) ] );
        }

        update_post_meta( $post_id, 'tb_tour_id', $tour_id );
        update_post_meta( $post_id, 'tb_booking_date', $booking_date );
        update_post_meta( $post_id, 'tb_booking_start', $booking_start );
        update_post_meta( $post_id, 'tb_booking_end', $booking_end );

        // Link/create client
        $client_id = TB_Clients::find_or_create_by_email( $client_name, $client_email, $client_phone );
        update_post_meta( $post_id, 'tb_client_id', $client_id );
        update_post_meta( $post_id, 'tb_participants', $participants );
        update_post_meta( $post_id, 'tb_custom_fields', $cf_values );

        // Assign taxonomy status
        wp_set_object_terms( $post_id, $status_slug, 'tb_booking_status', false );

        do_action( 'tb_booking_status_changed', $post_id, '', $status_slug );

        wp_send_json_success( [ 'message' => __( 'Booking submitted successfully.', 'tours-booking' ) ] );
    }

    private static function has_overlap( $tour_id, $start, $end ) {
        global $wpdb;
        $meta_key1 = 'tb_booking_start';
        $meta_key2 = 'tb_booking_end';
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