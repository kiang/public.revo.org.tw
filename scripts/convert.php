#!/usr/bin/env php
<?php
/**
 * JSON to GeoJSON Converter CLI Script
 */

require_once __DIR__ . '/../src/Converters/JsonToGeoJsonConverter.php';

// Load configuration
$config = require __DIR__ . '/../config/config.php';

$converter = new SolarToGeoJSON();

echo "Converting Taiwan Solar data to GeoJSON format...\n\n";

try {
    $converter->convert();
    $converter->showStatistics();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nConversion completed successfully!\n";