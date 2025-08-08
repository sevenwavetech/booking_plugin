<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
    <h2><?php echo $service ? esc_html__( 'Edit Tour', 'tours-booking' ) : esc_html__( 'Add Tour', 'tours-booking' ); ?></h2>
    <form method="post">
        <?php wp_nonce_field( 'tb_save_service', 'tb_service_nonce' ); ?>
        <input type="hidden" name="service_id" value="<?php echo $service ? intval( $service->ID ) : 0; ?>" />
        <table class="form-table">
            <tr><th><label for="title"><?php echo esc_html__( 'Title', 'tours-booking' ); ?></label></th><td><input type="text" id="title" name="title" class="regular-text" value="<?php echo esc_attr( $service ? $service->post_title : '' ); ?>" required/></td></tr>
            <tr><th><label for="description"><?php echo esc_html__( 'Description', 'tours-booking' ); ?></label></th><td><textarea id="description" name="description" class="large-text" rows="6"><?php echo esc_textarea( $service ? $service->post_content : '' ); ?></textarea></td></tr>
            <tr><th><label for="duration"><?php echo esc_html__( 'Duration', 'tours-booking' ); ?></label></th><td><input type="text" id="duration" name="duration" class="regular-text" value="<?php echo esc_attr( $duration ); ?>"/></td></tr>
            <tr><th><label for="cost"><?php echo esc_html__( 'Cost', 'tours-booking' ); ?></label></th><td><input type="number" step="0.01" id="cost" name="cost" class="regular-text" value="<?php echo esc_attr( $cost ); ?>"/></td></tr>
            <tr><th><label for="min_participants"><?php echo esc_html__( 'Minimum Participants', 'tours-booking' ); ?></label></th><td><input type="number" id="min_participants" name="min_participants" class="regular-text" value="<?php echo esc_attr( $minp ); ?>"/></td></tr>
            <tr><th><label for="max_participants"><?php echo esc_html__( 'Maximum Participants', 'tours-booking' ); ?></label></th><td><input type="number" id="max_participants" name="max_participants" class="regular-text" value="<?php echo esc_attr( $maxp ); ?>"/></td></tr>
            <tr><th><?php echo esc_html__( 'Assigned Guides', 'tours-booking' ); ?></th><td>
                <?php foreach ( $guides as $g ) : ?>
                    <label style="display:block"><input type="checkbox" name="assigned_guides[]" value="<?php echo esc_attr( $g->ID ); ?>" <?php checked( in_array( $g->ID, $assigned, true ) ); ?> /> <?php echo esc_html( $g->display_name ); ?></label>
                <?php endforeach; ?>
            </td></tr>
        </table>
        <p><button class="button button-primary" type="submit"><?php echo esc_html__( 'Save', 'tours-booking' ); ?></button></p>
    </form>
</div>