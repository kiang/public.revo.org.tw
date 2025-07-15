#!/usr/bin/env php
<?php
/**
 * Grid GeoJSON Generator CLI Script
 */

require_once __DIR__ . '/../src/Generators/GridGeoJsonGenerator.php';

// Load configuration
$config = require __DIR__ . '/../config/config.php';

$generator = new GridGeoJSONGenerator();

echo "Generating GeoJSON for crawler grid points...\n\n";

try {
    $generator->generateGridGeoJSON();
    
    // Ask if user wants search radius circles too
    echo "\nGenerate search radius circles? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $answer = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($answer) === 'y' || strtolower($answer) === 'yes') {
        $generator->generateSearchRadiusGeoJSON();
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nGrid generation completed successfully!\n";