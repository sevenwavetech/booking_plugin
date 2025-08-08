<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Admin {
    public static function register_menus() {
        add_menu_page(
            __( 'Tours Booking', 'tours-booking' ),
            __( 'Tours Booking', 'tours-booking' ),
            'read',
            'tb_dashboard',
            [ __CLASS__, 'render_dashboard' ],
            'dashicons-calendar-alt',
            26
        );

        add_submenu_page( 'tb_dashboard', __( 'Tours', 'tours-booking' ), __( 'Tours', 'tours-booking' ), 'manage_options', 'edit.php?post_type=tb_tour' );
        add_submenu_page( 'tb_dashboard', __( 'Bookings', 'tours-booking' ), __( 'Bookings', 'tours-booking' ), 'manage_options', 'edit.php?post_type=tb_booking' );
        add_submenu_page( 'tb_dashboard', __( 'Calendar', 'tours-booking' ), __( 'Calendar', 'tours-booking' ), 'manage_options', 'tb_calendar', [ __CLASS__, 'render_calendar' ] );
        add_submenu_page( 'tb_dashboard', __( 'Settings', 'tours-booking' ), __( 'Settings', 'tours-booking' ), 'manage_options', 'tb_settings', [ 'TB_Settings', 'render_settings_page' ] );
    }

    public static function render_dashboard() {
        if ( current_user_can( 'manage_options' ) ) {
            include TB_PLUGIN_DIR . 'admin/views/dashboard-admin.php';
        } else {
            include TB_PLUGIN_DIR . 'admin/views/dashboard-guide.php';
        }
    }

    public static function render_calendar() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'tours-booking' ) );
        }
        include TB_PLUGIN_DIR . 'admin/views/calendar.php';
    }

    public static function enqueue_assets( $hook ) {
        // Only load on our pages
        if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } else {
            $page = '';
        }

        if ( in_array( $page, [ 'tb_dashboard', 'tb_calendar', 'tb_settings' ], true ) ) {
            wp_enqueue_style( 'tb-admin', TB_PLUGIN_URL . 'admin/css/admin.css', [], TB_PLUGIN_VERSION );
            wp_enqueue_script( 'tb-admin', TB_PLUGIN_URL . 'admin/js/admin.js', [ 'jquery', 'jquery-ui-sortable' ], TB_PLUGIN_VERSION, true );
        }

        if ( 'tb_calendar' === $page ) {
            wp_enqueue_script( 'tb-calendar', TB_PLUGIN_URL . 'admin/js/calendar.js', [ 'jquery' ], TB_PLUGIN_VERSION, true );
            wp_localize_script( 'tb-calendar', 'TB_Calendar', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'tb_calendar_nonce' ),
            ] );
        }
    }

    public static function restrict_guide_admin() {
        if ( current_user_can( TB_Roles::ROLE_GUIDE ) && ! current_user_can( 'manage_options' ) ) {
            // Remove menus
            remove_menu_page( 'index.php' );
            remove_menu_page( 'edit.php' );
            remove_menu_page( 'upload.php' );
            remove_menu_page( 'edit.php?post_type=page' );
            remove_menu_page( 'edit-comments.php' );
            remove_menu_page( 'themes.php' );
            remove_menu_page( 'plugins.php' );
            remove_menu_page( 'users.php' );
            remove_menu_page( 'tools.php' );
            remove_menu_page( 'options-general.php' );
        }
    }

    public static function guide_login_redirect( $redirect_to, $request, $user ) {
        if ( $user && is_a( $user, 'WP_User' ) ) {
            if ( in_array( TB_Roles::ROLE_GUIDE, (array) $user->roles, true ) ) {
                return admin_url( 'admin.php?page=tb_dashboard' );
            }
        }
        return $redirect_to;
    }
}