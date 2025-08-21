<?php
if (!defined('ABSPATH')) { exit; }
class WP_Auth_Security_Stats_Endpoint {
    public static function handle($request) {
        $security_handler = new WP_Auth_Security_Handler();
        $stats = $security_handler->get_security_stats();
        return array('success' => true, 'data' => $stats);
    }
}
