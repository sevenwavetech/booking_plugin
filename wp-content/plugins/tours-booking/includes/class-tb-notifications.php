<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Notifications {
    public static function send_status_email( $booking_id, $old_status, $new_status ) {
        $settings = TB_Settings::get_settings();
        $templates = get_option( TB_Settings::OPTION_EMAIL_TEMPLATES, [] );
        $tpl = $templates[ $new_status ] ?? null;
        if ( ! $tpl ) {
            return;
        }

        $placeholders = self::get_placeholders( $booking_id );
        $subject = self::replace_placeholders( $tpl['subject'], $placeholders );
        $body = self::replace_placeholders( $tpl['body'], $placeholders );

        $headers = [];
        if ( ! empty( $settings['from_name'] ) && ! empty( $settings['from_email'] ) ) {
            $headers[] = 'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>';
        }
        $to = get_post_meta( $booking_id, 'tb_client_email', true );
        if ( is_email( $to ) ) {
            wp_mail( $to, $subject, $body, $headers );
        }
    }

    private static function get_placeholders( $booking_id ) {
        $tour_id = (int) get_post_meta( $booking_id, 'tb_tour_id', true );
        $tour_name = $tour_id ? get_the_title( $tour_id ) : '';
        $data = [
            'client_name' => get_post_meta( $booking_id, 'tb_client_name', true ),
            'client_email' => get_post_meta( $booking_id, 'tb_client_email', true ),
            'tour_name' => $tour_name,
            'booking_date' => get_post_meta( $booking_id, 'tb_booking_date', true ),
            'participants' => get_post_meta( $booking_id, 'tb_participants', true ),
            'booking_id' => $booking_id,
        ];
        return $data;
    }

    private static function replace_placeholders( $text, $data ) {
        foreach ( $data as $key => $val ) {
            $text = str_replace( '[' . $key . ']', (string) $val, $text );
        }
        return $text;
    }
}