<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminAiController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ContactSubmissionController;
use App\Http\Controllers\Admin\GeneralSettingController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MediaFolderController;
use App\Http\Controllers\Admin\RegisteredUserController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PageTemplateController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReelController;
use App\Http\Controllers\Admin\SalesStatisticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by bootstrap/app.php under:
| - prefix: / (none)
| - name: admin.
| - middleware: web + auth:admin
|
*/

// Dashboard
Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');

// Page Templates
Route::resource('page-templates', PageTemplateController::class)->except('show');

// Languages
Route::resource('languages', LanguageController::class)->except(['show']);
Route::patch('languages/{language}/toggle-active', [LanguageController::class, 'toggleActive'])->name('languages.toggle-active');
Route::patch('languages/{language}/set-default', [LanguageController::class, 'setDefault'])->name('languages.set-default');
Route::post('languages/bulk-action', [LanguageController::class, 'bulkAction'])->name('languages.bulk-action');
Route::post('languages/sort-order', [LanguageController::class, 'updateSortOrder'])->name('languages.sort-order');

// Media Library
Route::post('media/upload', [MediaController::class, 'upload'])->name('media.upload');
Route::get('media/browse', [MediaController::class, 'browse'])->name('media.browse');
Route::get('media/search', [MediaController::class, 'search'])->name('media.search');
Route::post('media/bulk-action', [MediaController::class, 'bulkAction'])->name('media.bulk-action');
Route::post('media/bulk-destroy', [MediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');
Route::post('media/bulk-move', [MediaController::class, 'bulkMove'])->name('media.bulk-move');
Route::delete('media/{media}/force', [MediaController::class, 'forceDestroy'])->name('media.force-destroy');
Route::patch('media/{media}/restore', [MediaController::class, 'restore'])->name('media.restore');
Route::get('media/{media}/download', [MediaController::class, 'download'])->name('media.download');
Route::resource('media', MediaController::class)
    ->parameters(['media' => 'media'])
    ->whereNumber('media')
    ->except(['create', 'edit']);

// Media Folders
Route::get('media-folders', [MediaFolderController::class, 'index'])->name('media-folders.index');
Route::post('media-folders', [MediaFolderController::class, 'store'])->name('media-folders.store');
Route::patch('media-folders/{folder}', [MediaFolderController::class, 'update'])->name('media-folders.update');
Route::delete('media-folders/{folder}', [MediaFolderController::class, 'destroy'])->name('media-folders.destroy');

// Content Blocks
Route::resource('blocks', \App\Http\Controllers\Admin\BlockTypeController::class)->except('show');

// AI-generated block content (page/post/product) from the block schema + page context
Route::post('ai/generate-block', [AdminAiController::class, 'generateBlock'])->name('ai.generate-block');

Route::post('pages/reorder', [PageController::class, 'reorder'])->name('pages.reorder');
Route::post('pages/ai/translate', [AdminAiController::class, 'translatePageDraft'])->name('pages.ai.translate-draft');
Route::post('pages/ai/seo', [AdminAiController::class, 'generatePageDraftSeo'])->name('pages.ai.seo-draft');
Route::post('pages/{page}/ai/translate', [AdminAiController::class, 'translatePage'])->name('pages.ai.translate');
Route::post('pages/{page}/ai/seo', [AdminAiController::class, 'generatePageSeo'])->name('pages.ai.seo');
Route::resource('pages', PageController::class)->except('show');
Route::post('posts/reorder', [PostController::class, 'reorder'])->name('posts.reorder');
Route::post('posts/ai/translate', [AdminAiController::class, 'translatePostDraft'])->name('posts.ai.translate-draft');
Route::post('posts/ai/seo', [AdminAiController::class, 'generatePostDraftSeo'])->name('posts.ai.seo-draft');
Route::post('posts/{post}/ai/translate', [AdminAiController::class, 'translatePost'])->name('posts.ai.translate');
Route::post('posts/{post}/ai/seo', [AdminAiController::class, 'generatePostSeo'])->name('posts.ai.seo');
Route::resource('posts', PostController::class)->except('show');
Route::post('products/reorder', [ProductController::class, 'reorder'])->name('products.reorder');
Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
Route::get('products/sample-download', [ProductController::class, 'sampleDownload'])->name('products.sample-download');
Route::post('products/ai/translate', [AdminAiController::class, 'translateProductDraft'])->name('products.ai.translate-draft');
Route::post('products/ai/seo', [AdminAiController::class, 'generateProductDraftSeo'])->name('products.ai.seo-draft');
Route::post('products/{product}/ai/translate', [AdminAiController::class, 'translateProduct'])->name('products.ai.translate');
Route::post('products/{product}/ai/seo', [AdminAiController::class, 'generateProductSeo'])->name('products.ai.seo');
Route::resource('products', ProductController::class)->except('show');
Route::post('reels/reorder', [ReelController::class, 'reorder'])->name('reels.reorder');
Route::post('reels/ai/translate', [AdminAiController::class, 'translateReelDraft'])->name('reels.ai.translate-draft');
Route::post('reels/{reel}/ai/translate', [AdminAiController::class, 'translateReel'])->name('reels.ai.translate');
Route::resource('reels', ReelController::class)->except('show');
Route::resource('menus', MenuController::class)->except('show');
Route::get('contact-submissions', [ContactSubmissionController::class, 'index'])->name('contact-submissions.index');
Route::get('contact-submissions/{contactSubmission}', [ContactSubmissionController::class, 'show'])->name('contact-submissions.show');
Route::patch('contact-submissions/{contactSubmission}/read', [ContactSubmissionController::class, 'markRead'])->name('contact-submissions.read');
Route::patch('contact-submissions/{contactSubmission}/unread', [ContactSubmissionController::class, 'markUnread'])->name('contact-submissions.unread');
Route::delete('contact-submissions/{contactSubmission}', [ContactSubmissionController::class, 'destroy'])->name('contact-submissions.destroy');
Route::get('sales', SalesStatisticsController::class)->name('sales.index');
Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
Route::get('activity-logs', ActivityLogController::class)->name('activity-logs.index');
Route::get('profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');
Route::put('profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password.update');
// Admin panel accounts (AdminUser) — managed under /admin/admins
Route::resource('admins', AdminUserController::class)->parameters(['admins' => 'user'])->except('show');
// Registered website users (User) — read-only management under /admin/users
Route::resource('users', RegisteredUserController::class)->only(['index', 'show', 'destroy']);
Route::resource('roles', AdminRoleController::class)->except('show');
Route::get('settings', [GeneralSettingController::class, 'edit'])->name('settings.edit');
Route::put('settings', [GeneralSettingController::class, 'update'])->name('settings.update');
