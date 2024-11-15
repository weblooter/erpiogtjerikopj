<?php

use Bitrix\Main\Config\Option;

$catalogId = (int)Option::get('userstory.itsintegrator', 'CATALOG_ID');

$imagesConfigs = [
    'image_list'    => [
        'iblock_id' => $catalogId,
        'sizes'     => [
            'middle' => 506,
        ],
        'keys'      => 'middle',
    ],
    'image_detail'  => [
        'iblock_id' => $catalogId,
        'sizes'     => [
            'small' => 164,
        ],
        'keys'      => 'small',
    ],
    'images_list'   => [
        'iblock_id' => $catalogId,
        'sizes'     => [
            'small'  => 305,
            'middle' => 506,
            'large'  => 3200,
        ],
        'keys'      => ['small', 'middle', 'large'],
    ],
    'images_detail' => [
        'iblock_id' => $catalogId,
        'sizes'     => [
            'small'  => 305,
            'middle' => 506,
            'large'  => 3200,
        ],
        'keys'      => ['small', 'middle', 'large'],
    ],
];
