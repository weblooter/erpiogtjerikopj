<?php

namespace NaturaSiberica\Api\Tools\Settings;

use Bitrix\Main\Config\Option;
use NaturaSiberica\Api\Interfaces\ModuleInterface;

class Options implements ModuleInterface
{
    /**
     * Статусы, при которых заказ считается архивным
     *
     * @return false|string[]
     */
    public static function getArchivedOrderStatuses()
    {
        $archivedOrderStatuses = Option::get(self::MODULE_ID, 'archived_order_statuses');
        return explode(',', $archivedOrderStatuses);
    }

    public static function getDefaultStore(): int
    {
        return (int)Option::get(self::MODULE_ID, 'default_store');
    }

    /**
     * ID стран из HL-блока доставок, в которые не осуществляется доставка
     *
     * @return false|string[]
     */
    public static function getDeliveryExcludedCountriesIds()
    {
        $countriesIds = Option::get(self::MODULE_ID, 'delivery_excluded_countries_ids');
        return explode(',', $countriesIds);
    }

    /**
     * Бесплатная доставка от.
     *
     * @return int
     */
    public static function getFreeShippingFrom(): int
    {
        return (int)Option::get(self::MODULE_ID, 'free_shipping_from');
    }

    /**
     * Количество цифр в коде
     *
     * @return int
     */
    public static function getSmsCodeDigits(): int
    {
        return (int)Option::get(self::MODULE_ID, 'sms_code_digits');
    }

    /**
     * Количество попыток отправки СМС-кода
     *
     * @return int
     */
    public static function getSmsCodeSendAttempts(): int
    {
        return (int)Option::get(self::MODULE_ID, 'send_code_attempts');
    }

    /**
     * Количество попыток входа
     *
     * @return int
     */
    public static function getLoginAttempts(): int
    {
        return (int)Option::get(self::MODULE_ID, 'login_attempts');
    }

    /**
     * Время блокировки пользователя в случае неуспешной авторизации в секундах
     *
     * @return int
     */
    public static function getBlockTimeout(): int
    {
        return (int)Option::get(self::MODULE_ID, 'block_timeout');
    }

    /**
     * Время ожидания повторной отправки кода
     *
     * @return int
     */
    public static function getSmsCodeResendInterval(): int
    {
        return (int)Option::get(self::MODULE_ID, 'sms_code_resend_interval');
    }

    /**
     * Флаг необходимости отправки смс
     *
     * @return string
     */
    public static function getSmsGatewayApiNeed(): string
    {
        return Option::get(self::MODULE_ID, 'sms_gateway_api_need');
    }

    /**
     * Флаг необходимости генерации кода для смс
     *
     * @return string
     */
    public static function getSmsGatewayApiGenerateNeed(): string
    {
        return Option::get(self::MODULE_ID, 'sms_gateway_api_generate_need');
    }

    /**
     * Статический код без смс
     *
     * @return string
     */
    public static function getSmsGatewayApiStaticCode(): string
    {
        return Option::get(self::MODULE_ID, 'sms_gateway_api_static_code', '1234');
    }

    /**
     * API-токен смс-шлюза
     *
     * @return string
     */
    public static function getSmsGatewayApiToken(): string
    {
        return Option::get(self::MODULE_ID, 'sms_gateway_api_token');
    }

    /**
     * Имя отправителя в смс
     *
     * @return string
     */
    public static function getSmsGatewaySenderName(): string
    {
        return Option::get(self::MODULE_ID, 'sms_gateway_api_sender_name');
    }

    public static function getReCaptchaNeed(): string
    {
        return Option::get(self::MODULE_ID, 're_captcha_need');
    }

    public static function getReCaptchaHostname(): string
    {
        return Option::get(self::MODULE_ID, 're_captcha_hostname');
    }

    public static function getReCaptchaSiteKey(): string
    {
        return Option::get(self::MODULE_ID, 're_captcha_site_key');
    }

    public static function getReCaptchaSecretKey(): string
    {
        return Option::get(self::MODULE_ID, 're_captcha_secret_key');
    }

    /**
     * Версия API Mindbox
     *
     * @return int
     */
    public static function getMindboxApiVersion(): int
    {
        return (int)Option::get(self::MODULE_ID, 'mindbox_api_version');
    }

    public static function getMindboxSubscriptionsTopicName(): string
    {
        return Option::get(self::MODULE_ID, 'mindbox_subscriptions_topic_name');
    }

    public static function getMindboxServerName(): string
    {
        return Option::get(self::MODULE_ID, 'mindbox_feed_server_name');
    }

    public static function getThumbnailerServiceUrl(): string
    {
        return Option::get(self::MODULE_ID, 'thumbnailer_service_url');
    }

    public static function getThumbnailerImagesSizesConfigs(): array
    {
        return Option::get(self::MODULE_ID, 'thumbnailer_images_configs');
    }

    public static function getThumbnailerImagesExtension(): string
    {
        return Option::get(self::MODULE_ID, 'thumbnailer_images_extension');
    }

    public static function isEnabledSendDataInMindbox(): bool
    {
        return Option::get(self::MODULE_ID, 'is_enabled_send_data_in_mindbox') === 'Y';
    }

    public static function getTextareaBeforeBrands(): string
    {
        return Option::get(self::MODULE_ID, 'textarea_before_brands');
    }

    public static function getSelectionsBlockTitle(): string
    {
        return Option::get(self::MODULE_ID, 'selections_block_title');
    }

    public static function getTextareaBeforeBloggersList(): string
    {
        return Option::get(self::MODULE_ID, 'textarea_before_bloggers_list');
    }

    public static function getSubscriptionBlockTitle(): string
    {
        return Option::get(self::MODULE_ID, 'subscription_block_title');
    }

    public static function getSeoTitle(): string
    {
        return Option::get(self::MODULE_ID, 'meta_title');
    }

    public static function getSeoPageTitle(): string
    {
        return Option::get(self::MODULE_ID, 'meta_page_title');
    }

    public static function getSeoDescription(): string
    {
        return Option::get(self::MODULE_ID, 'meta_description');
    }

    public static function getSeoKeywords(): string
    {
        return Option::get(self::MODULE_ID, 'meta_keywords');
    }
    // TODO: start: Убрать, когда не нужен будет тестовый режим
    public static function getTestingMode(): string
    {
        return Option::get(self::MODULE_ID, 'is_enabled_test_mode');
    }

    public static function getTestingConfirCode(): string
    {
        return Option::get(self::MODULE_ID, 'login_confirmation_code');
    }

    public static function getTestingTelephoneNumber(): string
    {
        return Option::get(self::MODULE_ID, 'number_telephone_for_test');
    }
    // TODO: end: Убрать, когда не нужен будет тестовый режим
}
