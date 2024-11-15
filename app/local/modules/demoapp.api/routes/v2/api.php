<?php

use NaturaSiberica\Api\Controllers\Catalog\OfferController;
use NaturaSiberica\Api\Logger\EventLogRecorder;
use NaturaSiberica\Api\Middlewares\Cache\CacheMiddleware;
use NaturaSiberica\Api\Middlewares\Http\JsonResponseMiddleware;
use NaturaSiberica\Api\Middlewares\Http\RequestBodyValidatorMiddleware;
use NaturaSiberica\Api\Middlewares\Routing\PlaceholderMiddleware;
use NaturaSiberica\Api\Middlewares\User\AuthMiddleware;
use NaturaSiberica\Api\V2\Controllers\Catalog\ProductController;
use NaturaSiberica\Api\V2\Controllers\User\FavouritesController;
use NaturaSiberica\Api\V2\Controllers\Search\SearchController;
use NaturaSiberica\Api\V2\Controllers\Marketing;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;

/**
 * @var App $app
 */

$app->group('/v2', function (RouteCollectorProxyInterface $proxy) {
    $authMiddleware                 = new AuthMiddleware();
    $requestBodyValidatorMiddleware = new RequestBodyValidatorMiddleware();
    $placeholderMiddleware = new PlaceholderMiddleware();

    /**
     * Товары
     */
    $proxy->get('/products', [ProductController::class, 'index'])->setName('v2.product.list');
    $proxy->get('/products/{code}', [ProductController::class, 'get'])->setName('v2.product.detail');

    /**
     * Торговые предложения
     */
    $proxy->get('/offers', [OfferController::class, 'get'])->setName('v2.offer.detail');

    /**
     * Избранное
     */
    $proxy->get('/favourites', [FavouritesController::class, 'index'])->addMiddleware($authMiddleware)->setName('v2.favourites.index');
    $proxy->get('/favourites/shared', [FavouritesController::class, 'getByUid'])->setName('v2.favourites.shared');
    $proxy
        ->post('/favourites', [FavouritesController::class, 'addProduct'])
        ->addMiddleware($authMiddleware)
        ->addMiddleware($requestBodyValidatorMiddleware)
        ->setName('v2.favourites.add');
    $proxy
        ->delete('/favourites/{id}', [FavouritesController::class, 'deleteProduct'])
        ->addMiddleware($placeholderMiddleware)
        ->addMiddleware($authMiddleware)
        ->setName('v2.favourites.delete');
    $proxy
        ->delete('/favourites', [FavouritesController::class, 'clear'])
        ->addMiddleware($authMiddleware)
        ->setName('v2.favourites.clear');

    /**
     * Поиск по каталогу
     */
    $proxy->get('/search', [SearchController::class, 'index'])->setName('v2.search.index');


    $proxy->get('/actions/promo', [Marketing\PromoBannerController::class, 'index'])->setName('promo.list');
})->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
        EventLogRecorder::addRequestLog($request);
        return $handler->handle($request);
    })->addMiddleware(new CacheMiddleware())->addMiddleware(new JsonResponseMiddleware());
