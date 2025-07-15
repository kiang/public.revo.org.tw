<?php
/**
 * Convert taiwan_solar_all.json to GeoJSON format
 */

class SolarToGeoJSON {
    private $inputFile = 'taiwan_solar_all.json';
    private $outputFile = 'taiwan_solar_all.geojson';
    
    public function convert() {
        echo "Converting {$this->inputFile} to GeoJSON format...\n";
        
        // Read input JSON file
        if (!file_exists($this->inputFile)) {
            die("Error: {$this->inputFile} not found!\n");
        }
        
        $data = json_decode(file_get_contents($this->inputFile), true);
        if (!$data || !isset($data['points'])) {
            die("Error: Invalid JSON format or no points found!\n");
        }
        
        $points = $data['points'];
        echo "Processing " . count($points) . " solar installations...\n";
        
        // Create GeoJSON structure
        $geojson = [
            'type' => 'FeatureCollection',
            'metadata' => [
                'generated' => date('Y-m-d H:i:s'),
                'total_installations' => count($points),
                'source' => 'REVO GraphicAPI',
                'original_timestamp' => $data['timestamp'] ?? null
            ],
            'features' => []
        ];
        
        $processed = 0;
        $skipped = 0;
        
        foreach ($points as $point) {
            $feature = $this->convertPointToFeature($point);
            if ($feature) {
                $geojson['features'][] = $feature;
                $processed++;
            } else {
                $skipped++;
            }
        }
        
        // Save GeoJSON file
        $jsonOutput = json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->outputFile, $jsonOutput);
        
        echo "Conversion completed!\n";
        echo "- Processed: $processed points\n";
        echo "- Skipped: $skipped points (missing coordinates)\n";
        echo "- Output: {$this->outputFile}\n";
        echo "- File size: " . number_format(filesize($this->outputFile) / 1024, 1) . " KB\n";
    }
    
    private function convertPointToFeature($point) {
        // Check for required coordinates
        $longitude = $point['x'] ?? null;
        $latitude = $point['y'] ?? null;
        
        if (!$longitude || !$latitude || !is_numeric($longitude) || !is_numeric($latitude)) {
            return null;
        }
        
        // Extract installer information
        $installerInfo = $this->extractInstallerInfo($point);
        
        // Create GeoJSON feature
        $feature = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [
                    floatval($longitude),
                    floatval($latitude)
                ]
            ],
            'properties' => [
                'pointId' => $point['pointId'] ?? null,
                'id' => $point['id'] ?? null,
                'name' => $point['name'] ?? null,
                'address' => $point['address'] ?? null,
                'countryId' => $point['countryId'] ?? null,
                'townId' => $point['townId'] ?? null,
                'village' => $point['village'] ?? null,
                'neighborhood' => $point['neighborhood'] ?? null,
                'region' => $point['region'] ?? null,
                'categoryId' => $point['categoryId'] ?? null,
                'dTypeId' => $point['dTypeId'] ?? null,
                'coordSysId' => $point['coordSysId'] ?? null,
                'seq' => $point['seq'] ?? null,
                
                // Installer/facility information
                'installer_name' => $installerInfo['installer_name'],
                'status' => $installerInfo['status'],
                'renewable_type' => $installerInfo['renewable_type'],
                'equipment_type' => $installerInfo['equipment_type'],
                'location_type' => $installerInfo['location_type'],
                'capacity_kw' => $installerInfo['capacity'] ? floatval($installerInfo['capacity']) : null,
                
                // Original groupContent for reference
                'original_group_content' => $point['groupContent'] ?? null
            ]
        ];
        
        return $feature;
    }
    
    private function extractInstallerInfo($point) {
        $info = [
            'installer_name' => null,
            'status' => null,
            'renewable_type' => null,
            'equipment_type' => null,
            'location_type' => null,
            'capacity' => null
        ];
        
        if (isset($point['groupContent']) && is_array($point['groupContent'])) {
            foreach ($point['groupContent'] as $group) {
                if (isset($group['value'])) {
                    $valueData = json_decode($group['value'], true);
                    if ($valueData && is_array($valueData)) {
                        $info['installer_name'] = $valueData['設置者名稱'] ?? null;
                        $info['status'] = $valueData['案件狀態'] ?? null;
                        $info['renewable_type'] = $valueData['再生能源類別'] ?? null;
                        $info['equipment_type'] = $valueData['設備型別'] ?? null;
                        $info['location_type'] = $valueData['設置位置'] ?? null;
                        $info['capacity'] = $valueData['商轉容量'] ?? null;
                        break; // Use first valid entry
                    }
                }
            }
        }
        
        return $info;
    }
    
    public function showStatistics() {
        if (!file_exists($this->outputFile)) {
            echo "GeoJSON file not found. Run convert() first.\n";
            return;
        }
        
        $geojson = json_decode(file_get_contents($this->outputFile), true);
        $features = $geojson['features'] ?? [];
        
        echo "\n=== GEOJSON STATISTICS ===\n";
        echo "Total features: " . count($features) . "\n";
        
        // Count by status
        $statusCounts = [];
        $locationCounts = [];
        $totalCapacity = 0;
        $validCapacityCount = 0;
        
        foreach ($features as $feature) {
            $props = $feature['properties'];
            
            $status = $props['status'] ?: 'Unknown';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
            
            $locationType = $props['location_type'] ?: 'Unknown';
            $locationCounts[$locationType] = ($locationCounts[$locationType] ?? 0) + 1;
            
            if ($props['capacity_kw'] && is_numeric($props['capacity_kw'])) {
                $totalCapacity += $props['capacity_kw'];
                $validCapacityCount++;
            }
        }
        
        echo "\nBy Status:\n";
        foreach ($statusCounts as $status => $count) {
            echo "  $status: $count\n";
        }
        
        echo "\nBy Location Type:\n";
        foreach ($locationCounts as $locationType => $count) {
            echo "  $locationType: $count\n";
        }
        
        echo "\nCapacity:\n";
        echo "  Total: " . number_format($totalCapacity, 2) . " kW\n";
        echo "  Installations with capacity data: $validCapacityCount\n";
        echo "  Average capacity: " . number_format($totalCapacity / max($validCapacityCount, 1), 2) . " kW\n";
        echo "========================\n";
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $converter = new SolarToGeoJSON();
    $converter->convert();
    $converter->showStatistics();
} else {
    echo "This script should be run from command line.\n";
}