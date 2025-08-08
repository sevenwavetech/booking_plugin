<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$settings = TB_Settings::get_settings();
$custom_fields = get_option( TB_Settings::OPTION_CUSTOM_FIELDS, [] );
$email_templates = get_option( TB_Settings::OPTION_EMAIL_TEMPLATES, [] );
$statuses = get_terms( [ 'taxonomy' => 'tb_booking_status', 'hide_empty' => false ] );
?>
<div class="wrap">
    <h1><?php echo esc_html__( 'Tours Booking Settings', 'tours-booking' ); ?></h1>
    <h2 class="nav-tab-wrapper">
        <a class="nav-tab <?php echo $tab==='general'?'nav-tab-active':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=tb_settings&tab=general' ) ); ?>"><?php echo esc_html__( 'General', 'tours-booking' ); ?></a>
        <a class="nav-tab <?php echo $tab==='form'?'nav-tab-active':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=tb_settings&tab=form' ) ); ?>"><?php echo esc_html__( 'Booking Form', 'tours-booking' ); ?></a>
        <a class="nav-tab <?php echo $tab==='notifications'?'nav-tab-active':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=tb_settings&tab=notifications' ) ); ?>"><?php echo esc_html__( 'Notifications', 'tours-booking' ); ?></a>
        <a class="nav-tab <?php echo $tab==='payments'?'nav-tab-active':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=tb_settings&tab=payments' ) ); ?>"><?php echo esc_html__( 'Payments', 'tours-booking' ); ?></a>
        <a class="nav-tab <?php echo $tab==='integrations'?'nav-tab-active':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=tb_settings&tab=integrations' ) ); ?>"><?php echo esc_html__( 'Integrations', 'tours-booking' ); ?></a>
        <a class="nav-tab <?php echo $tab==='advanced'?'nav-tab-active':''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=tb_settings&tab=advanced' ) ); ?>"><?php echo esc_html__( 'Advanced', 'tours-booking' ); ?></a>
    </h2>

    <form method="post">
        <?php wp_nonce_field( 'tb_save_settings', 'tb_settings_nonce' ); ?>

        <?php if ( 'general' === $tab ) : ?>
            <table class="form-table">
                <tr>
                    <th><label for="business_name"><?php echo esc_html__( 'Business Name', 'tours-booking' ); ?></label></th>
                    <td><input type="text" id="business_name" name="business_name" value="<?php echo esc_attr( $settings['business_name'] ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="business_email"><?php echo esc_html__( 'Business Email', 'tours-booking' ); ?></label></th>
                    <td><input type="email" id="business_email" name="business_email" value="<?php echo esc_attr( $settings['business_email'] ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="default_currency"><?php echo esc_html__( 'Default Currency', 'tours-booking' ); ?></label></th>
                    <td><input type="text" id="default_currency" name="default_currency" value="<?php echo esc_attr( $settings['default_currency'] ); ?>" class="small-text" /></td>
                </tr>
                <tr>
                    <th><label for="timezone"><?php echo esc_html__( 'Time Zone', 'tours-booking' ); ?></label></th>
                    <td><input type="text" id="timezone" name="timezone" value="<?php echo esc_attr( $settings['timezone'] ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="date_format"><?php echo esc_html__( 'Date Format', 'tours-booking' ); ?></label></th>
                    <td><input type="text" id="date_format" name="date_format" value="<?php echo esc_attr( $settings['date_format'] ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="time_format"><?php echo esc_html__( 'Time Format', 'tours-booking' ); ?></label></th>
                    <td><input type="text" id="time_format" name="time_format" value="<?php echo esc_attr( $settings['time_format'] ); ?>" class="regular-text" /></td>
                </tr>
            </table>
        <?php elseif ( 'form' === $tab ) : ?>
            <h2><?php echo esc_html__( 'Custom Fields', 'tours-booking' ); ?></h2>
            <p class="description"><?php echo esc_html__( 'Drag to reorder. Add/edit fields below.', 'tours-booking' ); ?></p>
            <ul id="tb-fields-list" class="tb-sortable">
                <?php foreach ( $custom_fields as $field ) : ?>
                    <li class="tb-field" data-id="<?php echo esc_attr( $field['id'] ); ?>" data-json='<?php echo wp_json_encode( $field ); ?>'>
                        <strong><?php echo esc_html( $field['label'] ); ?></strong> (<?php echo esc_html( $field['type'] ); ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
            <input type="hidden" id="tb_custom_fields_json" name="tb_custom_fields_json" value='<?php echo esc_attr( wp_json_encode( $custom_fields ) ); ?>' />
            <div id="tb-field-editor"></div>
            <hr />
            <h2><?php echo esc_html__( 'Default Booking Status', 'tours-booking' ); ?></h2>
            <select name="default_status">
                <?php foreach ( $statuses as $status ) : ?>
                    <option value="<?php echo esc_attr( $status->slug ); ?>" <?php selected( $settings['default_status'], $status->slug ); ?>><?php echo esc_html( $status->name ); ?></option>
                <?php endforeach; ?>
            </select>
        <?php elseif ( 'notifications' === $tab ) : ?>
            <p class="description"><?php echo esc_html__( 'Templates are sent to Client and assigned Guides on events: created, updated, status changes (pending, approved, canceled). Use placeholders: [client_name], [client_email], [tour_name], [booking_date], [booking_start], [booking_end], [participants], [booking_id].', 'tours-booking' ); ?></p>
            <div id="tb-templates">
                <?php
                $events = array_merge( [ (object) ['slug' => 'created', 'name' => __( 'Created', 'tours-booking' ) ], (object) ['slug' => 'updated', 'name' => __( 'Updated', 'tours-booking' ) ] ], $statuses );
                foreach ( $events as $status ) :
                    $key = is_object( $status ) ? $status->slug : $status;
                    $label = is_object( $status ) ? $status->name : ucfirst( $status );
                    $tplClient = $email_templates[ $key ]['client'] ?? [ 'subject' => '', 'body' => '' ];
                    $tplGuide  = $email_templates[ $key ]['guide'] ?? [ 'subject' => '', 'body' => '' ];
                ?>
                    <div class="tb-template" data-status="<?php echo esc_attr( $key ); ?>">
                        <h3><?php echo esc_html( $label ); ?></h3>
                        <h4><?php echo esc_html__( 'Client Email', 'tours-booking' ); ?></h4>
                        <p><input type="text" class="large-text tb-template-subject" data-role="client" placeholder="<?php echo esc_attr__( 'Subject', 'tours-booking' ); ?>" value="<?php echo esc_attr( $tplClient['subject'] ); ?>" /></p>
                        <p><textarea class="large-text tb-template-body" data-role="client" rows="6" placeholder="<?php echo esc_attr__( 'Body', 'tours-booking' ); ?>"><?php echo esc_textarea( $tplClient['body'] ); ?></textarea></p>
                        <h4><?php echo esc_html__( 'Guide Email', 'tours-booking' ); ?></h4>
                        <p><input type="text" class="large-text tb-template-subject" data-role="guide" placeholder="<?php echo esc_attr__( 'Subject', 'tours-booking' ); ?>" value="<?php echo esc_attr( $tplGuide['subject'] ); ?>" /></p>
                        <p><textarea class="large-text tb-template-body" data-role="guide" rows="6" placeholder="<?php echo esc_attr__( 'Body', 'tours-booking' ); ?>"><?php echo esc_textarea( $tplGuide['body'] ); ?></textarea></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" id="tb_email_templates_json" name="tb_email_templates_json" value='<?php echo esc_attr( wp_json_encode( $email_templates ) ); ?>' />
            <hr />
            <h2><?php echo esc_html__( 'From Settings', 'tours-booking' ); ?></h2>
            <table class="form-table">
                <tr><th><label for="from_name"><?php echo esc_html__( 'From Name', 'tours-booking' ); ?></label></th><td><input id="from_name" name="from_name" type="text" class="regular-text" value="<?php echo esc_attr( $settings['from_name'] ); ?>" /></td></tr>
                <tr><th><label for="from_email"><?php echo esc_html__( 'From Email', 'tours-booking' ); ?></label></th><td><input id="from_email" name="from_email" type="email" class="regular-text" value="<?php echo esc_attr( $settings['from_email'] ); ?>" /></td></tr>
            </table>
        <?php elseif ( 'payments' === $tab ) : ?>
            <p class="description"><?php echo esc_html__( 'Payment gateways coming soon. Configure placeholder keys below.', 'tours-booking' ); ?></p>
            <table class="form-table">
                <tr><th><?php echo esc_html__( 'Test Mode', 'tours-booking' ); ?></th><td><label><input type="checkbox" name="payments_test_mode" <?php checked( $settings['payments_test_mode'], 1 ); ?> /> <?php echo esc_html__( 'Enable', 'tours-booking' ); ?></label></td></tr>
                <tr><th><label for="payments_stripe_key"><?php echo esc_html__( 'Stripe API Key', 'tours-booking' ); ?></label></th><td><input id="payments_stripe_key" name="payments_stripe_key" type="text" class="regular-text" value="<?php echo esc_attr( $settings['payments_stripe_key'] ); ?>" /></td></tr>
                <tr><th><label for="payments_paypal_key"><?php echo esc_html__( 'PayPal API Key', 'tours-booking' ); ?></label></th><td><input id="payments_paypal_key" name="payments_paypal_key" type="text" class="regular-text" value="<?php echo esc_attr( $settings['payments_paypal_key'] ); ?>" /></td></tr>
            </table>
        <?php elseif ( 'integrations' === $tab ) : ?>
            <h2><?php echo esc_html__( 'Google Calendar', 'tours-booking' ); ?></h2>
            <table class="form-table">
                <tr><th><label for="gcal_client_id"><?php echo esc_html__( 'Client ID', 'tours-booking' ); ?></label></th><td><input id="gcal_client_id" name="gcal_client_id" type="text" class="regular-text" value="<?php echo esc_attr( $settings['gcal_client_id'] ); ?>" /></td></tr>
                <tr><th><label for="gcal_client_secret"><?php echo esc_html__( 'Client Secret', 'tours-booking' ); ?></label></th><td><input id="gcal_client_secret" name="gcal_client_secret" type="text" class="regular-text" value="<?php echo esc_attr( $settings['gcal_client_secret'] ); ?>" /></td></tr>
                <tr><th><label for="gcal_calendar_id"><?php echo esc_html__( 'Calendar ID', 'tours-booking' ); ?></label></th><td><input id="gcal_calendar_id" name="gcal_calendar_id" type="text" class="regular-text" value="<?php echo esc_attr( $settings['gcal_calendar_id'] ); ?>" /></td></tr>
                <tr><th><label for="gcal_token"><?php echo esc_html__( 'Token (JSON)', 'tours-booking' ); ?></label></th><td><textarea id="gcal_token" name="gcal_token" rows="6" class="large-text"><?php echo esc_textarea( $settings['gcal_token'] ); ?></textarea></td></tr>
            </table>
            <p>
                <a href="#" class="button disabled" aria-disabled="true"><?php echo esc_html__( 'Authenticate with Google (placeholder)', 'tours-booking' ); ?></a>
                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tb_pull_gcal' ), 'tb_pull_gcal' ) ); ?>" class="button"><?php echo esc_html__( 'Pull Busy Dates', 'tours-booking' ); ?></a>
            </p>
        <?php elseif ( 'advanced' === $tab ) : ?>
            <table class="form-table">
                <tr><th><?php echo esc_html__( 'Delete all plugin data on uninstall', 'tours-booking' ); ?></th><td><label><input type="checkbox" name="delete_on_uninstall" <?php checked( $settings['delete_on_uninstall'], 1 ); ?> /> <?php echo esc_html__( 'Enable', 'tours-booking' ); ?></label></td></tr>
                <tr><th><?php echo esc_html__( 'Debug Log', 'tours-booking' ); ?></th><td><label><input type="checkbox" name="debug_log" <?php checked( $settings['debug_log'], 1 ); ?> /> <?php echo esc_html__( 'Enable', 'tours-booking' ); ?></label></td></tr>
            </table>
        <?php endif; ?>

        <p><button class="button button-primary" type="submit"><?php echo esc_html__( 'Save Changes', 'tours-booking' ); ?></button></p>
    </form>
</div>