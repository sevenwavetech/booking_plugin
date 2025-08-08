<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Uploads {
    public static function get_private_upload_dir() {
        $upload_dir = wp_upload_dir();
        $base = trailingslashit( $upload_dir['basedir'] ) . 'tours_booking_private';
        return $base;
    }

    public static function ensure_private_upload_dir() {
        $dir = self::get_private_upload_dir();
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        // htaccess deny
        $htaccess = trailingslashit( $dir ) . '.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            $rules = "Order allow,deny\nDeny from all";
            file_put_contents( $htaccess, $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
        }
        // index.php to prevent listing
        $index = trailingslashit( $dir ) . 'index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
        }
    }

    public static function handle_private_upload( $file, $field_def ) {
        self::ensure_private_upload_dir();
        $allowed_mimes = isset( $field_def['mime_types'] ) ? (array) $field_def['mime_types'] : [ 'image/jpeg', 'image/png', 'application/pdf' ];
        $max_size_mb = isset( $field_def['max_size_mb'] ) ? intval( $field_def['max_size_mb'] ) : 5;
        $max_size = $max_size_mb * 1024 * 1024;

        if ( $file['size'] > $max_size ) {
            return new WP_Error( 'tb_file_too_large', sprintf( __( 'File too large. Maximum %d MB.', 'tours-booking' ), $max_size_mb ) );
        }

        $finfo = finfo_open( FILEINFO_MIME_TYPE );
        $mime = $finfo ? finfo_file( $finfo, $file['tmp_name'] ) : $file['type'];
        if ( $finfo ) { finfo_close( $finfo ); }

        if ( ! in_array( $mime, $allowed_mimes, true ) ) {
            return new WP_Error( 'tb_invalid_mime', __( 'Invalid file type.', 'tours-booking' ) );
        }

        add_filter( 'upload_dir', [ __CLASS__, 'filter_upload_dir' ] );
        $overrides = [ 'test_form' => false, 'mimes' => array_fill_keys( $allowed_mimes, '' ) ];
        $result = wp_handle_upload( $file, $overrides );
        remove_filter( 'upload_dir', [ __CLASS__, 'filter_upload_dir' ] );

        if ( isset( $result['error'] ) ) {
            return new WP_Error( 'tb_upload_error', $result['error'] );
        }

        return $result;
    }

    public static function filter_upload_dir( $dirs ) {
        $private = self::get_private_upload_dir();
        $upload_dir = wp_upload_dir();
        $dirs['path'] = $private;
        $dirs['url'] = trailingslashit( $upload_dir['baseurl'] ) . 'tours_booking_private'; // not public due to htaccess
        $dirs['subdir'] = '';
        return $dirs;
    }

    public static function secure_download() {
        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'Not authorized.', 'tours-booking' ) );
        }
        $file = isset( $_GET['file'] ) ? wp_normalize_path( wp_unslash( $_GET['file'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $file = realpath( $file );
        $base = realpath( self::get_private_upload_dir() );
        if ( ! $file || ! $base || strpos( $file, $base ) !== 0 || ! file_exists( $file ) ) {
            wp_die( esc_html__( 'File not found.', 'tours-booking' ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            // If guide, ensure file belongs to their assigned tours
            $can = apply_filters( 'tb_user_can_download_file', false, get_current_user_id(), $file );
            if ( ! $can ) {
                wp_die( esc_html__( 'Not authorized.', 'tours-booking' ) );
            }
        }
        nocache_headers();
        header( 'Content-Type: application/octet-stream' );
        header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '"' );
        header( 'Content-Length: ' . filesize( $file ) );
        readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
        exit;
    }
}