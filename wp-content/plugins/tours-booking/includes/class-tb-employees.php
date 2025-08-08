<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Employees {
    public static function register_menu() {
        add_submenu_page( 'tb_dashboard', __( 'Employees', 'tours-booking' ), __( 'Employees', 'tours-booking' ), 'manage_options', 'tb_employees', [ __CLASS__, 'render_list' ] );
    }

    private static function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        if ( isset( $_POST['tb_emp_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_emp_nonce'] ) ), 'tb_save_employee' ) ) {
            $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
            $display_name = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
            $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
            $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
            $bio = wp_kses_post( wp_unslash( $_POST['bio'] ?? '' ) );
            $assigned_tours = isset( $_POST['assigned_tours'] ) ? array_map( 'intval', (array) $_POST['assigned_tours'] ) : [];

            if ( $user_id ) {
                wp_update_user( [ 'ID' => $user_id, 'display_name' => $display_name, 'user_email' => $email ] );
            } else {
                $password = wp_generate_password( 12, true );
                $user_id = wp_insert_user( [
                    'user_login' => sanitize_user( $email ),
                    'user_email' => $email,
                    'display_name' => $display_name,
                    'role' => TB_Roles::ROLE_GUIDE,
                    'user_pass' => $password,
                ] );
                if ( ! is_wp_error( $user_id ) ) {
                    wp_new_user_notification( $user_id, null, 'both' );
                }
            }
            if ( ! is_wp_error( $user_id ) ) {
                update_user_meta( $user_id, 'tb_phone', $phone );
                update_user_meta( $user_id, 'tb_bio', $bio );
                // Sync assignments: update each tour meta tb_assigned_guides
                self::sync_tour_assignments( $user_id, $assigned_tours );
                wp_safe_redirect( admin_url( 'admin.php?page=tb_employees&updated=1' ) );
                exit;
            }
        }
    }

    private static function sync_tour_assignments( $user_id, $assigned_tours ) {
        $all_tours = get_posts( [ 'post_type' => 'tb_tour', 'numberposts' => -1, 'post_status' => 'publish' ] );
        foreach ( $all_tours as $tour ) {
            $list = (array) get_post_meta( $tour->ID, 'tb_assigned_guides', true );
            $list = array_map( 'intval', $list );
            if ( in_array( $tour->ID, $assigned_tours, true ) ) {
                if ( ! in_array( $user_id, $list, true ) ) { $list[] = $user_id; }
            } else {
                $list = array_diff( $list, [ $user_id ] );
            }
            update_post_meta( $tour->ID, 'tb_assigned_guides', array_values( $list ) );
        }
    }

    public static function render_list() {
        self::handle_actions();
        $guides = get_users( [ 'role' => TB_Roles::ROLE_GUIDE ] );
        include TB_PLUGIN_DIR . 'admin/views/employees-list.php';
    }

    public static function render_edit( $user_id = 0 ) {
        $user = $user_id ? get_user_by( 'id', $user_id ) : null;
        $tours = get_posts( [ 'post_type' => 'tb_tour', 'numberposts' => -1, 'post_status' => 'publish' ] );
        $assigned = [];
        if ( $user_id ) {
            foreach ( $tours as $tour ) {
                $g = (array) get_post_meta( $tour->ID, 'tb_assigned_guides', true );
                if ( in_array( $user_id, array_map( 'intval', $g ), true ) ) { $assigned[] = $tour->ID; }
            }
        }
        include TB_PLUGIN_DIR . 'admin/views/employees-edit.php';
    }
}