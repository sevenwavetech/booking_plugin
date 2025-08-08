<?php
/**
 * Plugin Name: Tours Booking
 * Description: Manage tours, guides, and bookings with secure uploads, custom fields, statuses, notifications, and calendar sync.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: tours-booking
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
if ( ! defined( 'TB_PLUGIN_VERSION' ) ) {
    define( 'TB_PLUGIN_VERSION', '1.0.0' );
}
if ( ! defined( 'TB_PLUGIN_FILE' ) ) {
    define( 'TB_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'TB_PLUGIN_BASENAME' ) ) {
    define( 'TB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'TB_PLUGIN_DIR' ) ) {
    define( 'TB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'TB_PLUGIN_URL' ) ) {
    define( 'TB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Autoload includes
require_once TB_PLUGIN_DIR . 'includes/class-tb-roles.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-post-types.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-settings.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-notifications.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-uploads.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-bookings.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-admin.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-shortcodes.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-calendar.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-gcal.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-employees.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-clients.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-form-builder.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-i18n.php';
require_once TB_PLUGIN_DIR . 'includes/class-tb-services.php';

class Tours_Booking_Plugin {

    public function __construct() {
        // Load text domain
        add_action( 'init', [ $this, 'load_textdomain' ] );

        // Init components
        add_action( 'init', [ 'TB_Post_Types', 'register' ] );
        add_action( 'init', [ 'TB_Roles', 'register_roles' ] );
        add_action( 'init', [ 'TB_Clients', 'register' ] );
        add_action( 'admin_init', [ 'TB_Roles', 'map_caps' ] );
        add_action( 'admin_menu', [ 'TB_Admin', 'register_menus' ] );
        add_action( 'admin_menu', [ 'TB_Employees', 'register_menu' ] );
        add_action( 'admin_menu', [ 'TB_Clients', 'add_menu' ] );
        add_action( 'admin_menu', [ 'TB_Form_Builder', 'register_menu' ] );
        add_action( 'admin_menu', [ 'TB_Services', 'register_menu' ] );

        // Assets
        add_action( 'admin_enqueue_scripts', [ 'TB_Admin', 'enqueue_assets' ] );
        add_action( 'wp_enqueue_scripts', [ 'TB_Shortcodes', 'maybe_enqueue_assets' ] );

        // Shortcodes
        add_action( 'init', [ 'TB_Shortcodes', 'register_shortcodes' ] );

        // i18n dynamic string registration
        add_action( 'init', [ 'TB_I18n', 'register_dynamic_strings' ] );

        // AJAX endpoints
        add_action( 'wp_ajax_tb_submit_booking', [ 'TB_Bookings', 'handle_booking_submission' ] );
        add_action( 'wp_ajax_nopriv_tb_submit_booking', [ 'TB_Bookings', 'handle_booking_submission' ] );

        add_action( 'wp_ajax_tb_get_calendar_events', [ 'TB_Calendar', 'ajax_get_events' ] );
        add_action( 'wp_ajax_tb_secure_download', [ 'TB_Uploads', 'secure_download' ] );
        add_action( 'admin_post_tb_secure_download', [ 'TB_Uploads', 'secure_download' ] );

        // Create private upload dir if missing
        add_action( 'init', [ 'TB_Uploads', 'ensure_private_upload_dir' ] );

        // Booking statuses and notifications
        add_action( 'init', [ 'TB_Bookings', 'register_default_statuses' ] );
        add_action( 'tb_booking_status_changed', [ 'TB_Notifications', 'send_status_email' ], 10, 3 );
        add_action( 'tb_booking_status_changed', [ 'TB_GCal', 'on_booking_status_changed' ], 10, 3 );

        // Detect status changes when taxonomy terms are updated
        add_action( 'set_object_terms', [ $this, 'detect_status_change' ], 10, 6 );

        // Guide experience
        add_action( 'admin_init', [ 'TB_Admin', 'restrict_guide_admin' ] );
        add_action( 'login_redirect', [ 'TB_Admin', 'guide_login_redirect' ], 10, 3 );

        // Default permission for guide file download
        add_filter( 'tb_user_can_download_file', [ $this, 'guide_can_download_uploaded_file' ], 10, 3 );

        // Manual GCal pull endpoint
        add_action( 'admin_post_tb_pull_gcal', [ 'TB_GCal', 'manual_pull' ] );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'tours-booking', false, dirname( TB_PLUGIN_BASENAME ) . '/languages' );
    }

    public static function activate() {
        // Register roles and CPTs before flushing
        TB_Roles::register_roles();
        TB_Post_Types::register();
        TB_Clients::register();
        flush_rewrite_rules();

        // Ensure private dir
        TB_Uploads::ensure_private_upload_dir();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function detect_status_change( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
        if ( 'tb_booking_status' !== $taxonomy ) { return; }
        $old = '';
        if ( ! empty( $old_tt_ids ) && is_array( $old_tt_ids ) ) {
            $old_term = get_term( (int) $old_tt_ids[0], 'tb_booking_status' );
            if ( $old_term && ! is_wp_error( $old_term ) ) { $old = $old_term->slug; }
        }
        $new = '';
        if ( ! empty( $terms ) && is_array( $terms ) ) {
            $new_term = get_term( (int) $terms[0], 'tb_booking_status' );
            if ( $new_term && ! is_wp_error( $new_term ) ) { $new = $new_term->slug; }
        }
        if ( $new !== $old ) {
            do_action( 'tb_booking_status_changed', (int) $object_id, $old, $new );
        }
    }

    public function guide_can_download_uploaded_file( $allowed, $user_id, $file ) {
        // Attempt to map file to a booking by checking meta values
        global $wpdb;
        $like = '%' . $wpdb->esc_like( basename( $file ) ) . '%';
        $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'tb_custom_fields' AND meta_value LIKE %s LIMIT 1", $like ) );
        if ( $post_id ) {
            $tour_id = (int) get_post_meta( (int) $post_id, 'tb_tour_id', true );
            $assigned = (array) get_post_meta( $tour_id, 'tb_assigned_guides', true );
            if ( in_array( (int) $user_id, $assigned, true ) ) {
                return true;
            }
        }
        return false;
    }
}

register_activation_hook( __FILE__, [ 'Tours_Booking_Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Tours_Booking_Plugin', 'deactivate' ] );

// Bootstrap plugin
new Tours_Booking_Plugin();