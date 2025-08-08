<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
    <h1><?php echo esc_html__( 'Booking Form Builder', 'tours-booking' ); ?></h1>
    <form method="post">
        <?php wp_nonce_field( 'tb_save_form_builder', 'tb_form_builder_nonce' ); ?>
        <h2><?php echo esc_html__( 'Custom Fields', 'tours-booking' ); ?></h2>
        <p class="description"><?php echo esc_html__( 'Drag to reorder. Click “Add Field” to create new fields.', 'tours-booking' ); ?></p>
        <ul id="tb-fields-list" class="tb-sortable">
            <?php foreach ( $custom_fields as $field ) : ?>
                <li class="tb-field" data-id="<?php echo esc_attr( $field['id'] ); ?>" data-json='<?php echo wp_json_encode( $field ); ?>'>
                    <strong><?php echo esc_html( $field['label'] ); ?></strong> (<?php echo esc_html( $field['type'] ); ?>)
                </li>
            <?php endforeach; ?>
        </ul>
        <input type="hidden" id="tb_custom_fields_json" name="tb_custom_fields_json" value='<?php echo esc_attr( wp_json_encode( $custom_fields ) ); ?>' />
        <div id="tb-field-editor"></div>
        <p><button type="submit" class="button button-primary"><?php echo esc_html__( 'Save Form', 'tours-booking' ); ?></button></p>
    </form>
</div>