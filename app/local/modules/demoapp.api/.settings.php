<?php

use Bitrix\Main\DI\ServiceLocator;
use Slim\Factory\AppFactory;

return [
    'services' => [
        'value' => [
            'slim.app' => [
                'constructor' => static function() {
                    return AppFactory::create(null, ServiceLocator::getInstance());
                }
            ]
        ]
    ],
];
