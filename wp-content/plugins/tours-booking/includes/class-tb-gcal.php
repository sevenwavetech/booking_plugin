<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_GCal {
    private static function get_settings() {
        return TB_Settings::get_settings();
    }

    private static function get_access_token() {
        $settings = self::get_settings();
        if ( empty( $settings['gcal_token'] ) ) {
            return new WP_Error( 'tb_gcal_no_token', __( 'Google Calendar token not configured.', 'tours-booking' ) );
        }
        $token = json_decode( $settings['gcal_token'], true );
        if ( ! is_array( $token ) ) {
            return new WP_Error( 'tb_gcal_bad_token', __( 'Invalid Google token JSON.', 'tours-booking' ) );
        }
        $now = time();
        if ( isset( $token['expires_at'] ) && $token['expires_at'] > $now + 60 ) {
            return $token['access_token'];
        }
        if ( empty( $token['refresh_token'] ) ) {
            return $token['access_token'] ?? new WP_Error( 'tb_gcal_no_refresh', __( 'No refresh token available.', 'tours-booking' ) );
        }
        $new = self::refresh_token( $token['refresh_token'] );
        if ( is_wp_error( $new ) ) {
            return $new;
        }
        $token['access_token'] = $new['access_token'];
        $token['expires_in'] = $new['expires_in'];
        $token['expires_at'] = time() + intval( $new['expires_in'] );
        // persist
        $settings['gcal_token'] = wp_json_encode( $token );
        update_option( TB_Settings::OPTION_SETTINGS, $settings );
        return $token['access_token'];
    }

    private static function refresh_token( $refresh_token ) {
        $settings = self::get_settings();
        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', [
            'timeout' => 15,
            'body' => [
                'client_id' => $settings['gcal_client_id'],
                'client_secret' => $settings['gcal_client_secret'],
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            ],
        ] );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return new WP_Error( 'tb_gcal_refresh_failed', __( 'Failed to refresh Google token.', 'tours-booking' ) );
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['access_token'] ) ) {
            return new WP_Error( 'tb_gcal_refresh_bad', __( 'Invalid token refresh response.', 'tours-booking' ) );
        }
        return $data;
    }

    public static function is_date_free( $date_ymd ) {
        $events = self::get_events_for_date( $date_ymd );
        return empty( $events );
    }

    public static function get_events_for_date( $date_ymd ) {
        $token = self::get_access_token();
        if ( is_wp_error( $token ) ) {
            return [];
        }
        $settings = self::get_settings();
        $calendarId = rawurlencode( $settings['gcal_calendar_id'] );
        if ( empty( $calendarId ) ) { return []; }
        $tz = $settings['timezone'] ?: 'UTC';
        $start = $date_ymd . 'T00:00:00Z';
        $end   = $date_ymd . 'T23:59:59Z';
        // Use timeMin/timeMax in UTC; for all-day we will use date
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $calendarId . '/events';
        $args = [
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
            'timeout' => 15,
        ];
        $query = [
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
            'timeMin' => gmdate( 'c', strtotime( $start ) ),
            'timeMax' => gmdate( 'c', strtotime( $end ) ),
            'maxResults' => 50,
        ];
        $response = wp_remote_get( $url . '?' . http_build_query( $query, '', '&' ), $args );
        if ( is_wp_error( $response ) ) { return []; }
        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) { return []; }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return is_array( $data['items'] ?? null ) ? $data['items'] : [];
    }

    public static function create_event_for_booking( $booking_id ) {
        $token = self::get_access_token();
        if ( is_wp_error( $token ) ) { return $token; }
        $settings = self::get_settings();
        $calendarId = rawurlencode( $settings['gcal_calendar_id'] );
        if ( empty( $calendarId ) ) { return new WP_Error( 'tb_gcal_no_calendar', __( 'No calendar configured.', 'tours-booking' ) ); }

        $tour_id = (int) get_post_meta( $booking_id, 'tb_tour_id', true );
        $client_name = (string) get_post_meta( $booking_id, 'tb_client_name', true );
        $start = (string) get_post_meta( $booking_id, 'tb_booking_start', true );
        $end = (string) get_post_meta( $booking_id, 'tb_booking_end', true );
        $date = (string) get_post_meta( $booking_id, 'tb_booking_date', true );
        if ( ! $start ) { return new WP_Error( 'tb_gcal_no_date', __( 'No booking time.', 'tours-booking' ) ); }
        $summary = sprintf( __( 'Tour: %s - %s', 'tours-booking' ), get_the_title( $tour_id ), $client_name );

        $body = [
            'summary' => $summary,
            'start' => [ 'dateTime' => date( 'c', strtotime( $start ) ) ],
            'end' => [ 'dateTime' => date( 'c', strtotime( $end ?: $start ) ) ],
            'description' => get_edit_post_link( $booking_id ) ?: '',
        ];
        $response = wp_remote_post( 'https://www.googleapis.com/calendar/v3/calendars/' . $calendarId . '/events', [
            'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
            'body' => wp_json_encode( $body ),
            'timeout' => 15,
        ] );
        if ( is_wp_error( $response ) ) { return $response; }
        if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return new WP_Error( 'tb_gcal_create_failed', __( 'Failed to create Google Calendar event.', 'tours-booking' ) );
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $data['id'] ) ) {
            update_post_meta( $booking_id, 'tb_gcal_event_id', sanitize_text_field( $data['id'] ) );
        }
        return $data;
    }

    public static function delete_event_for_booking( $booking_id ) {
        $token = self::get_access_token();
        if ( is_wp_error( $token ) ) { return $token; }
        $settings = self::get_settings();
        $calendarId = rawurlencode( $settings['gcal_calendar_id'] );
        $event_id = get_post_meta( $booking_id, 'tb_gcal_event_id', true );
        if ( empty( $event_id ) || empty( $calendarId ) ) { return false; }
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $calendarId . '/events/' . rawurlencode( $event_id );
        $response = wp_remote_request( $url, [
            'method' => 'DELETE',
            'headers' => [ 'Authorization' => 'Bearer ' . $token ],
            'timeout' => 15,
        ] );
        if ( is_wp_error( $response ) ) { return $response; }
        if ( wp_remote_retrieve_response_code( $response ) === 204 ) {
            delete_post_meta( $booking_id, 'tb_gcal_event_id' );
            return true;
        }
        return false;
    }

    public static function on_booking_status_changed( $booking_id, $old, $new ) {
        if ( $new === 'canceled' ) {
            self::delete_event_for_booking( $booking_id );
        } elseif ( $new === 'approved' || $new === 'pending' ) {
            // ensure event exists
            $existing = get_post_meta( $booking_id, 'tb_gcal_event_id', true );
            if ( empty( $existing ) ) {
                self::create_event_for_booking( $booking_id );
            }
        }
    }

    public static function manual_pull() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Not authorized', 'tours-booking' ) );
        }
        check_admin_referer( 'tb_pull_gcal' );
        // For simplicity: just fetch next 30 days and cache busy dates
        $busy = [];
        for ( $i = 0; $i < 30; $i++ ) {
            $date = gmdate( 'Y-m-d', strtotime( '+' . $i . ' days' ) );
            $events = self::get_events_for_date( $date );
            if ( ! empty( $events ) ) { $busy[] = $date; }
        }
        set_transient( 'tb_gcal_busy_dates', $busy, DAY_IN_SECONDS );
        wp_safe_redirect( admin_url( 'admin.php?page=tb_calendar&pulled=1' ) );
        exit;
    }
}