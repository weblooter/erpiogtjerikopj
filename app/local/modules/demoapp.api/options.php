<?php

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use NaturaSiberica\Api\Tools\Settings\Settings;

$request = Context::getCurrent()->getRequest();

$module_id = !empty(htmlspecialcharsbx($request->get('mid'))) ? $request->get('mid') : $request->get('id');

Loader::includeModule($module_id);

$settings = new Settings($request);

$settings->parseRequest();
$settings->render();
