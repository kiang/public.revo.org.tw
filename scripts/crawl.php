#!/usr/bin/env php
<?php
/**
 * Taiwan Solar Crawler CLI Script
 */

require_once __DIR__ . '/../src/Crawlers/TaiwanSolarCrawler.php';

// Load configuration
$config = require __DIR__ . '/../config/config.php';

// Update paths in crawler to use config values
$crawler = new FastTaiwanSolarCrawler();

echo "Starting Taiwan Solar Power Plant Crawler...\n";
echo "Configuration:\n";
echo "- Grid points: 850\n";
echo "- Grid spacing: {$config['crawler']['grid_spacing']}° (~11km)\n";
echo "- Search radius: " . ($config['crawler']['search_radius'] / 1000) . "km\n";
echo "- Delay between requests: {$config['crawler']['delay_seconds']}s\n";
echo "\nPress Ctrl+C to stop at any time (progress will be saved)\n\n";

try {
    $crawler->crawl();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nCrawling completed successfully!\n";