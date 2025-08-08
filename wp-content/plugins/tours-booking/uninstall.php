<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$settings = get_option( 'tb_settings', [] );
if ( empty( $settings['delete_on_uninstall'] ) ) {
    return;
}

// Delete options
delete_option( 'tb_settings' );
delete_option( 'tb_custom_fields' );
delete_option( 'tb_email_templates' );

// Delete CPT posts
$types = [ 'tb_booking', 'tb_tour' ];
foreach ( $types as $pt ) {
    $posts = get_posts( [ 'post_type' => $pt, 'numberposts' => -1, 'post_status' => 'any' ] );
    foreach ( $posts as $p ) {
        wp_delete_post( $p->ID, true );
    }
}

// Delete taxonomy terms
$terms = get_terms( [ 'taxonomy' => 'tb_booking_status', 'hide_empty' => false ] );
if ( ! is_wp_error( $terms ) ) {
    foreach ( $terms as $t ) {
        wp_delete_term( $t->term_id, 'tb_booking_status' );
    }
}

// Delete private upload directory files
$upload_dir = wp_upload_dir();
$dir = trailingslashit( $upload_dir['basedir'] ) . 'tours_booking_private';
if ( file_exists( $dir ) ) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ( $files as $fileinfo ) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo( $fileinfo->getRealPath() );
    }
    rmdir( $dir );
}