<?php

namespace NaturaSiberica\Api\Interfaces;

interface ModuleInterface
{
    const MODULE_ID                            = 'demoapp.api';
    const FIVE_YEARS_IN_SECONDS                = 157680000;
    const HUNDRED_YEARS_IN_SECONDS             = 3153600000;
    const EVENT_LOG_AUDIT_TYPE_ID_API_ERROR    = 'API_ERROR';
    const EVENT_LOG_AUDIT_TYPE_ID_REQUEST_INFO = 'REQUEST_INFO';
    const ONE_DAY_IN_SECONDS                   = 86400;
    const ONE                                  = 1;
    const API_VERSION_1                        = 1;
    const API_VERSION_2                        = 2;
}
