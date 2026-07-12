<?php
namespace Config;

class Config {
    public static function get($key, $default = null) {
        $configs = [
            'app_name' => 'UniCore App',
            'app_env' => 'development',
            'api_base_url' => 'http://localhost/uni_core_proj_01/backend/api',
            'frontend_url' => 'http://localhost:5173',
            'upload_dir' => __DIR__ . '/../../uploads/',
        ];

        return $configs[$key] ?? $default;
    }
}
