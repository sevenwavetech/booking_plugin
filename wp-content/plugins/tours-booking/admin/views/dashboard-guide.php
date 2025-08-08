<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$user_id = get_current_user_id();
$tours = get_posts([
    'post_type' => 'tb_tour',
    'numberposts' => -1,
    'post_status' => 'publish',
    'meta_query' => [
        [
            'key' => 'tb_assigned_guides',
            'value' => '"' . $user_id . '"',
            'compare' => 'LIKE',
        ],
    ],
]);
?>
<div class="wrap">
    <h1><?php echo esc_html__( 'My Dashboard', 'tours-booking' ); ?></h1>
    <h2><?php echo esc_html__( 'Assigned Tours', 'tours-booking' ); ?></h2>
    <ul>
        <?php foreach ( $tours as $tour ) : ?>
            <li><?php echo esc_html( $tour->post_title ); ?></li>
        <?php endforeach; ?>
    </ul>
    <h2><?php echo esc_html__( 'Upcoming Bookings', 'tours-booking' ); ?></h2>
    <ul>
        <?php
        $bookings = get_posts([
            'post_type' => 'tb_booking',
            'numberposts' => 20,
            'post_status' => 'publish',
            'meta_key' => 'tb_booking_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
        ]);
        foreach ( $bookings as $booking ) :
            $tour_id = (int) get_post_meta( $booking->ID, 'tb_tour_id', true );
            $assigned = (array) get_post_meta( $tour_id, 'tb_assigned_guides', true );
            if ( ! in_array( $user_id, $assigned, true ) ) { continue; }
            ?>
            <li><?php echo esc_html( get_the_title( $tour_id ) . ' - ' . get_post_meta( $booking->ID, 'tb_booking_date', true ) ); ?></li>
        <?php endforeach; ?>
    </ul>
</div>