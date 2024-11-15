<?php

namespace NaturaSiberica\Api\Tools\Settings;

use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;
use NaturaSiberica\Api\DTO\Structure\Menu\FooterMenuDTO;
use NaturaSiberica\Api\Helpers\Http\UrlHelper;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Interfaces\Services\Mindbox\MindboxServiceInterface;
use NaturaSiberica\Api\Repositories\Report\ProductsRepository;
use NaturaSiberica\Api\Repositories\Sale\StatusRepository;
use NaturaSiberica\Api\Repositories\Sale\StoreRepository;
use NaturaSiberica\Api\Traits\Entities\CacheTrait;

Loader::includeModule('highloadblock');
Loader::includeModule('iblock');
Loc::loadMessages(__FILE__);
Loc::loadCustomMessages(dirname(__DIR__, 3) . '/admin/ns_api_cart_settings.php');
Loc::loadCustomMessages(dirname(__DIR__, 3) . '/admin/ns_api_footer_settings.php');

class Settings implements ModuleInterface
{
    use CacheTrait;

    private ?HttpRequest    $request = null;
    private StoreRepository $storeRepository;

    private array $excludedOptions = ['mid', 'lang', 'save', 'apply'];

    /**
     * @param HttpRequest|null $request
     */
    public function __construct(?HttpRequest $request = null)
    {
        $this->storeRepository = new StoreRepository();
        $this->setRequest($request);
    }

    /**
     * @param HttpRequest|null $request
     *
     * @return $this
     */
    public function setRequest(?HttpRequest $request): Settings
    {
        $this->request = $request ?? Context::getCurrent()->getRequest();
        return $this;
    }

    public function render(): void
    {
        $tabControl = new \CAdminTabControl('tabControl', $this->getTabs());

        $tabControl->Begin();
        $this->formStart();

        foreach ($this->getTabs() as $tab) {
            if (! empty($tab['OPTIONS'])) {
                $tabControl->BeginNextTab();
                __AdmSettingsDrawList(self::MODULE_ID, $tab['OPTIONS']);
                $tabControl->EndTab();
            }
        }

        $tabControl->Buttons([
            'buttons' => [
                'btnSave'  => true,
                'btnApply' => true,
            ],
        ]);
        $this->formEnd();
        $tabControl->End();
    }

    public function getTabs(): array
    {
        return [
            [
                'DIV'     => 'store_settings',
                'TAB'     => Loc::getMessage('store_tab_tab'),
                'TITLE'   => Loc::getMessage('store_tab_title'),
                'OPTIONS' => [
                    Loc::getMessage('note_order'),
                    [
                        'archived_order_statuses',
                        Loc::getMessage('label_archived_order_statuses'),
                        '',
                        ['multiselectbox', $this->getOrderStatusList()],
                    ],
                    Loc::getMessage('note_delivery'),
                    [
                        'default_store',
                        Loc::getMessage('label_default_store'),
                        '',
                        ['selectbox', $this->storeRepository->getSelectorList()],
                    ],
                    [
                        'delivery_data_hl_entity',
                        Loc::getMessage('label_delivery_data_hl_entity'),
                        '',
                        ['selectbox', $this->getHighloadBlocksList()],

                    ],
                    [
                        'delivery_excluded_countries_ids',
                        Loc::getMessage('label_delivery_excluded_countries_ids'),
                        '',
                        ['text', 64],
                    ],
                    [
                        'free_shipping_from',
                        Loc::getMessage('label_free_shipping_from'),
                        '',
                        ['text', 64],
                    ],

                ],
            ],
            [
                'DIV'     => 'sms_gateway_settings',
                'TAB'     => Loc::getMessage('sms_gateway_tab'),
                'TITLE'   => Loc::getMessage('sms_gateway_title'),
                'OPTIONS' => [
                    [
                        'sms_gateway_api_need',
                        Loc::getMessage('label_sms_gateway_api_need'),
                        '',
                        [
                            'selectbox',
                            [
                                ''  => Loc::getMessage('select_value_not_selected'),
                                'Y' => Loc::getMessage('select_value_yes'),
                                'N' => Loc::getMessage('select_value_no'),
                            ],
                        ],
                    ],
                    [
                        'sms_gateway_api_generate_need',
                        Loc::getMessage('label_sms_gateway_api_generate_need'),
                        '',
                        [
                            'selectbox',
                            [
                                ''  => Loc::getMessage('select_value_not_selected'),
                                'Y' => Loc::getMessage('select_value_yes'),
                                'N' => Loc::getMessage('select_value_no'),
                            ],
                        ],
                    ],
                    [
                        'sms_gateway_api_static_code',
                        Loc::getMessage('label_sms_gateway_api_static_code'),
                        '1234',
                        ['text', 4],
                    ],
                    [
                        'sms_gateway_api_token',
                        Loc::getMessage('label_sms_gateway_api_token'),
                        '',
                        ['text', 64],
                    ],
                    [
                        'sms_gateway_api_sender_name',
                        Loc::getMessage('label_sms_gateway_api_sender_name'),
                        '',
                        ['text', 64],
                    ],
                    'google Re Captcha',
                    [
                        're_captcha_need',
                        'Выключить проверку капчи',
                        'N',
                        [
                            'selectbox',
                            [
                                'N' => Loc::getMessage('select_value_no'),
                                'Y' => Loc::getMessage('select_value_yes'),
                            ],
                        ],
                    ],
                    [
                        're_captcha_hostname',
                        Loc::getMessage('label_re_captcha_hostname'),
                        '',
                        ['text', 64],
                    ],
                    [
                        're_captcha_site_key',
                        Loc::getMessage('label_re_captcha_site_key'),
                        '',
                        ['text', 64],
                    ],
                    [
                        're_captcha_secret_key',
                        Loc::getMessage('label_re_captcha_secret_key'),
                        '',
                        ['text', 64],
                    ],
                ],
            ],
            [
                'DIV'     => 'auth_settings',
                'TAB'     => Loc::getMessage('auth_tab_tab'),
                'TITLE'   => Loc::getMessage('auth_tab_title'),
                'OPTIONS' => [
                    [
                        'sms_code_digits',
                        Loc::getMessage('label_sms_code_digits'),
                        '',
                        ['text', 2],
                    ],
                    [
                        'sms_code_resend_interval',
                        Loc::getMessage('label_sms_code_resend_interval'),
                        '',
                        ['text', 4],
                    ],
                    [
                        'send_code_attempts',
                        Loc::getMessage('label_send_code_attempts'),
                        '',
                        ['text', 2],
                    ],
                    [
                        'login_attempts',
                        Loc::getMessage('label_login_attempts'),
                        '',
                        ['text', 2],
                    ],
                    [
                        'block_timeout',
                        Loc::getMessage('label_block_timeout'),
                        '',
                        ['text', 4],
                    ],
                    // TODO: start: Убрать, когда не нужен будет тестовый режим
                    Loc::getMessage('label_test_access'),
                    [
                        'is_enabled_test_mode',
                        Loc::getMessage('label_is_enabled_test_mode'),
                        '',
                        [
                            'selectbox',
                            [
                                'N'    => Loc::getMessage('select_value_no'),
                                'Y'    => Loc::getMessage('select_value_yes')
                            ],
                        ],
                    ],
                    [
                        'login_confirmation_code',
                        Loc::getMessage('label_login_confirmation_code'),
                        '6835',
                        ['text', 4],
                    ],
                    [
                        'number_telephone_for_test',
                        Loc::getMessage('label_number_telephone_for_test'),
                        '+79009009090',
                        ['text', 12],
                    ],
                    // TODO: end: Убрать, когда не нужен будет тестовый режим
                ],
            ],
            [
                'DIV'     => 'integrations_settings',
                'TAB'     => Loc::getMessage('integrations_tab_tab'),
                'TITLE'   => Loc::getMessage('integrations_tab_title'),
                'OPTIONS' => [
                    'Mindbox',
                    [
                        'mindbox_api_version',
                        Loc::getMessage('label_mindbox_api_version'),
                        MindboxServiceInterface::MINDBOX_API_VERSION,
                        ['text', 2],
                    ],
                    [
                        'mindbox_subscriptions_topic_name',
                        Loc::getMessage('label_mindbox_subscriptions_topic_name'),
                        MindboxServiceInterface::MINDBOX_SUBSCRIPTIONS_TOPIC_NAME,
                        ['text'],
                    ],
                    [
                        'mindbox_feed_server_name',
                        Loc::getMessage('label_mindbox_feed_server_name'),
                        UrlHelper::getServerName(),
                        ['text'],
                    ],
                    [
                        'is_enabled_send_data_in_mindbox',
                        Loc::getMessage('label_send_data_to_mindbox'),
                        '',
                        [
                            'selectbox',
                            [
                                'null' => Loc::getMessage('select_value_not_selected'),
                                'Y'    => Loc::getMessage('select_value_yes'),
                                'N'    => Loc::getMessage('select_value_no'),
                            ],
                        ],
                    ],
                    'Thumbnailer',
                    [
                        'thumbnailer_service_url',
                        Loc::getMessage('label_thumbnailer_service_url'),
                        '',
                        ['text'],
                    ],
                ],
            ],
            [
                'DIV'     => 'main_page_settings',
                'TAB'     => Loc::getMessage('main_page_tab_tab'),
                'TITLE'   => Loc::getMessage('main_page_tab_title'),
                'OPTIONS' => [
                    [
                        'textarea_before_brands',
                        Loc::getMessage('option_textarea_before_brands'),
                        '',
                        ['textarea', 5, 64],
                    ],
                    [
                        'selections_block_title',
                        Loc::getMessage('option_selections_block_title'),
                        '',
                        ['text', 64],
                    ],
                    [
                        'textarea_before_bloggers_list',
                        Loc::getMessage('option_textarea_before_bloggers_list'),
                        '',
                        ['textarea', 5, 64],
                    ],
                    [
                        'subscription_block_title',
                        Loc::getMessage('option_subscription_block_title'),
                        '',
                        ['text', 64],
                    ],
                    'Настройки SEO информации',
                    [
                        'meta_title',
                        Loc::getMessage('option_meta_title'),
                        '',
                        ['textarea', 5, 64],
                    ],
                    [
                        'meta_page_title',
                        Loc::getMessage('option_meta_page_title'),
                        '',
                        ['textarea', 5, 64],
                    ],
                    [
                        'meta_description',
                        Loc::getMessage('option_meta_description'),
                        '',
                        ['textarea', 5, 64],
                    ],
                    [
                        'meta_keywords',
                        Loc::getMessage('option_meta_keywords'),
                        '',
                        ['textarea', 5, 64],
                    ],
                ],
            ],
            [
                'DIV'     => 'footer_settings',
                'TAB'     => Loc::getMessage('footer_tab_tab'),
                'TITLE'   => Loc::getMessage('footer_tab_title'),
                'OPTIONS' => [
                    [
                        'since_year',
                        Loc::getMessage('option_since_year'),
                        date('Y'),
                        ['text', 4],
                    ],
                    [
                        'footer_menu_hl_entity',
                        Loc::getMessage('option_footer_menu_hl_entity'),
                        $this->getDefaultHlFooterMenuPages(),
                        ['selectbox', $this->getHighloadBlocksList()],
                    ],
                    Loc::getMessage('note_mobile_apps'),
                    [
                        'google_play_url',
                        Loc::getMessage('option_google_play_url'),
                        '',
                        ['text'],
                    ],
                    [
                        'app_store_url',
                        Loc::getMessage('option_app_store_url'),
                        '',
                        ['text'],
                    ],
                    Loc::getMessage('note_social'),
                    [
                        'vk_url',
                        'VK',
                        '',
                        ['text'],
                    ],
                    [
                        'telegram_url',
                        'Telegram',
                        '',
                        ['text'],
                    ],
                    [
                        'ok_url',
                        'OK',
                        '',
                        ['text'],
                    ],
                ],
            ],
            [
                'DIV'     => 'cache_settings',
                'TAB'     => Loc::getMessage('cache_tab_tab'),
                'TITLE'   => Loc::getMessage('cache_tab_title'),
                'OPTIONS' => $this->getCacheOption(),
            ],
        ];
    }

    private function getOrderStatusList(): array
    {
        $statusRepository = new StatusRepository();
        $filter           = [
            'type' => StatusRepository::STATUS_TYPE_ORDER,
            'lang' => LANGUAGE_ID,
        ];

        return $statusRepository->getSelectorList($filter);
    }

    /**
     * @return array
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getHighloadBlocksList(): array
    {
        $hlBlocks = [];

        $params = [
            'filter' => [
                'LID' => LANGUAGE_ID,
            ],
            'select' => ['ID', 'NAME'],
        ];

        $rows = HighloadBlockLangTable::getList($params)->fetchCollection();

        foreach ($rows as $row) {
            $hlBlocks[$row->getId()] = $row->getName();
        }

        return $hlBlocks;
    }

    private function getDefaultHlFooterMenuPages()
    {
        $hlBlock = HighloadBlockTable::getList([
            'filter' => ['=NAME' => 'FooterMenuPages'],
            'select' => ['ID'],
        ])->fetch();

        return $hlBlock['ID'];
    }

    public function getCacheOption(): array
    {
        $result = [];
        $data   = self::getCacheEntityList();
        foreach ($data as $key => $name) {
            $code   = $this->convertURLToCamel($key);
            $result = array_merge($result, [
                $name,
                [
                    $code . '_cache_is_need',
                    Loc::getMessage('label_cache_is_need'),
                    '',
                    ['selectbox', ['Y' => Loc::getMessage('select_value_yes'), 'N' => Loc::getMessage('select_value_no')]],
                ],
                [
                    $code . '_cache_TTL',
                    Loc::getMessage('label_cache_TTL'),
                    3600,
                    ['text'],
                ],
                [
                    $code . '_cache_iblock_code',
                    Loc::getMessage('label_cache_iblock_code'),
                    '',
                    ['selectbox', array_merge(['null' => Loc::getMessage('select_value_not_selected')], $this->getInfoBlocksList())],
                ],
            ]);
        }
        return $result;
    }

    public function getInfoBlocksList(): array
    {
        $result = [];
        $data   = \Bitrix\Iblock\IblockTable::getList([
            'select' => ['CODE', 'NAME'],
            'filter' => ['ACTIVE' => 'Y'],
        ])->fetchCollection();
        foreach ($data as $item) {
            $result[$item->getCode()] = $item->getName();
        }
        return $result;
    }

    public function formStart(): void
    {
        echo sprintf(
            '<form action="%s" method="post">',
            $this->getFormActionUrl()
        );
        echo bitrix_sessid_post();
    }

    private function getFormActionUrl(): string
    {
        return sprintf(
            '%s?%s',
            $this->request->getRequestedPage(),
            http_build_query([
                'mid'  => self::MODULE_ID,
                'lang' => LANGUAGE_ID,
            ])
        );
    }

    public function formEnd()
    {
        echo '</form>';
    }

    /**
     * @throws Exception
     */
    public function parseRequest(): void
    {
        if ($this->request['save'] || $this->request['apply']) {
            foreach ($this->request->toArray() as $option => &$value) {
                if (in_array($option, $this->excludedOptions)) {
                    continue;
                }

                if ($option === 'access_token_secret_key') {
                    $value = md5($value);
                }

                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                Option::set(self::MODULE_ID, $option, $value);

                if ($value === 'null' || empty($value)) {
                    Option::delete(self::MODULE_ID, [
                        'name' => $option,
                    ]);
                }
            }
        }
    }

    public function getMenuItemsFromModule(): array
    {
        return [
            'mobile' => [
                new FooterMenuDTO([
                    'url'  => Option::get(self::MODULE_ID, 'google_play_url'),
                    'text' => $this->getMenuItemTitle('google_play_url'),
                ]),
                new FooterMenuDTO([
                    'url'  => Option::get(self::MODULE_ID, 'app_store_url'),
                    'text' => $this->getMenuItemTitle('app_store_url'),
                ]),
            ],
            'social' => [
                new FooterMenuDTO([
                    'url'  => Option::get(self::MODULE_ID, 'vk_url'),
                    'text' => $this->getMenuItemTitle('vk_url'),
                ]),
                new FooterMenuDTO([
                    'url'  => Option::get(self::MODULE_ID, 'telegram_url'),
                    'text' => $this->getMenuItemTitle('telegram_url'),
                ]),
            ],
        ];
    }

    public function getMenuItemTitle(string $code): string
    {
        $items = $this->getMenuItemsTitles();
        return $items[$code];
    }

    public function getMenuItemsTitles(): array
    {
        return [
            'google_play_url' => 'Google Play',
            'app_store_url'   => 'App Store',
            'vk_url'          => 'VK',
            'ok_url'          => 'OK',
            'telegram_url'    => 'Telegram',
        ];
    }
}
