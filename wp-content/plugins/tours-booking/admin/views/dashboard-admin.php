<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
    <h1><?php echo esc_html__( 'Tours Booking Dashboard', 'tours-booking' ); ?></h1>
    <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=tb_tour' ) ); ?>"><?php echo esc_html__( 'Add Tour', 'tours-booking' ); ?></a>
    <a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=tb_booking' ) ); ?>"><?php echo esc_html__( 'Add Booking', 'tours-booking' ); ?></a>
    <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=tb_calendar' ) ); ?>"><?php echo esc_html__( 'View Calendar', 'tours-booking' ); ?></a></p>
</div>