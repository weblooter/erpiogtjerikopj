<?php

require_once __DIR__ . '/configs/thumbnailer/sizes.php';

/**
 * @var array $imagesConfigs
 */

$naturasiberica_api_default_option = [
    'access_token_ttl' => 2592000,
    'refresh_token_ttl' => 2592000,
    'access_token_secret_key' => md5('naturasiberica.api_access'),
    'refresh_token_secret_key' => md5('naturasiberica.api_refresh'),
    'allowed_origins' => '',
    'thumbnailer_images_configs' => $imagesConfigs,
    'thumbnailer_images_extension' => 'webp'
];
