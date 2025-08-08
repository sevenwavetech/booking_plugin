<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Calendar {
    public static function ajax_get_events() {
        check_ajax_referer( 'tb_calendar_nonce', 'nonce' );

        $guide = isset( $_GET['guide'] ) ? intval( $_GET['guide'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $start = isset( $_GET['start'] ) ? sanitize_text_field( wp_unslash( $_GET['start'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $end = isset( $_GET['end'] ) ? sanitize_text_field( wp_unslash( $_GET['end'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $args = [
            'post_type' => 'tb_booking',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [],
            'tax_query' => [],
        ];
        if ( $status ) {
            $args['tax_query'][] = [
                'taxonomy' => 'tb_booking_status',
                'field' => 'slug',
                'terms' => [ $status ],
            ];
        }

        $bookings = get_posts( $args );
        $events = [];

        foreach ( $bookings as $booking ) {
            $tour_id = (int) get_post_meta( $booking->ID, 'tb_tour_id', true );
            if ( $guide ) {
                $assigned = (array) get_post_meta( $tour_id, 'tb_assigned_guides', true );
                if ( ! in_array( $guide, $assigned, true ) ) {
                    continue;
                }
            }
            $start = get_post_meta( $booking->ID, 'tb_booking_start', true );
            $end = get_post_meta( $booking->ID, 'tb_booking_end', true );
            if ( ! $start ) { continue; }
            $client_id = (int) get_post_meta( $booking->ID, 'tb_client_id', true );
            $client_name = $client_id ? get_the_title( $client_id ) : '';
            $events[] = [
                'id' => $booking->ID,
                'title' => get_the_title( $tour_id ) . ' - ' . $client_name,
                'start' => $start,
                'end' => $end,
                'url' => get_edit_post_link( $booking->ID, '' ),
            ];
        }

        wp_send_json( $events );
    }
}