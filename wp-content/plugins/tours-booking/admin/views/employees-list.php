<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
    <h1><?php echo esc_html__( 'Employees', 'tours-booking' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=tb_employees&action=add' ) ); ?>" class="page-title-action"><?php echo esc_html__( 'Add New', 'tours-booking' ); ?></a></h1>
    <?php if ( isset( $_GET['action'] ) && 'add' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) : // phpcs:ignore ?>
        <?php TB_Employees::render_edit( 0 ); ?>
    <?php elseif ( isset( $_GET['action'] ) && 'edit' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) && ! empty( $_GET['user_id'] ) ) : // phpcs:ignore ?>
        <?php TB_Employees::render_edit( intval( $_GET['user_id'] ) ); // phpcs:ignore ?>
    <?php else : ?>
        <table class="widefat">
            <thead><tr><th><?php echo esc_html__( 'Name', 'tours-booking' ); ?></th><th><?php echo esc_html__( 'Email', 'tours-booking' ); ?></th><th><?php echo esc_html__( 'Actions', 'tours-booking' ); ?></th></tr></thead>
            <tbody>
                <?php foreach ( $guides as $g ) : ?>
                    <tr>
                        <td><?php echo esc_html( $g->display_name ); ?></td>
                        <td><?php echo esc_html( $g->user_email ); ?></td>
                        <td><a href="<?php echo esc_url( admin_url( 'admin.php?page=tb_employees&action=edit&user_id=' . $g->ID ) ); ?>"><?php echo esc_html__( 'Edit', 'tours-booking' ); ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>