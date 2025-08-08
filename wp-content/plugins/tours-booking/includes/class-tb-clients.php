<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Clients {
    public static function register() {
        $labels = [
            'name' => __( 'Clients', 'tours-booking' ),
            'singular_name' => __( 'Client', 'tours-booking' ),
        ];
        register_post_type( 'tb_client', [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => [ 'title' ],
        ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post_tb_client', [ __CLASS__, 'save_meta' ] );
    }

    public static function add_menu() {
        add_submenu_page( 'tb_dashboard', __( 'Clients', 'tours-booking' ), __( 'Clients', 'tours-booking' ), 'manage_options', 'edit.php?post_type=tb_client' );
    }

    public static function add_meta_boxes() {
        add_meta_box( 'tb_client_details', __( 'Client Details', 'tours-booking' ), [ __CLASS__, 'render_meta' ], 'tb_client', 'normal', 'default' );
    }

    public static function render_meta( $post ) {
        wp_nonce_field( 'tb_save_client_meta', 'tb_client_nonce' );
        $email = get_post_meta( $post->ID, 'tb_client_email', true );
        $phone = get_post_meta( $post->ID, 'tb_client_phone', true );
        $notes = get_post_meta( $post->ID, 'tb_client_notes', true );
        ?>
        <p><label><strong><?php echo esc_html__( 'Email', 'tours-booking' ); ?></strong></label><input type="email" name="tb_client_email" class="widefat" value="<?php echo esc_attr( $email ); ?>"/></p>
        <p><label><strong><?php echo esc_html__( 'Phone', 'tours-booking' ); ?></strong></label><input type="text" name="tb_client_phone" class="widefat" value="<?php echo esc_attr( $phone ); ?>"/></p>
        <p><label><strong><?php echo esc_html__( 'Notes', 'tours-booking' ); ?></strong></label><textarea name="tb_client_notes" class="widefat" rows="4"><?php echo esc_textarea( $notes ); ?></textarea></p>
        <?php
    }

    public static function save_meta( $post_id ) {
        if ( ! isset( $_POST['tb_client_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_client_nonce'] ) ), 'tb_save_client_meta' ) ) { return; }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
        if ( isset( $_POST['tb_client_email'] ) ) update_post_meta( $post_id, 'tb_client_email', sanitize_email( wp_unslash( $_POST['tb_client_email'] ) ) );
        if ( isset( $_POST['tb_client_phone'] ) ) update_post_meta( $post_id, 'tb_client_phone', sanitize_text_field( wp_unslash( $_POST['tb_client_phone'] ) ) );
        if ( isset( $_POST['tb_client_notes'] ) ) update_post_meta( $post_id, 'tb_client_notes', wp_kses_post( wp_unslash( $_POST['tb_client_notes'] ) ) );
    }

    public static function find_or_create_by_email( $name, $email, $phone = '' ) {
        $existing = get_posts( [
            'post_type' => 'tb_client',
            'numberposts' => 1,
            'meta_key' => 'tb_client_email',
            'meta_value' => $email,
            'post_status' => 'any',
        ] );
        if ( $existing ) { return $existing[0]->ID; }
        $id = wp_insert_post( [ 'post_type' => 'tb_client', 'post_status' => 'publish', 'post_title' => $name ] );
        if ( $id && ! is_wp_error( $id ) ) {
            update_post_meta( $id, 'tb_client_email', sanitize_email( $email ) );
            if ( $phone ) update_post_meta( $id, 'tb_client_phone', sanitize_text_field( $phone ) );
        }
        return $id;
    }
}