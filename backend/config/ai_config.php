<?php
// backend/config/ai_config.php

// Google Gemini API Configuration
class AIConfig {
    public static $GEMINI_API_KEY = "";
    public static $MODEL = "gemini-flash-latest"; 

    public static function init() {
        // Look for the .env file in the root directory (2 levels up from /backend/config/)
        $env_path = realpath(__DIR__ . '/../../.env');
        if (file_exists($env_path)) {
            $env_array = parse_ini_file($env_path);
            if (isset($env_array['GEMINI_API_KEY'])) {
                self::$GEMINI_API_KEY = trim($env_array['GEMINI_API_KEY'], '"\'');
            }
        }
    }
}

// Automatically initialize the config when included
AIConfig::init();
?>
