<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap">
    <h1><?php echo esc_html__( 'Calendar', 'tours-booking' ); ?></h1>
    <div class="tablenav top">
        <div class="alignleft actions">
            <label><?php echo esc_html__( 'Guide', 'tours-booking' ); ?>
                <?php $guides = get_users( [ 'role' => TB_Roles::ROLE_GUIDE ] ); ?>
                <select id="tb-filter-guide">
                    <option value="">—</option>
                    <?php foreach ( $guides as $g ) : ?>
                        <option value="<?php echo esc_attr( $g->ID ); ?>"><?php echo esc_html( $g->display_name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?php echo esc_html__( 'Status', 'tours-booking' ); ?>
                <?php $statuses = get_terms( [ 'taxonomy' => 'tb_booking_status', 'hide_empty' => false ] ); ?>
                <select id="tb-filter-status">
                    <option value="">—</option>
                    <?php foreach ( $statuses as $s ) : ?>
                        <option value="<?php echo esc_attr( $s->slug ); ?>"><?php echo esc_html( $s->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button" id="tb-refresh-cal"><?php echo esc_html__( 'Refresh', 'tours-booking' ); ?></button>
        </div>
    </div>
    <div id="tb-calendar" style="min-height:400px;border:1px solid #ccd0d4;background:#fff;padding:10px;"></div>
</div>