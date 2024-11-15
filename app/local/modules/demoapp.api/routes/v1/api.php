<?php

use NaturaSiberica\Api\Controllers\CityController;
use NaturaSiberica\Api\Controllers\Content\BlogerController;
use NaturaSiberica\Api\Controllers\Content\CertificateController;
use NaturaSiberica\Api\Controllers\Content\NewsController;
use NaturaSiberica\Api\Controllers\Content\VideoTutorialsController;
use NaturaSiberica\Api\Controllers\Forms\FormsController;
use NaturaSiberica\Api\Controllers\Sale\CartController;
use NaturaSiberica\Api\Controllers\Sale\OrderController;
use NaturaSiberica\Api\Controllers\Sale\StoreController;
use NaturaSiberica\Api\Controllers\Structure\MenuController;
use NaturaSiberica\Api\Controllers\Content\Pages\PagesController;
use NaturaSiberica\Api\Controllers\TokenController;
use NaturaSiberica\Api\Controllers\User\AuthController;
use NaturaSiberica\Api\Controllers\User\PhoneController;
use NaturaSiberica\Api\Controllers\User\ProfileController;
use NaturaSiberica\Api\Controllers\Marketing;
use NaturaSiberica\Api\Controllers\Catalog;
use NaturaSiberica\Api\Controllers\Mindbox;
use NaturaSiberica\Api\Controllers\Search;
use NaturaSiberica\Api\Controllers\User\WishListController;
use NaturaSiberica\Api\Controllers\UserField\UserFieldController;
use NaturaSiberica\Api\Logger\EventLogRecorder;
use NaturaSiberica\Api\Middlewares\Cache\CacheMiddleware;
use NaturaSiberica\Api\Middlewares\Routing\PlaceholderMiddleware;
use NaturaSiberica\Api\Middlewares\User\AuthMiddleware;
use NaturaSiberica\Api\Middlewares\Http\JsonResponseMiddleware;
use NaturaSiberica\Api\Middlewares\Http\RequestBodyValidatorMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * @var App $app
 * @var
 */

$app->group('/v1', function (RouteCollectorProxy $proxy) {
    $authMiddleware = new AuthMiddleware();
    $requestBodyValidatorMiddleware = new RequestBodyValidatorMiddleware();
    $placeholderMiddleware = new PlaceholderMiddleware();

    /**
     * Генерация/обновление токена
     */
    $proxy->post('/token', [TokenController::class, 'index'])->setName('token');

    /**
     * Страницы
     */
    $proxy->get('/content/pages/{code}', [PagesController::class, 'get'])->setName('page.show');

    /**
     * Видеоуроки
     */
    $proxy->get('/content/videotutorials', [VideoTutorialsController::class, 'show'])->setName('videotutorials.list');

    $proxy->get('/content/videotutorials/{code}', [VideoTutorialsController::class, 'show'])->setName('videotutorials.show');

    $proxy->get('/content/brands', [Marketing\BrandController::class, 'getMainBrandProducts'])->setName('brandproducts.show');

    /**
     * Новости
     */
    $proxy->get('/content/news', [NewsController::class, 'index'])->setName('news.list');

    $proxy->get('/content/news/{code}', [NewsController::class, 'get'])->setName('news.show');

    /**
     * Сертификаты
     */
    $proxy->get('/content/certificates', [CertificateController::class, 'index'])->setName('certificates.list');

    /**
     * Блогеры
     */
    $proxy->get('/content/blogers', [BlogerController::class, 'index'])->setName('blogers.list');

    /**
     * Меню
     */
    $proxy->get('/menu/header', [MenuController::class, 'getHeaderMenu'])->setName('menu.header');
    $proxy->get('/menu/footer', [MenuController::class, 'getFooterMenu'])->setName('menu.footer');

    /**
     * Справочники
     */
    $proxy->get('/references/skin-types', [UserFieldController::class, 'getSkinTypes'])->setName('references.skinTypes');
    $proxy->get('/references/marital-statuses', [UserFieldController::class, 'getMaritalStatuses'])->setName('references.maritalStatuses');

    /**
     * Регистрация/авторизация
     */
    $proxy->post('/register', [AuthController::class, 'register'])
          ->addMiddleware($authMiddleware)
          ->addMiddleware($requestBodyValidatorMiddleware)
          ->setName('registration');

    $proxy->post('/login', [AuthController::class, 'login'])->addMiddleware($authMiddleware)->addMiddleware($requestBodyValidatorMiddleware)->setName(
            'login'
        );

    $proxy->post('/logout', [AuthController::class, 'logout'])->addMiddleware($authMiddleware)->setName('logout');

    /**
     * Подтверждение телефона
     */
    $proxy->get('/captcha', [PhoneController::class, 'getSiteKey'])->setName('captcha.code');

    $proxy->post('/phone/code', [PhoneController::class, 'getCode'])
          ->addMiddleware($authMiddleware)
          ->addMiddleware($requestBodyValidatorMiddleware)
          ->setName('phone.code');

    $proxy->post('/phone/confirm', [PhoneController::class, 'confirm'])->addMiddleware($authMiddleware)->addMiddleware(
            $requestBodyValidatorMiddleware
        )->setName('phone.confirm');

    $proxy->post('/push', [PhoneController::class, 'sendPush'])->addMiddleware($requestBodyValidatorMiddleware)->setName('sendPush');

    /**
     * Профиль пользователя
     */
    $proxy->get('/profile', [ProfileController::class, 'getProfile'])->addMiddleware($authMiddleware)->setName('profile.show');

    $proxy->patch('/profile', [ProfileController::class, 'editProfile'])->addMiddleware($authMiddleware)->addMiddleware(
            $requestBodyValidatorMiddleware
        )->setName('profile.edit');

    $proxy->delete('/profile', [ProfileController::class, 'clearProfile'])->addMiddleware($authMiddleware)->setName('profile.clear');

    /**
     * Редактирование email
     */
    $proxy->patch('/profile/email', [ProfileController::class, 'changeEmail'])->addMiddleware($authMiddleware)->addMiddleware(
            $requestBodyValidatorMiddleware
        )->setName('email.change');

    /**
     * Настройки уведомлений
     */
    $proxy->patch('/profile/notifications', [ProfileController::class, 'editNotifications'])->addMiddleware($authMiddleware)->addMiddleware(
            $requestBodyValidatorMiddleware
        )->setName('notifications.settings');

    /**
     * Адреса пользователя
     */
    $proxy->get('/profile/addresses', [ProfileController::class, 'getAddress'])->addMiddleware($authMiddleware)->setName('address.list');

    $proxy->post('/profile/addresses', [ProfileController::class, 'addAddress'])->addMiddleware($authMiddleware)->addMiddleware(
            $requestBodyValidatorMiddleware
        )->setName('address.add');

    $proxy->get('/profile/addresses/{id}', [ProfileController::class, 'getAddress'])->addMiddleware($placeholderMiddleware)->addMiddleware(
            $authMiddleware
        )->setName('address.show');

    $proxy->put('/profile/addresses/{id}', [ProfileController::class, 'editAddress'])->addMiddleware($placeholderMiddleware)->addMiddleware(
            $authMiddleware
        )->addMiddleware($requestBodyValidatorMiddleware)->setName('address.edit');

    $proxy->delete('/profile/addresses/{id}', [ProfileController::class, 'deleteAddress'])->addMiddleware($placeholderMiddleware)->addMiddleware(
            $authMiddleware
        )->setName('address.delete');

    /**
     * Избранное
     */

    $proxy->delete('/favourites', [WishListController::class, 'clear'])->addMiddleware($authMiddleware)->setName('favourites.clear');

    /**
     * Магазины
     */
    $proxy->get('/stores', [StoreController::class, 'index'])->setName('stores.list');

    /**
     * Города
     */
    $proxy->get('/cities', [CityController::class, 'index'])->setName('cities.list');

    /**
     * Способы оплаты и доставки
     */
    $proxy->get('/orders/payments', [OrderController::class, 'getPayments'])->addMiddleware($authMiddleware)->setName('payments.show');
    $proxy->get('/orders/deliveries', [OrderController::class, 'getDeliveries'])->addMiddleware($authMiddleware)->setName('shipment.show');


    $proxy->get('/orders/freeShipping', [OrderController::class, 'getFreeShipping'])->addMiddleware($authMiddleware)->setName('freeshipping.show');

    /**
     * Проверка корзины перед оформлением заказа
     */
    $proxy->post('/orders/check-cart', [OrderController::class, 'checkCart'])->addMiddleware($authMiddleware)->setName('order.check.cart');

    /**
     * Статусы заказа
     */
    $proxy->get('/orders/statuses', [OrderController::class, 'getStatuses'])->setName('order.status.list');

    /**
     * Заказы пользователя
     */
    $proxy->get('/orders', [OrderController::class, 'show'])->addMiddleware($authMiddleware)->setName('order.list');
    $proxy->post('/orders', [OrderController::class, 'create'])->addMiddleware($authMiddleware)->setName('order.add');

    $proxy->get('/orders/{id}', [OrderController::class, 'show'])->addMiddleware($placeholderMiddleware)->addMiddleware($authMiddleware)->setName(
            'order.show'
        );

    $proxy->patch('/orders/{id}/cancel', [OrderController::class, 'cancel'])->add($placeholderMiddleware)->addMiddleware($authMiddleware)->setName(
            'order.cancel'
        );

    /**
     * Ссылка на оплату заказа
     */
    $proxy->get('/orders/{id}/payment', [OrderController::class, 'getPaymentUrl'])->addMiddleware($placeholderMiddleware)->addMiddleware(
            $authMiddleware
        )->setName('order.payment.url');

    /**
     * Корзина
     */
    $proxy->get('/cart', [CartController::class, 'index'])->addMiddleware($authMiddleware)->setName('cart.show');

    $proxy->put('/cart', [CartController::class, 'update'])->addMiddleware($authMiddleware)->addMiddleware($requestBodyValidatorMiddleware)->setName(
            'cart.update'
        );
    $proxy->delete('/cart', [CartController::class, 'delete'])->addMiddleware($authMiddleware)->setName('cart.delete');

    /**
     * Категории товаров
     */
    $proxy->get('/categories', [Catalog\CategoryController::class, 'index'])->setName('category.list');

    /**
     * Список сортировки товаров
     */
    $proxy->get('/products/sort', [Catalog\SortController::class, 'index'])->setName('sort.list');

    /**
     * Фильтр товаров
     */
    $proxy->get('/products/filter', [Catalog\FilterController::class, 'index'])->setName('filter.list');

    /**
     * Быстрые фильтры товаров
     */
    $proxy->get('/products/fast-filter', [Catalog\FastFilterController::class, 'index'])->setName('fastFilter.list');

    /**
     * Акции
     */
    $proxy->get('/actions/promo', [Marketing\PromoBannerController::class, 'index'])->setName('promo.list');
    $proxy->get('/actions', [Marketing\SaleActionController::class, 'index'])->setName('action.list');
    $proxy->get('/banners', [Marketing\BannerController::class, 'index'])->setName('banner.list');

    /**
     * Главная страница
     */
    $proxy->get('/homepage/banners/{position}', [Marketing\BannerHomepageController::class, 'get'])->setName('homepage.banner');
    $proxy->get('/homepage', [Marketing\BannerHomepageController::class, 'index'])->setName('homepage');

    /**
     * Коллекции товаров
     */
    $proxy->get('/collections', [Marketing\CollectionController::class, 'index'])->setName('collection.list');

    /**
     * Линейки брендов
     */
    $proxy->get('/brands/series', [Marketing\SeriesController::class, 'index'])->setName('series.list');

    /**
     * Бренды
     */
    $proxy->get('/brands', [Marketing\BrandController::class, 'index'])->setName('brand.list');

    /**
     * Формы
     */
    $proxy->post('/form/feedback', [FormsController::class, 'feedback'])
          ->addMiddleware($authMiddleware)
          ->addMiddleware($requestBodyValidatorMiddleware)
          ->setName('form.feedback');

    /**
     * Прокси-запросы в Mindbox с фронта
     */
    $proxy->post('/mindbox/CalculatePriceProduct', [Mindbox\DiscountController::class, 'index'])->setName('mindbox.calculate');
    $proxy->post('/mindbox/CheckCustomer', [Mindbox\CheckCustomerController::class, 'index'])->setName('mindbox.check');
    $proxy->post('/mindbox/CalculateAuthorizedCart', [Mindbox\CalculateAuthorizedCartController::class, 'index'])->setName('mindbox.cart');
    $proxy->post('/mindbox/MergeCustomers', [Mindbox\MergeCustomersController::class, 'index'])->setName('mindbox.mergeCustomers');
    $proxy->post('/mindbox/CalculateAnonymousCart', [Mindbox\CalculateAnonymousCartController::class, 'index'])->setName('mindbox.CalculateAnonymousCart');

})->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
        EventLogRecorder::addRequestLog($request);
        return $handler->handle($request);
    })->addMiddleware(new CacheMiddleware())->addMiddleware(new JsonResponseMiddleware());
