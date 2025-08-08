<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Form_Builder {
    public static function register_menu() {
        add_submenu_page( 'tb_dashboard', __( 'Form Builder', 'tours-booking' ), __( 'Form Builder', 'tours-booking' ), 'manage_options', 'tb_form_builder', [ __CLASS__, 'render' ] );
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Not authorized', 'tours-booking' ) ); }
        if ( isset( $_POST['tb_form_builder_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_form_builder_nonce'] ) ), 'tb_save_form_builder' ) ) {
            $raw = isset( $_POST['tb_custom_fields_json'] ) ? wp_unslash( $_POST['tb_custom_fields_json'] ) : '[]';
            $fields = json_decode( $raw, true );
            if ( is_array( $fields ) ) {
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
                update_option( TB_Settings::OPTION_CUSTOM_FIELDS, $sanitized );
                echo '<div class="updated notice"><p>' . esc_html__( 'Form updated.', 'tours-booking' ) . '</p></div>';
            }
        }
        $custom_fields = get_option( TB_Settings::OPTION_CUSTOM_FIELDS, [] );
        include TB_PLUGIN_DIR . 'admin/views/form-builder.php';
    }
}