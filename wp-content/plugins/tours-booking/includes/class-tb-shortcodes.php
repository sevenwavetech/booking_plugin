<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Shortcodes {
    public static function register_shortcodes() {
        add_shortcode( 'tours_booking_form', [ __CLASS__, 'booking_form' ] );
    }

    public static function maybe_enqueue_assets() {
        if ( is_singular() ) {
            global $post;
            if ( has_shortcode( $post->post_content, 'tours_booking_form' ) ) {
                wp_enqueue_style( 'tb-public', TB_PLUGIN_URL . 'public/css/style.css', [], TB_PLUGIN_VERSION );
                wp_enqueue_script( 'tb-form', TB_PLUGIN_URL . 'public/js/form.js', [ 'jquery' ], TB_PLUGIN_VERSION, true );
                wp_localize_script( 'tb-form', 'TB_Form', [
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'tb_booking_nonce' ),
                ] );
            }
        }
    }

    public static function booking_form( $atts ) {
        $settings = TB_Settings::get_settings();
        $custom_fields = get_option( TB_Settings::OPTION_CUSTOM_FIELDS, [] );
        $tours = get_posts( [ 'post_type' => 'tb_tour', 'numberposts' => -1, 'post_status' => 'publish' ] );

        ob_start();
        ?>
        <form id="tb-booking-form" method="post" enctype="multipart/form-data">
            <div class="tb-field">
                <label><?php echo esc_html__( 'Tour', 'tours-booking' ); ?>*</label>
                <select name="tb_tour_id" required>
                    <option value="">--</option>
                    <?php foreach ( $tours as $tour ) : ?>
                        <option value="<?php echo esc_attr( $tour->ID ); ?>"><?php echo esc_html( $tour->post_title ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="tb-field">
                <label><?php echo esc_html__( 'Booking Date', 'tours-booking' ); ?>*</label>
                <input type="date" name="tb_booking_date" required />
            </div>
            <div class="tb-field">
                <label><?php echo esc_html__( 'Client Name', 'tours-booking' ); ?>*</label>
                <input type="text" name="tb_client_name" required />
            </div>
            <div class="tb-field">
                <label><?php echo esc_html__( 'Client Email', 'tours-booking' ); ?>*</label>
                <input type="email" name="tb_client_email" required />
            </div>
            <div class="tb-field">
                <label><?php echo esc_html__( 'Participants', 'tours-booking' ); ?>*</label>
                <input type="number" name="tb_participants" min="1" required />
            </div>

            <?php foreach ( $custom_fields as $field ) : ?>
                <div class="tb-field">
                    <label><?php echo esc_html( $field['label'] ); ?><?php echo ! empty( $field['required'] ) ? '*' : ''; ?></label>
                    <?php if ( 'text' === $field['type'] || 'email' === $field['type'] ) : ?>
                        <input type="<?php echo esc_attr( $field['type'] ); ?>" name="cf[<?php echo esc_attr( $field['id'] ); ?>]" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?> />
                    <?php elseif ( 'select' === $field['type'] ) : ?>
                        <select name="cf[<?php echo esc_attr( $field['id'] ); ?>]" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?>>
                            <?php foreach ( (array) $field['options'] as $opt ) : ?>
                                <option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ( 'file' === $field['type'] ) : ?>
                        <input type="file" name="cf_file_<?php echo esc_attr( $field['id'] ); ?>" <?php echo ! empty( $field['required'] ) ? 'required' : ''; ?> />
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <input type="hidden" name="action" value="tb_submit_booking" />
            <input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce( 'tb_booking_nonce' ) ); ?>" />
            <button type="submit" class="tb-btn"><?php echo esc_html__( 'Submit Booking', 'tours-booking' ); ?></button>
            <div class="tb-message" style="display:none"></div>
        </form>
        <?php
        return ob_get_clean();
    }
}