<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AddonController as AdminAddonController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\Auth\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\Auth\TwoFactorChallengeController as AdminTwoFactorChallengeController;
use App\Http\Controllers\Admin\CarouselSlideController as AdminCarouselSlideController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HealthController as AdminHealthController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\OrderRequestController as AdminOrderRequestController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductOptionController as AdminProductOptionController;
use App\Http\Controllers\Admin\ProductSizePriceController as AdminProductSizePriceController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\RefundController as AdminRefundController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SecurityController as AdminSecurityController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BrandAssetController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Middleware\RecordAdminActivity;
use App\Http\Middleware\RecordCustomerActivity;
use Illuminate\Support\Facades\Route;

Route::get('/favicon.png', [BrandAssetController::class, 'favicon'])->name('favicon');
Route::get('/brand-logo.png', [BrandAssetController::class, 'logo'])->name('brand.logo');
Route::get('/', HomeController::class)->name('home');
Route::get('/storefront/version', [HomeController::class, 'version'])->name('storefront.version');
Route::post('/assistant/reply', [AssistantController::class, 'reply'])->middleware('throttle:40,1')->name('assistant.reply');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

Route::middleware(['guest', RecordCustomerActivity::class])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'send'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth', RecordCustomerActivity::class])->name('logout');

Route::middleware(['auth', 'active', RecordCustomerActivity::class])->group(function () {
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menu/{product:slug}', [MenuController::class, 'show'])->name('menu.show');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
    Route::patch('/cart/items/{cartItem}/selection', [CartController::class, 'updateSelection'])->name('cart.items.selection');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');

    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/requests', [OrderController::class, 'storeRequest'])->name('orders.requests.store');
    Route::post('/orders/{order}/review', [OrderController::class, 'storeReview'])->middleware('throttle:10,1')->name('orders.reviews.store');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/payments/{payment}/proof', [PaymentProofController::class, 'update'])->name('orders.payments.proof.update');
    Route::post('/orders/{order}/payments/retry', [PaymentProofController::class, 'retry'])->name('orders.payments.retry');

    Route::get('/payments/{payment}/proof', [PaymentProofController::class, 'show'])->name('payments.proof');

    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/receipts/{receipt}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])->name('notifications.open');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

    Route::get('/account/export', [AccountController::class, 'export'])->name('account.export');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(RecordAdminActivity::class)->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1')->name('login.store');

    Route::middleware('guest')->group(function () {
        Route::get('/forgot-password', [PasswordResetLinkController::class, 'adminRequest'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'adminSend'])->middleware('throttle:6,1')->name('password.email');
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'adminCreate'])->name('password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'adminStore'])->middleware('throttle:6,1')->name('password.update');

        Route::get('/two-factor-challenge', [AdminTwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
        Route::post('/two-factor-challenge', [AdminTwoFactorChallengeController::class, 'store'])->middleware('throttle:6,1')->name('two-factor.challenge.store');
    });

    Route::middleware(['auth', 'active', 'admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/avatar', [AdminProfileController::class, 'updateAvatar'])->name('profile.avatar.update');

        Route::get('/security', [AdminSecurityController::class, 'edit'])->name('security.edit');
        Route::post('/security/two-factor/enable', [AdminSecurityController::class, 'enable'])->name('security.two-factor.enable');
        Route::post('/security/two-factor/confirm', [AdminSecurityController::class, 'confirm'])->name('security.two-factor.confirm');
        Route::delete('/security/two-factor', [AdminSecurityController::class, 'disable'])->name('security.two-factor.disable');
        Route::post('/security/two-factor/recovery-codes', [AdminSecurityController::class, 'regenerateRecoveryCodes'])->name('security.two-factor.recovery-codes');

        Route::resource('categories', AdminCategoryController::class)->except(['show'])->middleware('admin.role:super_admin,admin');
        Route::resource('products', AdminProductController::class)->except(['show'])->middleware(['admin.role:super_admin,admin', 'product.submit-once']);
        Route::resource('addons', AdminAddonController::class)->except(['show'])->middleware('admin.role:super_admin,admin');
        Route::resource('promotions', AdminPromotionController::class)->except(['show'])->middleware('admin.role:super_admin,admin');
        Route::post('/carousel/bulk', [AdminCarouselSlideController::class, 'storeMany'])->middleware('admin.role:super_admin,admin')->name('carousel.store-many');
        Route::delete('/carousel/bulk', [AdminCarouselSlideController::class, 'destroyMany'])->middleware('admin.role:super_admin,admin')->name('carousel.destroy-many');
        Route::resource('carousel', AdminCarouselSlideController::class)->only(['index', 'destroy'])->parameters(['carousel' => 'carouselSlide'])->middleware('admin.role:super_admin,admin');

        Route::put('/products/{product}/size-prices', [AdminProductSizePriceController::class, 'update'])->name('products.size-prices.update');
        Route::post('/products/{product}/options', [AdminProductOptionController::class, 'store'])->name('products.options.store');
        Route::patch('/products/{product}/options/{option}', [AdminProductOptionController::class, 'update'])->name('products.options.update');
        Route::delete('/products/{product}/options/{option}', [AdminProductOptionController::class, 'destroy'])->name('products.options.destroy');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/ticket', [AdminOrderController::class, 'ticket'])->name('orders.ticket');
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
        Route::patch('/orders/{order}/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->name('orders.payments.verify');
        Route::patch('/orders/{order}/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('orders.payments.reject');
        Route::post('/orders/{order}/payments', [AdminPaymentController::class, 'record'])->name('orders.payments.record');
        Route::post('/orders/{order}/refunds', [AdminRefundController::class, 'store'])->middleware('admin.role:super_admin,admin')->name('orders.refunds.store');

        Route::get('/refunds', [AdminRefundController::class, 'index'])->middleware('admin.role:super_admin,admin')->name('refunds.index');

        Route::get('/reports', [AdminReportController::class, 'index'])->middleware('admin.role:super_admin,admin')->name('reports.index');
        Route::get('/reports/export/{format}', [AdminReportController::class, 'export'])->middleware('admin.role:super_admin,admin')->name('reports.export');

        Route::get('/health', [AdminHealthController::class, 'index'])->middleware('admin.role:super_admin,admin')->name('health.index');
        Route::post('/health/failed-jobs/retry', [AdminHealthController::class, 'retry'])->middleware('admin.role:super_admin,admin')->name('health.retry');
        Route::delete('/health/failed-jobs', [AdminHealthController::class, 'flush'])->middleware('admin.role:super_admin,admin')->name('health.flush');

        Route::get('/notifications', [NotificationController::class, 'adminIndex'])->name('notifications.index');
        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::get('/notifications/{notification}', [NotificationController::class, 'open'])->name('notifications.open');

        Route::get('/requests', [AdminOrderRequestController::class, 'index'])->name('requests.index');
        Route::patch('/requests/{orderRequest}/review', [AdminOrderRequestController::class, 'review'])->name('requests.review');

        Route::get('/reviews', [AdminReviewController::class, 'index'])->middleware('admin.role:super_admin,admin')->name('reviews.index');
        Route::patch('/reviews/{review}', [AdminReviewController::class, 'update'])->middleware('admin.role:super_admin,admin')->name('reviews.update');

        Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
        Route::get('/customers/{customer}/export', [AdminCustomerController::class, 'export'])->name('customers.export');
        Route::patch('/customers/{customer}/toggle', [AdminCustomerController::class, 'toggleActive'])->name('customers.toggle');

        Route::get('/settings', [AdminSettingController::class, 'edit'])->middleware('admin.role:super_admin,admin')->name('settings.edit');
        Route::put('/settings', [AdminSettingController::class, 'update'])->middleware('admin.role:super_admin,admin')->name('settings.update');

        Route::get('/staff', [AdminStaffController::class, 'index'])->middleware('admin.role:super_admin')->name('staff.index');
        Route::post('/staff', [AdminStaffController::class, 'store'])->middleware('admin.role:super_admin')->name('staff.store');
        Route::patch('/staff/{staff}', [AdminStaffController::class, 'update'])->middleware('admin.role:super_admin')->name('staff.update');
        Route::delete('/staff/{staff}', [AdminStaffController::class, 'destroy'])->middleware('admin.role:super_admin')->name('staff.destroy');

        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->middleware('admin.role:super_admin,admin')->name('audit.index');
        Route::get('/audit-logs/feed', [AdminAuditLogController::class, 'feed'])->middleware('admin.role:super_admin,admin')->name('audit.feed');
        Route::get('/audit-logs/export', [AdminAuditLogController::class, 'export'])->middleware('admin.role:super_admin,admin')->name('audit.export');
    });
});
