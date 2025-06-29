<?php
/**
 * Generate GeoJSON for the 850 grid points used by taiwan_solar_fast.php
 */

class GridGeoJSONGenerator {
    private $outputFile = 'taiwan_grid_850.geojson';
    
    // Taiwan bounding box - same as taiwan_solar_fast.php
    private $taiwanBounds = [
        'minLat' => 21.9,   // Kaohsiung area
        'maxLat' => 25.3,   // Taipei area
        'minLng' => 119.5,  // Exclude far western waters
        'maxLng' => 121.9   // Exclude far eastern waters
    ];
    
    public function generateGridGeoJSON() {
        echo "Generating GeoJSON for 850 grid points...\n";
        
        // Generate the same grid coordinates as taiwan_solar_fast.php
        $gridCoords = $this->generateOptimizedGrid();
        echo "Generated " . count($gridCoords) . " grid points\n";
        
        // Create GeoJSON structure
        $geojson = [
            'type' => 'FeatureCollection',
            'metadata' => [
                'generated' => date('Y-m-d H:i:s'),
                'description' => 'Grid points used by taiwan_solar_fast.php crawler',
                'total_points' => count($gridCoords),
                'grid_spacing' => '0.1 degrees (~11km)',
                'search_radius' => '10km per point',
                'bounds' => $this->taiwanBounds
            ],
            'features' => []
        ];
        
        // Convert each grid point to a GeoJSON feature
        foreach ($gridCoords as $index => $coord) {
            $feature = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        $coord['lng'],
                        $coord['lat']
                    ]
                ],
                'properties' => [
                    'grid_index' => $index + 1,
                    'latitude' => $coord['lat'],
                    'longitude' => $coord['lng'],
                    'search_radius_km' => 10,
                    'grid_id' => "grid_" . ($index + 1),
                    'row' => $this->calculateRow($coord['lat']),
                    'col' => $this->calculateCol($coord['lng'])
                ]
            ];
            
            $geojson['features'][] = $feature;
        }
        
        // Save GeoJSON file
        $jsonOutput = json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->outputFile, $jsonOutput);
        
        echo "Grid GeoJSON generated!\n";
        echo "- Output file: {$this->outputFile}\n";
        echo "- Total features: " . count($geojson['features']) . "\n";
        echo "- File size: " . number_format(filesize($this->outputFile) / 1024, 1) . " KB\n";
        
        $this->showGridInfo($gridCoords);
    }
    
    /**
     * Generate optimized grid covering Taiwan efficiently (same as taiwan_solar_fast.php)
     */
    private function generateOptimizedGrid() {
        $coords = [];
        $step = 0.1; // ~11km spacing for faster coverage
        
        // Start from bottom-left, move systematically
        for ($lat = $this->taiwanBounds['minLat']; $lat <= $this->taiwanBounds['maxLat']; $lat += $step) {
            for ($lng = $this->taiwanBounds['minLng']; $lng <= $this->taiwanBounds['maxLng']; $lng += $step) {
                $coords[] = [
                    'lat' => round($lat, 3),
                    'lng' => round($lng, 3)
                ];
            }
        }
        
        return $coords;
    }
    
    /**
     * Calculate row number based on latitude
     */
    private function calculateRow($lat) {
        return intval(($lat - $this->taiwanBounds['minLat']) / 0.1) + 1;
    }
    
    /**
     * Calculate column number based on longitude
     */
    private function calculateCol($lng) {
        return intval(($lng - $this->taiwanBounds['minLng']) / 0.1) + 1;
    }
    
    /**
     * Show grid information
     */
    private function showGridInfo($gridCoords) {
        echo "\n=== GRID INFORMATION ===\n";
        echo "Grid spacing: 0.1 degrees (~11km)\n";
        echo "Search radius per point: 10km\n";
        echo "Bounds:\n";
        echo "  Latitude: {$this->taiwanBounds['minLat']} to {$this->taiwanBounds['maxLat']}\n";
        echo "  Longitude: {$this->taiwanBounds['minLng']} to {$this->taiwanBounds['maxLng']}\n";
        
        $latRange = $this->taiwanBounds['maxLat'] - $this->taiwanBounds['minLat'];
        $lngRange = $this->taiwanBounds['maxLng'] - $this->taiwanBounds['minLng'];
        $rows = intval($latRange / 0.1) + 1;
        $cols = intval($lngRange / 0.1) + 1;
        
        echo "Grid dimensions: {$rows} rows × {$cols} columns\n";
        echo "Coverage area: ~" . number_format($latRange * 111, 0) . "km × " . number_format($lngRange * 111, 0) . "km\n";
        
        // Show first few and last few points
        echo "\nFirst 5 grid points:\n";
        for ($i = 0; $i < min(5, count($gridCoords)); $i++) {
            $coord = $gridCoords[$i];
            echo "  " . ($i + 1) . ": ({$coord['lng']}, {$coord['lat']})\n";
        }
        
        echo "\nLast 5 grid points:\n";
        for ($i = max(0, count($gridCoords) - 5); $i < count($gridCoords); $i++) {
            $coord = $gridCoords[$i];
            echo "  " . ($i + 1) . ": ({$coord['lng']}, {$coord['lat']})\n";
        }
        echo "========================\n";
    }
    
    /**
     * Generate additional circle polygons showing search radius for each point
     */
    public function generateSearchRadiusGeoJSON($outputFile = 'taiwan_grid_search_circles.geojson') {
        echo "Generating search radius circles GeoJSON...\n";
        
        $gridCoords = $this->generateOptimizedGrid();
        
        $geojson = [
            'type' => 'FeatureCollection',
            'metadata' => [
                'generated' => date('Y-m-d H:i:s'),
                'description' => 'Search radius circles (10km) for each grid point',
                'total_circles' => count($gridCoords)
            ],
            'features' => []
        ];
        
        foreach ($gridCoords as $index => $coord) {
            // Create circle polygon (approximate)
            $circle = $this->createCirclePolygon($coord['lng'], $coord['lat'], 10); // 10km radius
            
            $feature = [
                'type' => 'Feature',
                'geometry' => $circle,
                'properties' => [
                    'grid_index' => $index + 1,
                    'center_lat' => $coord['lat'],
                    'center_lng' => $coord['lng'],
                    'radius_km' => 10,
                    'grid_id' => "search_" . ($index + 1)
                ]
            ];
            
            $geojson['features'][] = $feature;
        }
        
        file_put_contents($outputFile, json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Search circles GeoJSON generated: $outputFile\n";
        echo "- File size: " . number_format(filesize($outputFile) / 1024, 1) . " KB\n";
    }
    
    /**
     * Create a circle polygon approximation
     */
    private function createCirclePolygon($centerLng, $centerLat, $radiusKm, $points = 32) {
        $coordinates = [];
        $earthRadius = 6371; // Earth radius in km
        
        for ($i = 0; $i <= $points; $i++) {
            $angle = ($i * 360 / $points) * (M_PI / 180); // Convert to radians
            
            // Calculate point on circle
            $lat = $centerLat + ($radiusKm / $earthRadius) * (180 / M_PI) * cos($angle);
            $lng = $centerLng + ($radiusKm / $earthRadius) * (180 / M_PI) * sin($angle) / cos($centerLat * M_PI / 180);
            
            $coordinates[] = [round($lng, 6), round($lat, 6)];
        }
        
        return [
            'type' => 'Polygon',
            'coordinates' => [$coordinates]
        ];
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $generator = new GridGeoJSONGenerator();
    $generator->generateGridGeoJSON();
    
    // Ask if user wants search radius circles too
    echo "\nGenerate search radius circles? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $answer = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($answer) === 'y' || strtolower($answer) === 'yes') {
        $generator->generateSearchRadiusGeoJSON();
    }
} else {
    echo "This script should be run from command line.\n";
}