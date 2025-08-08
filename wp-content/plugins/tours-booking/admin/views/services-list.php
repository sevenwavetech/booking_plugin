<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
    <h1><?php echo esc_html__( 'Services', 'tours-booking' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=tb_services&action=add' ) ); ?>" class="page-title-action"><?php echo esc_html__( 'Add New', 'tours-booking' ); ?></a></h1>
    <?php if ( isset( $_GET['action'] ) && 'add' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) : // phpcs:ignore ?>
        <?php TB_Services::render_edit( 0 ); ?>
    <?php elseif ( isset( $_GET['action'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) && ! empty( $_GET['service_id'] ) ) : // phpcs:ignore ?>
        <?php TB_Services::render_edit( intval( $_GET['service_id'] ) ); // phpcs:ignore ?>
    <?php else : ?>
        <table class="widefat">
            <thead><tr><th><?php echo esc_html__( 'Title', 'tours-booking' ); ?></th><th><?php echo esc_html__( 'Duration', 'tours-booking' ); ?></th><th><?php echo esc_html__( 'Cost', 'tours-booking' ); ?></th><th><?php echo esc_html__( 'Actions', 'tours-booking' ); ?></th></tr></thead>
            <tbody>
                <?php foreach ( $services as $s ) : ?>
                    <tr>
                        <td><?php echo esc_html( $s->post_title ); ?></td>
                        <td><?php echo esc_html( get_post_meta( $s->ID, 'tb_duration', true ) ); ?></td>
                        <td><?php echo esc_html( get_post_meta( $s->ID, 'tb_cost', true ) ); ?></td>
                        <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=tb_services&action=edit&service_id=' . $s->ID ) ); ?>"><?php echo esc_html__( 'Edit', 'tours-booking' ); ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>