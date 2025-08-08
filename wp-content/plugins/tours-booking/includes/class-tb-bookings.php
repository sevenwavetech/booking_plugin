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
        $client_name = isset( $_POST['tb_client_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tb_client_name'] ) ) : '';
        $client_email = isset( $_POST['tb_client_email'] ) ? sanitize_email( wp_unslash( $_POST['tb_client_email'] ) ) : '';
        $participants = isset( $_POST['tb_participants'] ) ? intval( $_POST['tb_participants'] ) : 0;

        if ( ! $tour_id || ! $booking_date || ! $client_name || ! is_email( $client_email ) || $participants < 1 ) {
            wp_send_json_error( [ 'message' => __( 'Please fill in all required fields.', 'tours-booking' ) ] );
        }

        // Prevent double booking if Google Calendar shows busy
        $busy = get_transient( 'tb_gcal_busy_dates' );
        if ( is_array( $busy ) && in_array( $booking_date, $busy, true ) ) {
            wp_send_json_error( [ 'message' => __( 'Selected date is not available. Please choose another date.', 'tours-booking' ) ] );
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
        update_post_meta( $post_id, 'tb_client_name', $client_name );
        update_post_meta( $post_id, 'tb_client_email', $client_email );
        update_post_meta( $post_id, 'tb_participants', $participants );
        update_post_meta( $post_id, 'tb_custom_fields', $cf_values );

        // Assign taxonomy status
        wp_set_object_terms( $post_id, $status_slug, 'tb_booking_status', false );

        do_action( 'tb_booking_status_changed', $post_id, '', $status_slug );

        wp_send_json_success( [ 'message' => __( 'Booking submitted successfully.', 'tours-booking' ) ] );
    }
}