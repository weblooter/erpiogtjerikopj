<?php

namespace NaturaSiberica\Api\Interfaces;

interface ConstantEntityInterface
{
    const IBLOCK_CATALOG         = 'products';
    const IBLOCK_OFFER           = 'offers';
    const IBLOCK_COLLECTION      = 'collections';
    const IBLOCK_ACTION          = 'actions';
    const IBLOCK_PROMO_BANNER    = 'promo';
    const IBLOCK_BRAND           = 'brands';
    const IBLOCK_PAGES           = 'pages';
    const IBLOCK_BANNER          = 'banners';
    const IBLOCK_BANNER_HOMEPAGE = 'banners_homepage';

    const HLBLOCK_CERTIFICATE       = 'Certificates';
    const HLBLOCK_COLOR             = 'Colors';
    const HLBLOCK_INGREDIENT        = 'Ingredients';
    const HLBLOCK_SHOPS             = 'Stores';
    const HLBLOCK_CITY              = 'Cities';
    const HLBLOCK_FAST_FILTER       = 'FastFilters';
    const HLBLOCK_MLK_DELIVERY_DATA = 'MlkDeliveryData';

    const DEFAULT_CITY_ID   = 1;
    const DEFAULT_LANG_CODE = 'ru';

    const DEFAULT_ELEMENT_COUNT = 50;
    const DEFAULT_NEWS_COUNT = 20;

    const MIN_LANG_LENGTH        = 2;
    const MIN_FAST_FILTER_LENGTH = 2;
    const MIN_CITY_VALUE         = 1;
    const MIN_OFFSET_VALUE       = 0;
    const MIN_LIMIT_VALUE        = 1;

    const ORDER_PROPERTY_SELECTED_DELIVERY_CODE = 'SELECTED_DELIVEY_CODE';
    const ORDER_PROPERTY_CITY                   = 'TOWN';
    const ORDER_PROPERTY_STREET                 = 'STREET';
    const ORDER_PROPERTY_HOUSE_NUMBER           = 'HOUSE';
    const ORDER_PROPERTY_FLAT                   = 'FLAT';
    const ORDER_PROPERTY_ENTRANCE               = 'PODYEZD';
    const ORDER_PROPERTY_FLOOR                  = 'ETAJ';
    const ORDER_PROPERTY_DOOR_PHONE             = 'DOOR_CODE';
    const ORDER_PROPERTY_MINDBOX_BONUS          = 'MINDBOX_BONUS';
    const ORDER_PROPERTY_MINDBOX_COUPON         = 'MINDBOX_PROMO_CODE';
    const ORDER_PROPERTY_MINDBOX_COUPON_VALUE   = 'MINDBOX_PROMO_VALUE';
}
