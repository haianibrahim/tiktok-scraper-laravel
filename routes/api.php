<?php

use Illuminate\Support\Facades\Route;
use Hki98\LaravelTikTokScraper\Http\Controllers\TikTokScraperController;

Route::group([
    'prefix' => 'api/tiktok-scraper',
    'middleware' => ['api'],
], function () {
    // Main scraping endpoint
    Route::post('/scrape', [TikTokScraperController::class, 'scrape'])
        ->name('tiktok-scraper.scrape');
    
    // Bulk scraping endpoint
    Route::post('/bulk-scrape', [TikTokScraperController::class, 'bulkScrape'])
        ->name('tiktok-scraper.bulk-scrape');
    
    // URL validation endpoint
    Route::post('/validate', [TikTokScraperController::class, 'validateUrl'])
        ->name('tiktok-scraper.validate');
    
    // Statistics endpoint
    Route::get('/stats', [TikTokScraperController::class, 'stats'])
        ->name('tiktok-scraper.stats');
    
    // Cache management endpoints
    Route::delete('/cache', [TikTokScraperController::class, 'clearCache'])
        ->name('tiktok-scraper.clear-cache');
    
    Route::delete('/cache/{url}', [TikTokScraperController::class, 'clearUrlCache'])
        ->name('tiktok-scraper.clear-url-cache');
    
    // Health check endpoint
    Route::get('/health', [TikTokScraperController::class, 'health'])
        ->name('tiktok-scraper.health');
});
