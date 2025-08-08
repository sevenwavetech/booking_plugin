<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Settings {
    const OPTION_SETTINGS = 'tb_settings';
    const OPTION_CUSTOM_FIELDS = 'tb_custom_fields';
    const OPTION_EMAIL_TEMPLATES = 'tb_email_templates';

    public static function get_settings() {
        $defaults = [
            'business_name' => '',
            'business_email' => '',
            'default_currency' => 'USD',
            'timezone' => get_option( 'timezone_string' ),
            'date_format' => get_option( 'date_format' ),
            'time_format' => get_option( 'time_format' ),
            'default_status' => 'pending',
            'from_name' => get_bloginfo( 'name' ),
            'from_email' => get_bloginfo( 'admin_email' ),
            'gcal_client_id' => '',
            'gcal_client_secret' => '',
            'gcal_calendar_id' => '',
            'gcal_token' => '',
            'delete_on_uninstall' => 0,
            'debug_log' => 0,
            'payments_test_mode' => 1,
            'payments_stripe_key' => '',
            'payments_paypal_key' => '',
        ];
        $settings = get_option( self::OPTION_SETTINGS, [] );
        return wp_parse_args( $settings, $defaults );
    }

    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'tours-booking' ) );
        }

        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $settings = self::get_settings();

        if ( isset( $_POST['tb_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_settings_nonce'] ) ), 'tb_save_settings' ) && current_user_can( 'manage_options' ) ) {
            self::handle_post( $tab, $settings );
            $settings = self::get_settings();
            echo '<div class="updated notice"><p>' . esc_html__( 'Settings saved.', 'tours-booking' ) . '</p></div>';
        }

        include TB_PLUGIN_DIR . 'admin/views/settings.php';
    }

    private static function handle_post( $tab, $settings ) {
        switch ( $tab ) {
            case 'general':
                $settings['business_name'] = isset( $_POST['business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['business_name'] ) ) : '';
                $settings['business_email'] = isset( $_POST['business_email'] ) ? sanitize_email( wp_unslash( $_POST['business_email'] ) ) : '';
                $settings['default_currency'] = isset( $_POST['default_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['default_currency'] ) ) : 'USD';
                $settings['timezone'] = isset( $_POST['timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['timezone'] ) ) : '';
                $settings['date_format'] = isset( $_POST['date_format'] ) ? sanitize_text_field( wp_unslash( $_POST['date_format'] ) ) : '';
                $settings['time_format'] = isset( $_POST['time_format'] ) ? sanitize_text_field( wp_unslash( $_POST['time_format'] ) ) : '';
                update_option( self::OPTION_SETTINGS, $settings );
                break;
            case 'form':
                // Custom fields JSON from hidden input
                $raw = isset( $_POST['tb_custom_fields_json'] ) ? wp_unslash( $_POST['tb_custom_fields_json'] ) : '[]';
                $fields = json_decode( $raw, true );
                if ( is_array( $fields ) ) {
                    // sanitize each field
                    $sanitized = [];
                    foreach ( $fields as $field ) {
                        $sanitized[] = [
                            'id' => sanitize_key( $field['id'] ?? '' ),
                            'label' => sanitize_text_field( $field['label'] ?? '' ),
                            'type' => in_array( $field['type'] ?? 'text', [ 'text', 'email', 'select', 'file' ], true ) ? $field['type'] : 'text',
                            'required' => ! empty( $field['required'] ) ? 1 : 0,
                            'options' => isset( $field['options'] ) && is_array( $field['options'] ) ? array_map( 'sanitize_text_field', $field['options'] ) : [],
                            'mime_types' => isset( $field['mime_types'] ) && is_array( $field['mime_types'] ) ? array_map( 'sanitize_text_field', $field['mime_types'] ) : [ 'image/jpeg', 'image/png', 'application/pdf' ],
                            'max_size_mb' => isset( $field['max_size_mb'] ) ? min( 10, max( 1, intval( $field['max_size_mb'] ) ) ) : 5,
                        ];
                    }
                    update_option( self::OPTION_CUSTOM_FIELDS, $sanitized );
                }
                $settings['default_status'] = isset( $_POST['default_status'] ) ? sanitize_key( wp_unslash( $_POST['default_status'] ) ) : 'pending';
                update_option( self::OPTION_SETTINGS, $settings );
                break;
            case 'notifications':
                $templates_raw = isset( $_POST['tb_email_templates_json'] ) ? wp_unslash( $_POST['tb_email_templates_json'] ) : '{}';
                $templates = json_decode( $templates_raw, true );
                if ( is_array( $templates ) ) {
                    $sanitized = [];
                    foreach ( $templates as $status => $tpl ) {
                        $sanitized[ sanitize_key( $status ) ] = [
                            'subject' => sanitize_text_field( $tpl['subject'] ?? '' ),
                            'body' => wp_kses_post( $tpl['body'] ?? '' ),
                        ];
                    }
                    update_option( self::OPTION_EMAIL_TEMPLATES, $sanitized );
                }
                $settings['from_name'] = isset( $_POST['from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['from_name'] ) ) : '';
                $settings['from_email'] = isset( $_POST['from_email'] ) ? sanitize_email( wp_unslash( $_POST['from_email'] ) ) : '';
                update_option( self::OPTION_SETTINGS, $settings );
                break;
            case 'payments':
                $settings['payments_test_mode'] = ! empty( $_POST['payments_test_mode'] ) ? 1 : 0;
                $settings['payments_stripe_key'] = isset( $_POST['payments_stripe_key'] ) ? sanitize_text_field( wp_unslash( $_POST['payments_stripe_key'] ) ) : '';
                $settings['payments_paypal_key'] = isset( $_POST['payments_paypal_key'] ) ? sanitize_text_field( wp_unslash( $_POST['payments_paypal_key'] ) ) : '';
                update_option( self::OPTION_SETTINGS, $settings );
                break;
            case 'integrations':
                $settings['gcal_client_id'] = isset( $_POST['gcal_client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['gcal_client_id'] ) ) : '';
                $settings['gcal_client_secret'] = isset( $_POST['gcal_client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['gcal_client_secret'] ) ) : '';
                $settings['gcal_calendar_id'] = isset( $_POST['gcal_calendar_id'] ) ? sanitize_text_field( wp_unslash( $_POST['gcal_calendar_id'] ) ) : '';
                // token managed via auth flow typically; allow manual paste here for fallback
                $settings['gcal_token'] = isset( $_POST['gcal_token'] ) ? wp_kses_post( wp_unslash( $_POST['gcal_token'] ) ) : '';
                update_option( self::OPTION_SETTINGS, $settings );
                break;
            case 'advanced':
                $settings['delete_on_uninstall'] = ! empty( $_POST['delete_on_uninstall'] ) ? 1 : 0;
                $settings['debug_log'] = ! empty( $_POST['debug_log'] ) ? 1 : 0;
                update_option( self::OPTION_SETTINGS, $settings );
                break;
        }
    }
}