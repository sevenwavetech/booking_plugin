<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
    <h2><?php echo $user ? esc_html__( 'Edit Employee', 'tours-booking' ) : esc_html__( 'Add Employee', 'tours-booking' ); ?></h2>
    <form method="post">
        <?php wp_nonce_field( 'tb_save_employee', 'tb_emp_nonce' ); ?>
        <input type="hidden" name="user_id" value="<?php echo $user ? intval( $user->ID ) : 0; ?>" />
        <table class="form-table">
            <tr><th><label for="display_name"><?php echo esc_html__( 'Name', 'tours-booking' ); ?></label></th><td><input type="text" id="display_name" name="display_name" class="regular-text" value="<?php echo esc_attr( $user ? $user->display_name : '' ); ?>" required/></td></tr>
            <tr><th><label for="email"><?php echo esc_html__( 'Email', 'tours-booking' ); ?></label></th><td><input type="email" id="email" name="email" class="regular-text" value="<?php echo esc_attr( $user ? $user->user_email : '' ); ?>" required/></td></tr>
            <tr><th><label for="phone"><?php echo esc_html__( 'Phone', 'tours-booking' ); ?></label></th><td><input type="text" id="phone" name="phone" class="regular-text" value="<?php echo esc_attr( $user ? get_user_meta( $user->ID, 'tb_phone', true ) : '' ); ?>"/></td></tr>
            <tr><th><label for="bio"><?php echo esc_html__( 'Bio', 'tours-booking' ); ?></label></th><td><textarea id="bio" name="bio" class="large-text" rows="5"><?php echo esc_textarea( $user ? get_user_meta( $user->ID, 'tb_bio', true ) : '' ); ?></textarea></td></tr>
            <tr><th><?php echo esc_html__( 'Assigned Tours', 'tours-booking' ); ?></th><td>
                <?php foreach ( $tours as $t ) : ?>
                    <label style="display:block"><input type="checkbox" name="assigned_tours[]" value="<?php echo esc_attr( $t->ID ); ?>" <?php checked( in_array( $t->ID, $assigned, true ) ); ?> /> <?php echo esc_html( $t->post_title ); ?></label>
                <?php endforeach; ?>
            </td></tr>
        </table>
        <p><button class="button button-primary" type="submit"><?php echo esc_html__( 'Save', 'tours-booking' ); ?></button></p>
    </form>
</div>