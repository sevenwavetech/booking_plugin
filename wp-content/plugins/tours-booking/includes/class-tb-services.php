<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Services {
    public static function register_menu() {
        add_submenu_page( 'tb_dashboard', __( 'Services', 'tours-booking' ), __( 'Services', 'tours-booking' ), 'manage_options', 'tb_services', [ __CLASS__, 'render_list' ] );
    }

    private static function sanitize_service_input() {
        return [
            'ID' => isset( $_POST['service_id'] ) ? intval( $_POST['service_id'] ) : 0,
            'title' => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
            'description' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
            'duration' => sanitize_text_field( wp_unslash( $_POST['duration'] ?? '' ) ),
            'cost' => floatval( wp_unslash( $_POST['cost'] ?? 0 ) ),
            'min_participants' => intval( wp_unslash( $_POST['min_participants'] ?? 0 ) ),
            'max_participants' => intval( wp_unslash( $_POST['max_participants'] ?? 0 ) ),
            'assigned_guides' => isset( $_POST['assigned_guides'] ) ? array_map( 'intval', (array) $_POST['assigned_guides'] ) : [],
        ];
    }

    private static function handle_actions() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        if ( isset( $_POST['tb_service_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_service_nonce'] ) ), 'tb_save_service' ) ) {
            $data = self::sanitize_service_input();
            if ( $data['ID'] ) {
                wp_update_post( [ 'ID' => $data['ID'], 'post_title' => $data['title'], 'post_content' => $data['description'], 'post_type' => 'tb_tour', 'post_status' => 'publish' ] );
                $id = $data['ID'];
            } else {
                $id = wp_insert_post( [ 'post_title' => $data['title'], 'post_content' => $data['description'], 'post_type' => 'tb_tour', 'post_status' => 'publish' ] );
            }
            if ( ! is_wp_error( $id ) ) {
                update_post_meta( $id, 'tb_duration', $data['duration'] );
                update_post_meta( $id, 'tb_cost', $data['cost'] );
                update_post_meta( $id, 'tb_min_participants', $data['min_participants'] );
                update_post_meta( $id, 'tb_max_participants', $data['max_participants'] );
                update_post_meta( $id, 'tb_assigned_guides', $data['assigned_guides'] );
                wp_safe_redirect( admin_url( 'admin.php?page=tb_services&updated=1' ) );
                exit;
            }
        }
    }

    public static function render_list() {
        self::handle_actions();
        $services = get_posts( [ 'post_type' => 'tb_tour', 'numberposts' => -1, 'post_status' => 'publish' ] );
        include TB_PLUGIN_DIR . 'admin/views/services-list.php';
    }

    public static function render_edit( $service_id = 0 ) {
        $service = $service_id ? get_post( $service_id ) : null;
        $duration = $service ? get_post_meta( $service_id, 'tb_duration', true ) : '';
        $cost = $service ? get_post_meta( $service_id, 'tb_cost', true ) : '';
        $minp = $service ? get_post_meta( $service_id, 'tb_min_participants', true ) : '';
        $maxp = $service ? get_post_meta( $service_id, 'tb_max_participants', true ) : '';
        $assigned = $service ? (array) get_post_meta( $service_id, 'tb_assigned_guides', true ) : [];
        $guides = get_users( [ 'role' => TB_Roles::ROLE_GUIDE ] );
        include TB_PLUGIN_DIR . 'admin/views/services-edit.php';
    }
}