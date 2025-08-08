<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_I18n {
    public static function register_dynamic_strings() {
        // Custom Fields
        $fields = get_option( TB_Settings::OPTION_CUSTOM_FIELDS, [] );
        foreach ( (array) $fields as $field ) {
            $name = 'field_' . ( $field['id'] ?? '' );
            self::register_string( 'Custom Fields', $name . '_label', $field['label'] ?? '' );
        }
        // Email Templates
        $templates = get_option( TB_Settings::OPTION_EMAIL_TEMPLATES, [] );
        foreach ( (array) $templates as $status => $tpl ) {
            self::register_string( 'Email Templates', $status . '_subject', $tpl['subject'] ?? '' );
            self::register_string( 'Email Templates', $status . '_body', $tpl['body'] ?? '' );
        }
        // Services (titles/descriptions)
        $services = get_posts( [ 'post_type' => 'tb_tour', 'numberposts' => -1, 'post_status' => 'publish' ] );
        foreach ( $services as $s ) {
            self::register_string( 'Services', 'service_' . $s->ID . '_title', $s->post_title );
            self::register_string( 'Services', 'service_' . $s->ID . '_description', $s->post_content );
        }
    }

    public static function register_string( $group, $name, $value ) {
        if ( function_exists( 'icl_register_string' ) ) {
            icl_register_string( 'tours-booking', $group . ' - ' . $name, (string) $value );
        }
        if ( function_exists( 'pll_register_string' ) ) {
            pll_register_string( $group . ' - ' . $name, (string) $value, 'tours-booking' );
        }
    }

    public static function translate_string( $group, $name, $value ) {
        $translated = $value;
        if ( function_exists( 'icl_t' ) ) {
            $translated = icl_t( 'tours-booking', $group . ' - ' . $name, (string) $value );
        }
        if ( function_exists( 'pll__' ) ) {
            $maybe = pll__( $group . ' - ' . $name );
            if ( $maybe && $maybe !== $group . ' - ' . $name ) {
                $translated = $maybe;
            }
        }
        return $translated;
    }
}