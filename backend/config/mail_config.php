<?php
// backend/config/mail_config.php
// SMTP Email Configuration — reads from .env file

class MailConfig {
    public static $SMTP_HOST = '';
    public static $SMTP_PORT = 587;
    public static $SMTP_USER = '';
    public static $SMTP_PASS = '';
    public static $FROM_EMAIL = '';
    public static $FROM_NAME = '';
    public static $APP_NAME = 'FoldNest Furniture';

    public static function init() {
        $env_path = realpath(__DIR__ . '/../../.env');
        if (file_exists($env_path)) {
            $env = parse_ini_file($env_path);
            self::$SMTP_HOST   = isset($env['SMTP_HOST']) ? trim($env['SMTP_HOST'], '"\'') : '';
            self::$SMTP_PORT   = isset($env['SMTP_PORT']) ? (int) trim($env['SMTP_PORT'], '"\'') : 587;
            self::$SMTP_USER   = isset($env['SMTP_USER']) ? trim($env['SMTP_USER'], '"\'') : '';
            self::$SMTP_PASS   = isset($env['SMTP_PASS']) ? trim($env['SMTP_PASS'], '"\'') : '';
            self::$FROM_EMAIL  = isset($env['SMTP_FROM_EMAIL']) ? trim($env['SMTP_FROM_EMAIL'], '"\'') : '';
            self::$FROM_NAME   = isset($env['SMTP_FROM_NAME']) ? trim($env['SMTP_FROM_NAME'], '"\'') : 'FoldNest';
            self::$APP_NAME    = isset($env['APP_NAME']) ? trim($env['APP_NAME'], '"\'') : 'FoldNest Furniture';
        }
    }

    public static function isConfigured() {
        return !empty(self::$SMTP_HOST) && !empty(self::$SMTP_USER) && !empty(self::$SMTP_PASS) && self::$SMTP_USER !== 'your-email@gmail.com';
    }
}

MailConfig::init();
?>
