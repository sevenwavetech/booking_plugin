<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Post_Types {
    public static function register() {
        self::register_tour_cpt();
        self::register_booking_cpt();
        self::register_booking_status_taxonomy();
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
        add_action( 'save_post', [ __CLASS__, 'save_meta' ], 10, 2 );
    }

    private static function register_tour_cpt() {
        $labels = [
            'name' => __( 'Tours', 'tours-booking' ),
            'singular_name' => __( 'Tour', 'tours-booking' ),
        ];
        register_post_type( 'tb_tour', [
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'show_in_menu' => false,
            'supports' => [ 'title', 'editor' ],
        ] );
    }

    private static function register_booking_cpt() {
        $labels = [
            'name' => __( 'Bookings', 'tours-booking' ),
            'singular_name' => __( 'Booking', 'tours-booking' ),
        ];
        register_post_type( 'tb_booking', [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => [ 'title', 'editor' ],
        ] );
    }

    private static function register_booking_status_taxonomy() {
        $labels = [
            'name' => __( 'Booking Statuses', 'tours-booking' ),
            'singular_name' => __( 'Booking Status', 'tours-booking' ),
        ];
        register_taxonomy( 'tb_booking_status', [ 'tb_booking' ], [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'hierarchical' => false,
            'show_in_quick_edit' => true,
            'meta_box_cb' => null,
        ] );
    }

    public static function add_meta_boxes() {
        add_meta_box( 'tb_tour_details', __( 'Tour Details', 'tours-booking' ), [ __CLASS__, 'render_tour_meta_box' ], 'tb_tour', 'normal', 'default' );
        add_meta_box( 'tb_tour_guides', __( 'Assigned Guides', 'tours-booking' ), [ __CLASS__, 'render_tour_guides_meta_box' ], 'tb_tour', 'side', 'default' );

        add_meta_box( 'tb_booking_details', __( 'Booking Details', 'tours-booking' ), [ __CLASS__, 'render_booking_meta_box' ], 'tb_booking', 'normal', 'default' );
        add_meta_box( 'tb_booking_client', __( 'Client', 'tours-booking' ), [ __CLASS__, 'render_booking_client_meta_box' ], 'tb_booking', 'side', 'default' );
    }

    public static function render_tour_meta_box( $post ) {
        wp_nonce_field( 'tb_save_tour_meta', 'tb_tour_nonce' );
        $duration = get_post_meta( $post->ID, 'tb_duration', true );
        $cost = get_post_meta( $post->ID, 'tb_cost', true );
        $min_participants = get_post_meta( $post->ID, 'tb_min_participants', true );
        $max_participants = get_post_meta( $post->ID, 'tb_max_participants', true );
        ?>
        <p>
            <label for="tb_duration"><strong><?php echo esc_html__( 'Duration', 'tours-booking' ); ?></strong></label>
            <input type="text" id="tb_duration" name="tb_duration" value="<?php echo esc_attr( $duration ); ?>" class="widefat" />
        </p>
        <p>
            <label for="tb_cost"><strong><?php echo esc_html__( 'Cost', 'tours-booking' ); ?></strong></label>
            <input type="number" step="0.01" id="tb_cost" name="tb_cost" value="<?php echo esc_attr( $cost ); ?>" class="widefat" />
        </p>
        <p>
            <label for="tb_min_participants"><strong><?php echo esc_html__( 'Minimum Participants', 'tours-booking' ); ?></strong></label>
            <input type="number" id="tb_min_participants" name="tb_min_participants" value="<?php echo esc_attr( $min_participants ); ?>" class="widefat" />
        </p>
        <p>
            <label for="tb_max_participants"><strong><?php echo esc_html__( 'Maximum Participants', 'tours-booking' ); ?></strong></label>
            <input type="number" id="tb_max_participants" name="tb_max_participants" value="<?php echo esc_attr( $max_participants ); ?>" class="widefat" />
        </p>
        <?php
    }

    public static function render_tour_guides_meta_box( $post ) {
        wp_nonce_field( 'tb_save_tour_meta', 'tb_tour_nonce' );
        $assigned = (array) get_post_meta( $post->ID, 'tb_assigned_guides', true );
        $users = get_users( [ 'role' => TB_Roles::ROLE_GUIDE, 'fields' => [ 'ID', 'display_name' ] ] );
        ?>
        <p><?php echo esc_html__( 'Assign Guides to this tour:', 'tours-booking' ); ?></p>
        <select name="tb_assigned_guides[]" multiple size="5" style="width:100%">
        <?php foreach ( $users as $user ) : ?>
            <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( in_array( $user->ID, $assigned, true ) ); ?>><?php echo esc_html( $user->display_name ); ?></option>
        <?php endforeach; ?>
        </select>
        <p class="description"><?php echo esc_html__( 'Hold CTRL/Command to select multiple guides.', 'tours-booking' ); ?></p>
        <?php
    }

    public static function render_booking_meta_box( $post ) {
        wp_nonce_field( 'tb_save_booking_meta', 'tb_booking_nonce' );
        $tour_id = get_post_meta( $post->ID, 'tb_tour_id', true );
        $participants = get_post_meta( $post->ID, 'tb_participants', true );
        $date = get_post_meta( $post->ID, 'tb_booking_date', true );
        $tours = get_posts( [ 'post_type' => 'tb_tour', 'numberposts' => -1, 'post_status' => 'publish' ] );
        ?>
        <p>
            <label><strong><?php echo esc_html__( 'Tour', 'tours-booking' ); ?></strong></label>
            <select name="tb_tour_id" class="widefat">
                <option value=""><?php echo esc_html__( 'Select a tour', 'tours-booking' ); ?></option>
                <?php foreach ( $tours as $tour ) : ?>
                    <option value="<?php echo esc_attr( $tour->ID ); ?>" <?php selected( (int) $tour_id, (int) $tour->ID ); ?>><?php echo esc_html( $tour->post_title ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label><strong><?php echo esc_html__( 'Participants', 'tours-booking' ); ?></strong></label>
            <input type="number" name="tb_participants" value="<?php echo esc_attr( $participants ); ?>" class="widefat" />
        </p>
        <p>
            <label><strong><?php echo esc_html__( 'Booking Date', 'tours-booking' ); ?></strong></label>
            <input type="date" name="tb_booking_date" value="<?php echo esc_attr( $date ); ?>" class="widefat" />
        </p>
        <?php
    }

    public static function render_booking_client_meta_box( $post ) {
        $client_id = get_post_meta( $post->ID, 'tb_client_id', true );
        $clients = get_posts( [ 'post_type' => 'tb_client', 'numberposts' => -1, 'post_status' => 'publish' ] );
        ?>
        <p>
            <label><strong><?php echo esc_html__( 'Client', 'tours-booking' ); ?></strong></label>
            <select name="tb_client_id" class="widefat">
                <option value="">—</option>
                <?php foreach ( $clients as $c ) : ?>
                    <option value="<?php echo esc_attr( $c->ID ); ?>" <?php selected( (int) $client_id, (int) $c->ID ); ?>><?php echo esc_html( $c->post_title . ' (' . get_post_meta( $c->ID, 'tb_client_email', true ) . ')' ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public static function save_meta( $post_id, $post ) {
        if ( $post->post_type === 'tb_tour' ) {
            if ( ! isset( $_POST['tb_tour_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_tour_nonce'] ) ), 'tb_save_tour_meta' ) ) {
                return;
            }
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
            if ( ! current_user_can( 'edit_post', $post_id ) ) return;

            $fields = [
                'tb_duration' => 'sanitize_text_field',
                'tb_cost' => 'floatval',
                'tb_min_participants' => 'intval',
                'tb_max_participants' => 'intval',
            ];
            foreach ( $fields as $key => $callback ) {
                if ( isset( $_POST[ $key ] ) ) {
                    $value = call_user_func( $callback, wp_unslash( $_POST[ $key ] ) );
                    update_post_meta( $post_id, $key, $value );
                }
            }
            $guides = isset( $_POST['tb_assigned_guides'] ) ? array_map( 'intval', (array) $_POST['tb_assigned_guides'] ) : [];
            update_post_meta( $post_id, 'tb_assigned_guides', $guides );
        }

        if ( $post->post_type === 'tb_booking' ) {
            if ( ! isset( $_POST['tb_booking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tb_booking_nonce'] ) ), 'tb_save_booking_meta' ) ) {
                return;
            }
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
            if ( ! current_user_can( 'edit_post', $post_id ) ) return;

            $fields = [
                'tb_tour_id' => 'intval',
                'tb_client_id' => 'intval',
                'tb_participants' => 'intval',
                'tb_booking_date' => 'sanitize_text_field',
            ];
            foreach ( $fields as $key => $callback ) {
                if ( isset( $_POST[ $key ] ) ) {
                    $value = call_user_func( $callback, wp_unslash( $_POST[ $key ] ) );
                    update_post_meta( $post_id, $key, $value );
                }
            }
        }
    }
}