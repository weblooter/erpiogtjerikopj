<?php

if (is_file($_SERVER['DOCUMENT_ROOT'] . '/local/modules/userstory.i18n/admin/us_i18n_new_version.php')) {
    /** @noinspection PhpIncludeInspection */
    require $_SERVER['DOCUMENT_ROOT'] . '/local/modules/userstory.i18n/admin/us_i18n_new_version.php';
} else {
    /** @noinspection PhpIncludeInspection */
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/userstory.i18n/admin/us_i18n_new_version.php';
}
