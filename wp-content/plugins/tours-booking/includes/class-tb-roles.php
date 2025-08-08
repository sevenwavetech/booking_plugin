<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_Roles {
    const ROLE_GUIDE = 'tb_guide';

    public static function register_roles() {
        add_role( self::ROLE_GUIDE, __( 'Guide', 'tours-booking' ), [
            'read' => true,
        ] );
    }

    public static function map_caps() {
        // Ensure guides have minimal capabilities; admins manage via settings later
        $role = get_role( self::ROLE_GUIDE );
        if ( $role ) {
            $caps = [
                'read' => true,
            ];
            foreach ( $caps as $cap => $grant ) {
                if ( $grant && ! $role->has_cap( $cap ) ) {
                    $role->add_cap( $cap );
                }
            }
        }
    }
}