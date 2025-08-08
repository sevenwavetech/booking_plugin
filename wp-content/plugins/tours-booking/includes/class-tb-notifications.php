<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Notifications {
    public static function send_status_email( $booking_id, $old_status, $new_status ) {
        self::send_for_key( $booking_id, $new_status );
    }

    public static function send_created_email( $booking_id ) {
        self::send_for_key( $booking_id, 'created' );
    }

    public static function send_updated_email( $booking_id ) {
        self::send_for_key( $booking_id, 'updated' );
    }

    private static function send_for_key( $booking_id, $key ) {
        $settings = TB_Settings::get_settings();
        $templates = get_option( TB_Settings::OPTION_EMAIL_TEMPLATES, [] );
        $placeholders = self::get_placeholders( $booking_id );

        // Client
        $client_tpl = self::resolve_template( $templates, $key, 'client' );
        if ( $client_tpl ) {
            $subject = self::replace_placeholders( $client_tpl['subject'], $placeholders );
            $body = self::replace_placeholders( $client_tpl['body'], $placeholders );
            $headers = self::build_headers( $settings );
            $client_email = $placeholders['client_email'] ?? '';
            if ( is_email( $client_email ) ) {
                wp_mail( $client_email, $subject, $body, $headers );
            }
        }

        // Guides assigned to the tour
        $guide_tpl = self::resolve_template( $templates, $key, 'guide' );
        if ( $guide_tpl ) {
            $subject_g = self::replace_placeholders( $guide_tpl['subject'], $placeholders );
            $body_g = self::replace_placeholders( $guide_tpl['body'], $placeholders );
            $headers_g = self::build_headers( $settings );
            $tour_id = (int) get_post_meta( $booking_id, 'tb_tour_id', true );
            $guides = (array) get_post_meta( $tour_id, 'tb_assigned_guides', true );
            if ( $guides ) {
                $users = get_users( [ 'include' => array_map( 'intval', $guides ) ] );
                foreach ( $users as $user ) {
                    if ( is_email( $user->user_email ) ) {
                        wp_mail( $user->user_email, $subject_g, $body_g, $headers_g );
                    }
                }
            }
        }
    }

    private static function resolve_template( $templates, $key, $recipient ) {
        if ( isset( $templates[ $key ][ $recipient ] ) ) {
            $tpl = $templates[ $key ][ $recipient ];
            if ( ! empty( $tpl['subject'] ) || ! empty( $tpl['body'] ) ) {
                return [ 'subject' => (string) ( $tpl['subject'] ?? '' ), 'body' => (string) ( $tpl['body'] ?? '' ) ];
            }
        }
        // Backward compatibility: flat subject/body
        if ( isset( $templates[ $key ] ) && isset( $templates[ $key ]['subject'] ) ) {
            return [ 'subject' => (string) ( $templates[ $key ]['subject'] ?? '' ), 'body' => (string) ( $templates[ $key ]['body'] ?? '' ) ];
        }
        return null;
    }

    private static function build_headers( $settings ) {
        $headers = [];
        if ( ! empty( $settings['from_name'] ) && ! empty( $settings['from_email'] ) ) {
            $headers[] = 'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>';
        }
        return $headers;
    }

    private static function get_placeholders( $booking_id ) {
        $tour_id = (int) get_post_meta( $booking_id, 'tb_tour_id', true );
        $tour_name = $tour_id ? get_the_title( $tour_id ) : '';
        $client_id = (int) get_post_meta( $booking_id, 'tb_client_id', true );
        $client_name = $client_id ? get_the_title( $client_id ) : '';
        $client_email = $client_id ? get_post_meta( $client_id, 'tb_client_email', true ) : '';
        $date = get_post_meta( $booking_id, 'tb_booking_date', true );
        $start = get_post_meta( $booking_id, 'tb_booking_start', true );
        $end = get_post_meta( $booking_id, 'tb_booking_end', true );
        $participants = get_post_meta( $booking_id, 'tb_participants', true );
        return [
            'client_name' => $client_name,
            'client_email' => $client_email,
            'tour_name' => $tour_name,
            'booking_date' => $date,
            'booking_start' => $start,
            'booking_end' => $end,
            'participants' => $participants,
            'booking_id' => $booking_id,
        ];
    }

    private static function replace_placeholders( $text, $data ) {
        foreach ( $data as $key => $val ) {
            $text = str_replace( '[' . $key . ']', (string) $val, $text );
        }
        return $text;
    }
}