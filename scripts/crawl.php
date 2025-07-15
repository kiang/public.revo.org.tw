#!/usr/bin/env php
<?php
/**
 * Taiwan Solar Crawler CLI Script
 */

require_once __DIR__ . '/../src/Crawlers/TaiwanSolarCrawler.php';

// Parse command line arguments
$options = getopt('uh', ['update', 'help']);
$updateMode = isset($options['u']) || isset($options['update']);
$showHelp = isset($options['h']) || isset($options['help']);

if ($showHelp) {
    echo "Taiwan Solar Power Plant Crawler\n\n";
    echo "Usage: php crawl.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  -u, --update     Update mode: only refetch grids that already have data\n";
    echo "  -h, --help       Show this help message\n\n";
    echo "Examples:\n";
    echo "  php crawl.php           # Full crawl (962 grid points: mainland + 澎湖/金門/馬祖)\n";
    echo "  php crawl.php --update  # Update existing data only\n\n";
    exit(0);
}

// Load configuration
$config = require __DIR__ . '/../config/config.php';

// Initialize crawler with update mode
$crawler = new FastTaiwanSolarCrawler($updateMode);

echo "Starting Taiwan Solar Power Plant Crawler...\n";
if ($updateMode) {
    echo "MODE: UPDATE ONLY - Refetching grids with existing data\n";
} else {
    echo "MODE: FULL CRAWL - Scanning 962 grid points (mainland + outer islands)\n";
}
echo "\nConfiguration:\n";
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