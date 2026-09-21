<?php

namespace App\Providers;

use App\Models\ContactSubmission;
use App\Repositories\Contracts\BlockTypeRepositoryInterface;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PageRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\WebsiteCartRepositoryInterface;
use App\Repositories\Contracts\WebsiteMenuRepositoryInterface;
use App\Repositories\Contracts\WebsitePageRepositoryInterface;
use App\Repositories\Contracts\WebsitePostRepositoryInterface;
use App\Repositories\Eloquent\BlockTypeRepository;
use App\Repositories\Eloquent\EloquentWebsiteCartRepository;
use App\Repositories\Eloquent\EloquentWebsiteMenuRepository;
use App\Repositories\Eloquent\EloquentWebsitePageRepository;
use App\Repositories\Eloquent\EloquentWebsitePostRepository;
use App\Repositories\Eloquent\LanguageRepository;
use App\Repositories\Eloquent\OrderRepository;
use App\Repositories\Eloquent\PageRepository;
use App\Repositories\Eloquent\PostRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Services\Payments\MockBogAuthService;
use App\Services\Payments\MockBogPaymentService;
use App\Support\BogMode;
use Bog\Payment\Services\BogAuthService;
use Bog\Payment\Services\BogPaymentService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Admin-side repositories
        $this->app->bind(PageRepositoryInterface::class, PageRepository::class);
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(BlockTypeRepositoryInterface::class, BlockTypeRepository::class);
        $this->app->bind(LanguageRepositoryInterface::class, LanguageRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);

        // Website-side repositories
        $this->app->bind(WebsitePageRepositoryInterface::class, EloquentWebsitePageRepository::class);
        $this->app->bind(WebsitePostRepositoryInterface::class, EloquentWebsitePostRepository::class);
        $this->app->bind(WebsiteMenuRepositoryInterface::class, EloquentWebsiteMenuRepository::class);
        $this->app->bind(WebsiteCartRepositoryInterface::class, EloquentWebsiteCartRepository::class);

        if (BogMode::isMockMode()) {
            $this->app->bind(BogAuthService::class, MockBogAuthService::class);
            $this->app->bind(BogPaymentService::class, MockBogPaymentService::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer(['admin.layouts.app'], static function ($view): void {
            $notifications = [
                'unreadCount' => 0,
                'messagesUnread' => 0,
                'callRequestsUnread' => 0,
                'latest' => collect(),
            ];

            if (Schema::hasTable('contact_submissions')) {
                $messagesUnread = ContactSubmission::query()
                    ->where('type', ContactSubmission::TYPE_MESSAGE)
                    ->where('is_read', false)
                    ->count();
                $callRequestsUnread = ContactSubmission::query()
                    ->where('type', ContactSubmission::TYPE_CALL_REQUEST)
                    ->where('is_read', false)
                    ->count();

                $notifications = [
                    'unreadCount' => $messagesUnread + $callRequestsUnread,
                    'messagesUnread' => $messagesUnread,
                    'callRequestsUnread' => $callRequestsUnread,
                    'latest' => ContactSubmission::query()
                        ->latest()
                        ->limit(5)
                        ->get(),
                ];
            }

            $view->with('adminContactNotifications', $notifications);
        });
    }
}
