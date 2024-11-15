<?php

namespace Userstory\ItsIntegrator;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ItsIntegratorTable extends DataManager
{
    public static function getTableName()
    {
        return 'userstory_itsintegrator';
    }

    public static function getMap()
    {
        return array(
            new IntegerField('ID', array(
                'autocomplete' => true,
                'primary' => true,
                'title' => Loc::getMessage('USERSTORY_ITSINTEGRATOR_ID'),
            )),
            new StringField('HOST', array(
                'required' => true,
                'title' => Loc::getMessage('USERSTORY_ITSINTEGRATOR_RABBITMQ_HOST'),
                'default_value' => function () {
                    return Loc::getMessage('USERSTORY_ITSINTEGRATOR_RABBITMQ_HOST_DEFAULT_VALUE');
                },
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 255),
                    );
                },
            )),
            new StringField('PORT', array(
                'required' => true,
                'title' => Loc::getMessage('USERSTORY_ITSINTEGRATOR_RABBITMQ_PORT'),
                'default_value' => function () {
                    return Loc::getMessage('USERSTORY_ITSINTEGRATOR_RABBITMQ_PORT_DEFAULT_VALUE');
                },
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 5),
                    );
                },
            )),
            new StringField('USER', array(
                'required' => true,
                'title' => Loc::getMessage('USERSTORY_ITSINTEGRATOR_RABBITMQ_USER'),
                'default_value' => function () {
                    return '';
                },
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 255),
                    );
                },
            )),
            new StringField('PASS', array(
                'required' => true,
                'title' => Loc::getMessage('USERSTORY_ITSINTEGRATOR_RABBITMQ_PASS'),
                'default_value' => function () {
                    return '';
                },
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 255),
                    );
                },
            )),
            new StringField('VHOST', array(
                'required' => true,
                'title' => Loc::getMessage('USERSTORY_ITSINTEGRATOR_RABBITMQ_VHOST'),
                'default_value' => function () {
                    return '/';
                },
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 255),
                    );
                },
            )),
            new IntegerField('CATALOG_ID', array(
                'required' => true,
                'default_value' => function () {
                    return 1;
                },
                'title' => Loc::getMessage('USERSTORY_ITSINTEGRATOR_CATALOG_ID'),
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 255),
                    );
                },
            )),
            new IntegerField('OFFERS_ID', array(
                'required' => true,
                'default_value' => function () {
                    return 1;
                },
                'title' => Loc::getMessage('USERSTORY_ITSINTEGRATOR_OFFERS_ID'),
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 255),
                    );
                },
            )),
            new StringField('EXCHANGE', array(
                'required' => true,
                'title' => Loc::getMessage('USERSTORY_ITSINTEGRATOR_RABBITMQ_EXCHANGE'),
                'default_value' => function () {
                    return 'thumbnailer_exchange';
                },
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 255),
                    );
                },
            )),
            new StringField('QUEUE', array(
                'required' => true,
                'title' => Loc::getMessage('USERSTORY_ITSINTEGRATOR_RABBITMQ_QUEUE'),
                'default_value' => function () {
                    return 'thumbnailer_queue';
                },
                'validation' => function () {
                    return array(
                        new Validator\Length(null, 255),
                    );
                },
            )),
        );
    }
}
