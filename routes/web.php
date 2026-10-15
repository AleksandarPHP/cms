<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
  return view('index');
});

Route::get('blog/{id}/{slug}', [App\Http\Controllers\BlogController::class, 'show'])->whereNumber('id')->name('blog.show');
Route::get('newsletter/{newsletter}', [App\Http\Controllers\NewsletterController::class, 'publicShow'])->whereNumber('newsletter')->name('newsletter.public');


require __DIR__.'/auth.php';

Route::middleware(['auth','active','CmsLanguage'])->group(function () {

    Route::group(['prefix' => 'cms'], function () {

		Route::get('/', function () { return view('cms.index'); });

      Route::middleware('can:admins')->group(function () {
        Route::resource('admins', App\Http\Controllers\UsersController::class)->except('show');
        Route::post('admins/ajax', [App\Http\Controllers\UsersController::class, 'ajax']); 
        Route::get('language/{lang}', [App\Http\Controllers\UsersController::class, 'userlang']);
        Route::get('admins/status/{id}', [App\Http\Controllers\UsersController::class, 'status']);
      });

      Route::middleware('can:settings')->group(function () {
        Route::post('settings/colors', [App\Http\Controllers\SettingsController::class, 'colors'])->name('settings.colors');
        Route::resource('settings', App\Http\Controllers\SettingsController::class)->only('update', 'edit');
      });

      Route::middleware('can:company')->group(function () {
        Route::resource('company', App\Http\Controllers\CompanyController::class)->only('update', 'edit');
      });

      Route::middleware('can:ai_posts')->prefix('ai-posts')->group(function () {
        Route::get('/plans', [App\Http\Controllers\ContentPlanController::class, 'index'])->name('ai-posts.plans.index');
        Route::get('/plans/create', [App\Http\Controllers\ContentPlanController::class, 'create'])->name('ai-posts.plans.create');
        Route::post('/plans', [App\Http\Controllers\ContentPlanController::class, 'store'])->name('ai-posts.plans.store');
        Route::post('/plans/{plan}/advance', [App\Http\Controllers\ContentPlanController::class, 'advance'])->whereNumber('plan');
        Route::post('/plans/{plan}/retry', [App\Http\Controllers\ContentPlanController::class, 'retry'])->whereNumber('plan');
        Route::post('/plans/{plan}/approve', [App\Http\Controllers\ContentPlanController::class, 'approveMany'])->whereNumber('plan');
        Route::post('/plans/{plan}/reject', [App\Http\Controllers\ContentPlanController::class, 'rejectMany'])->whereNumber('plan');
        Route::put('/plans/{plan}/posts/{post}', [App\Http\Controllers\ContentPlanController::class, 'updatePost'])->whereNumber('plan')->whereNumber('post');
        Route::post('/plans/{plan}/posts/{post}/approve', [App\Http\Controllers\ContentPlanController::class, 'approvePost'])->whereNumber('plan')->whereNumber('post');
        Route::post('/plans/{plan}/posts/{post}/reject', [App\Http\Controllers\ContentPlanController::class, 'rejectPost'])->whereNumber('plan')->whereNumber('post');
        Route::post('/plans/{plan}/posts/{post}/regenerate', [App\Http\Controllers\ContentPlanController::class, 'regeneratePost'])->whereNumber('plan')->whereNumber('post');
        Route::post('/plans/{plan}/posts/{post}/image-prompt', [App\Http\Controllers\ContentPlanController::class, 'regenerateImagePrompt'])->whereNumber('plan')->whereNumber('post');
        Route::get('/plans/{plan}', [App\Http\Controllers\ContentPlanController::class, 'show'])->whereNumber('plan')->name('ai-posts.plans.show');
        Route::delete('/plans/{plan}', [App\Http\Controllers\ContentPlanController::class, 'destroy'])->whereNumber('plan')->name('ai-posts.plans.destroy');

        Route::get('/', [App\Http\Controllers\AiPostsController::class, 'index'])->name('ai-posts.index');
        Route::get('/create', [App\Http\Controllers\AiPostsController::class, 'create'])->name('ai-posts.create');
        Route::post('/', [App\Http\Controllers\AiPostsController::class, 'store'])->name('ai-posts.store');
        Route::get('/calendar', [App\Http\Controllers\AiPostsController::class, 'calendar'])->name('ai-posts.calendar');
        Route::get('/accounts', [App\Http\Controllers\AiPostsController::class, 'accounts'])->name('ai-posts.accounts');
        Route::post('/accounts/{platform}/connect', [App\Http\Controllers\AiPostsController::class, 'connect'])->where('platform', 'facebook|instagram');
        Route::post('/accounts/{platform}/disconnect', [App\Http\Controllers\AiPostsController::class, 'disconnect'])->where('platform', 'facebook|instagram');
        Route::get('/{post}/edit', [App\Http\Controllers\AiPostsController::class, 'edit'])->whereNumber('post')->name('ai-posts.edit');
        Route::get('/{post}/status', [App\Http\Controllers\AiPostsController::class, 'status'])->whereNumber('post');
        Route::post('/{post}/refine', [App\Http\Controllers\AiPostsController::class, 'refine'])->whereNumber('post');
        Route::post('/{post}/image', [App\Http\Controllers\AiPostsController::class, 'generateImage'])->whereNumber('post');
        Route::post('/{post}/schedule', [App\Http\Controllers\AiPostsController::class, 'schedule'])->whereNumber('post');
        Route::post('/{post}/publish', [App\Http\Controllers\AiPostsController::class, 'publish'])->whereNumber('post');
        Route::post('/{post}/cancel', [App\Http\Controllers\AiPostsController::class, 'cancel'])->whereNumber('post');
        Route::post('/{post}/retry', [App\Http\Controllers\AiPostsController::class, 'retry'])->whereNumber('post');
        Route::put('/{post}', [App\Http\Controllers\AiPostsController::class, 'update'])->whereNumber('post');
        Route::delete('/{post}', [App\Http\Controllers\AiPostsController::class, 'destroy'])->whereNumber('post');
        Route::get('/{post}', [App\Http\Controllers\AiPostsController::class, 'show'])->whereNumber('post')->name('ai-posts.show');
      });

      Route::middleware('can:blog')->group(function () {
        Route::post('blog/ai/text', [App\Http\Controllers\BlogController::class, 'aiText']);
        Route::post('blog/ai/image', [App\Http\Controllers\BlogController::class, 'aiImage']);
        Route::resource('blog', App\Http\Controllers\BlogController::class)->except('show');
        Route::post('blog/ajax', [App\Http\Controllers\BlogController::class, 'ajax']); 
        Route::get('blog/status/{id}', [App\Http\Controllers\BlogController::class, 'status']);
        Route::get('blog/sort', [App\Http\Controllers\BlogController::class, 'sortShow']);
        Route::post('blog/sort', [App\Http\Controllers\BlogController::class, 'sortSave']);
        Route::get('blog/imagedelete/{id}', [App\Http\Controllers\BlogController::class, 'removeImage']);
      });

      Route::middleware('can:newsletter')->prefix('newsletter')->group(function () {
        Route::get('/', [App\Http\Controllers\NewsletterController::class, 'index'])->name('newsletter.index');
        Route::post('/ajax', [App\Http\Controllers\NewsletterController::class, 'ajax']);
        Route::get('/create', [App\Http\Controllers\NewsletterController::class, 'create'])->name('newsletter.create');
        Route::get('/{newsletter}/blogs', [App\Http\Controllers\NewsletterController::class, 'blogs'])->whereNumber('newsletter')->name('newsletter.blogs');
        Route::post('/{newsletter}/blogs', [App\Http\Controllers\NewsletterController::class, 'storeBlogs'])->whereNumber('newsletter')->name('newsletter.blogs.store');
        Route::get('/{newsletter}/content', [App\Http\Controllers\NewsletterController::class, 'content'])->whereNumber('newsletter')->name('newsletter.content');
        Route::post('/{newsletter}/content', [App\Http\Controllers\NewsletterController::class, 'storeContent'])->whereNumber('newsletter')->name('newsletter.content.store');
        Route::post('/{newsletter}/ai', [App\Http\Controllers\NewsletterController::class, 'ai'])->whereNumber('newsletter')->name('newsletter.ai');
        Route::get('/{newsletter}/review', [App\Http\Controllers\NewsletterController::class, 'review'])->whereNumber('newsletter')->name('newsletter.review');
        Route::get('/{newsletter}/preview', [App\Http\Controllers\NewsletterController::class, 'preview'])->whereNumber('newsletter')->name('newsletter.preview');
        Route::get('/{newsletter}/publish', [App\Http\Controllers\NewsletterController::class, 'publishForm'])->whereNumber('newsletter')->name('newsletter.publish');
        Route::post('/{newsletter}/publish', [App\Http\Controllers\NewsletterController::class, 'publish'])->whereNumber('newsletter')->name('newsletter.publish.store');
        Route::delete('/{newsletter}', [App\Http\Controllers\NewsletterController::class, 'destroy'])->whereNumber('newsletter')->name('newsletter.destroy');
      });
	});
});

