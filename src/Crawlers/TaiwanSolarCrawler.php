<?php
/**
 * Fast Taiwan Solar Power Plant Crawler
 * Uses optimized grid strategy starting from left-bottom corner
 */

class FastTaiwanSolarCrawler {
    private $apiUrl = 'https://public.revo.org.tw/GraphicAPI/api/Point';
    private $preRequestUrl = 'https://public.revo.org.tw/GraphicAPI/api/Point/GetOnePointByQuery';
    private $dataFile = 'data/raw/taiwan_solar_all.json';
    private $csvFile = 'data/processed/taiwan_solar_all.csv';
    private $logFile = 'logs/crawler.log';
    private $delaySeconds = 0.5; // Faster rate limiting
    
    // Taiwan bounding boxes - mainland and outer islands
    private $taiwanBounds = [
        'mainland' => [
            'minLat' => 21.9, 'maxLat' => 25.3,
            'minLng' => 119.5, 'maxLng' => 121.9
        ],
        'penghu' => [
            'minLat' => 23.2, 'maxLat' => 23.8,
            'minLng' => 119.2, 'maxLng' => 119.9
        ],
        'kinmen' => [
            'minLat' => 24.2, 'maxLat' => 24.6,
            'minLng' => 118.1, 'maxLng' => 118.6
        ],
        'matsu' => [
            'minLat' => 25.9, 'maxLat' => 26.4,
            'minLng' => 119.8, 'maxLng' => 120.5
        ]
    ];
    
    private $allPoints = [];
    private $processedAreas = [];
    private $updateMode = false;
    private $populatedGrids = [];
    
    public function __construct($updateMode = false) {
        $this->updateMode = $updateMode;
        $this->log("Fast Taiwan Solar Crawler initialized" . ($updateMode ? " (UPDATE MODE)" : ""));
    }
    
    /**
     * Main crawling function with optimized strategy
     */
    public function crawl() {
        $this->log("Starting fast crawl process");
        $this->loadExistingData();
        
        // In update mode, identify grids that already have data
        if ($this->updateMode) {
            $this->identifyPopulatedGrids();
        }
        
        // Use larger grid with overlapping coverage
        $gridCoords = $this->generateOptimizedGrid();
        
        // Filter grids if in update mode
        if ($this->updateMode && !empty($this->populatedGrids)) {
            $originalCount = count($gridCoords);
            $gridCoords = $this->filterGridsForUpdate($gridCoords);
            $this->log("Update mode: Filtering from $originalCount to " . count($gridCoords) . " grids with existing data");
        }
        
        $this->log("Generated " . count($gridCoords) . " optimized grid points");
        
        $totalNewPoints = 0;
        $gridSize = count($gridCoords);
        
        foreach ($gridCoords as $index => $coord) {
            $progress = $index + 1;
            $this->log("Processing grid $progress/$gridSize - Lat: {$coord['lat']}, Lng: {$coord['lng']}");
            
            $points = $this->fetchPointsAtLocation($coord['lng'], $coord['lat'], 10000); // 10km radius
            
            if ($points && count($points) > 0) {
                $newPoints = $this->filterNewPoints($points);
                if (count($newPoints) > 0) {
                    $this->allPoints = array_merge($this->allPoints, $newPoints);
                    $totalNewPoints += count($newPoints);
                    $this->log("Found " . count($newPoints) . " new points. Total: " . count($this->allPoints));
                }
            }
            
            // Save progress more frequently for large datasets
            if ($progress % 5 === 0 || $progress === $gridSize) {
                $this->saveData();
            }
            
            // Rate limiting
            usleep($this->delaySeconds * 1000000);
        }
        
        $this->saveData();
        $this->exportToCsv();
        $this->log("Fast crawl completed. Total points: " . count($this->allPoints) . " (New: $totalNewPoints)");
        $this->showSummary();
    }
    
    /**
     * Generate optimized grid covering Taiwan efficiently (mainland + outer islands)
     */
    private function generateOptimizedGrid() {
        $coords = [];
        $step = 0.1; // ~11km spacing for faster coverage
        
        $this->log("Generating grid for Taiwan mainland + outer islands (澎湖, 金門, 馬祖)");
        
        foreach ($this->taiwanBounds as $region => $bounds) {
            $this->log("Processing region: $region - ({$bounds['minLng']}, {$bounds['minLat']}) to ({$bounds['maxLng']}, {$bounds['maxLat']})");
            
            $regionCoords = [];
            // Generate grid for this region
            for ($lat = $bounds['minLat']; $lat <= $bounds['maxLat']; $lat += $step) {
                for ($lng = $bounds['minLng']; $lng <= $bounds['maxLng']; $lng += $step) {
                    $regionCoords[] = [
                        'lat' => round($lat, 3),
                        'lng' => round($lng, 3),
                        'region' => $region
                    ];
                }
            }
            
            $this->log("Region $region: " . count($regionCoords) . " grid points");
            $coords = array_merge($coords, $regionCoords);
        }
        
        $this->log("Total grid points: " . count($coords) . " (mainland + outer islands)");
        return $coords;
    }
    
    /**
     * Make pre-request to initialize session
     */
    private function makePreRequest($lng, $lat, $radius) {
        $payload = [
            'Mode' => 3,
            'X' => (string)$lng,
            'Y' => (string)$lat,
            'Radius' => (string)$radius
        ];
        
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Origin: https://public.revo.org.tw',
            'Referer: https://public.revo.org.tw/GraphicWeb',
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->preRequestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_COOKIEJAR => 'cache/cookies.txt',
            CURLOPT_COOKIEFILE => 'cache/cookies.txt'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    /**
     * Fetch points from API at specific location
     */
    private function fetchPointsAtLocation($lng, $lat, $radius = 10000) {
        // Make pre-request
        $this->makePreRequest($lng, $lat, $radius);
        
        $payload = [
            'Mode' => 3,
            'X' => (string)$lng,
            'Y' => (string)$lat,
            'Radius' => (string)$radius
        ];
        
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Origin: https://public.revo.org.tw',
            'Referer: https://public.revo.org.tw/GraphicWeb',
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_COOKIEJAR => 'cache/cookies.txt',
            CURLOPT_COOKIEFILE => 'cache/cookies.txt'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }
    
    /**
     * Filter out duplicate points
     */
    private function filterNewPoints($points) {
        $existingIds = $this->getExistingPointIds();
        $newPoints = [];
        
        foreach ($points as $point) {
            $pointId = $point['pointId'] ?? null;
            if ($pointId && !in_array($pointId, $existingIds)) {
                $newPoints[] = $point;
            }
        }
        
        return $newPoints;
    }
    
    /**
     * Get existing point IDs for deduplication
     */
    private function getExistingPointIds() {
        static $pointIds = null;
        
        if ($pointIds === null) {
            $pointIds = [];
            foreach ($this->allPoints as $point) {
                if (isset($point['pointId'])) {
                    $pointIds[] = $point['pointId'];
                }
            }
        }
        
        return $pointIds;
    }
    
    /**
     * Save data to JSON
     */
    private function saveData() {
        $data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_points' => count($this->allPoints),
            'grid_bounds' => $this->taiwanBounds,
            'points' => $this->allPoints
        ];
        
        file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Load existing data
     */
    private function loadExistingData() {
        if (file_exists($this->dataFile)) {
            $data = json_decode(file_get_contents($this->dataFile), true);
            if ($data && isset($data['points'])) {
                $this->allPoints = $data['points'];
                $this->log("Loaded " . count($this->allPoints) . " existing points");
            }
        }
    }
    
    /**
     * Export to CSV
     */
    private function exportToCsv() {
        $fp = fopen($this->csvFile, 'w');
        
        $headers = [
            'pointId', 'id', 'name', 'countryId', 'townId', 'address', 
            'longitude', 'latitude', 'categoryId', 'seq',
            'installer_name', 'status', 'renewable_type', 'equipment_type', 
            'location_type', 'capacity_kw'
        ];
        fputcsv($fp, $headers);
        
        foreach ($this->allPoints as $point) {
            $installerInfo = $this->extractInstallerInfo($point);
            
            fputcsv($fp, [
                $point['pointId'] ?? '',
                $point['id'] ?? '',
                $point['name'] ?? '',
                $point['countryId'] ?? '',
                $point['townId'] ?? '',
                $point['address'] ?? '',
                $point['x'] ?? '',
                $point['y'] ?? '',
                $point['categoryId'] ?? '',
                $point['seq'] ?? '',
                $installerInfo['installer_name'] ?? '',
                $installerInfo['status'] ?? '',
                $installerInfo['renewable_type'] ?? '',
                $installerInfo['equipment_type'] ?? '',
                $installerInfo['location_type'] ?? '',
                $installerInfo['capacity'] ?? ''
            ]);
        }
        
        fclose($fp);
        $this->log("Exported to {$this->csvFile}");
    }
    
    /**
     * Extract installer information
     */
    private function extractInstallerInfo($point) {
        $info = ['installer_name' => '', 'status' => '', 'renewable_type' => '', 'equipment_type' => '', 'location_type' => '', 'capacity' => ''];
        
        if (isset($point['groupContent']) && is_array($point['groupContent'])) {
            foreach ($point['groupContent'] as $group) {
                if (isset($group['value'])) {
                    $valueData = json_decode($group['value'], true);
                    if ($valueData) {
                        $info['installer_name'] = $valueData['設置者名稱'] ?? '';
                        $info['status'] = $valueData['案件狀態'] ?? '';
                        $info['renewable_type'] = $valueData['再生能源類別'] ?? '';
                        $info['equipment_type'] = $valueData['設備型別'] ?? '';
                        $info['location_type'] = $valueData['設置位置'] ?? '';
                        $info['capacity'] = $valueData['商轉容量'] ?? '';
                        break;
                    }
                }
            }
        }
        
        return $info;
    }
    
    /**
     * Show summary statistics
     */
    private function showSummary() {
        $totalCapacity = 0;
        $statusCounts = [];
        $locationCounts = [];
        
        foreach ($this->allPoints as $point) {
            $info = $this->extractInstallerInfo($point);
            
            $status = $info['status'] ?: 'Unknown';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
            
            $locationType = $info['location_type'] ?: 'Unknown';
            $locationCounts[$locationType] = ($locationCounts[$locationType] ?? 0) + 1;
            
            if ($info['capacity'] && is_numeric($info['capacity'])) {
                $totalCapacity += floatval($info['capacity']);
            }
        }
        
        $this->log("\n=== TAIWAN SOLAR SUMMARY ===");
        $this->log("Total solar installations: " . count($this->allPoints));
        $this->log("Total capacity: " . number_format($totalCapacity, 2) . " kW");
        
        $this->log("\nBy Status:");
        foreach ($statusCounts as $status => $count) {
            $this->log("  $status: $count");
        }
        
        $this->log("\nBy Location Type:");
        foreach ($locationCounts as $locationType => $count) {
            $this->log("  $locationType: $count");
        }
        $this->log("========================");
    }
    
    /**
     * Identify grid blocks that already have data from existing GeoJSON
     */
    private function identifyPopulatedGrids() {
        $geojsonFile = 'data/geojson/taiwan_solar_all.geojson';
        
        if (!file_exists($geojsonFile)) {
            $this->log("No existing GeoJSON file found for update mode");
            return;
        }
        
        $this->log("Analyzing existing GeoJSON to identify populated grids...");
        
        $geojson = json_decode(file_get_contents($geojsonFile), true);
        if (!$geojson || !isset($geojson['features'])) {
            $this->log("Invalid GeoJSON format");
            return;
        }
        
        $gridStep = 0.1;
        $gridCounts = [];
        
        foreach ($geojson['features'] as $feature) {
            $coords = $feature['geometry']['coordinates'];
            $lng = $coords[0];
            $lat = $coords[1];
            
            // Calculate which grid this point belongs to (check all regions)
            $gridLat = null;
            $gridLng = null;
            
            foreach ($this->taiwanBounds as $region => $bounds) {
                if ($lat >= $bounds['minLat'] && $lat <= $bounds['maxLat'] && 
                    $lng >= $bounds['minLng'] && $lng <= $bounds['maxLng']) {
                    $gridLat = floor(($lat - $bounds['minLat']) / $gridStep) * $gridStep + $bounds['minLat'];
                    $gridLng = floor(($lng - $bounds['minLng']) / $gridStep) * $gridStep + $bounds['minLng'];
                    break;
                }
            }
            
            if ($gridLat === null || $gridLng === null) {
                continue; // Point not in any defined region
            }
            
            $gridKey = round($gridLat, 3) . ',' . round($gridLng, 3);
            $gridCounts[$gridKey] = ($gridCounts[$gridKey] ?? 0) + 1;
        }
        
        $this->populatedGrids = array_keys($gridCounts);
        $this->log("Found " . count($this->populatedGrids) . " populated grid blocks with data");
        
        // Show top grids by installation count
        arsort($gridCounts);
        $this->log("Top 5 grids by installation count:");
        $count = 0;
        foreach ($gridCounts as $gridKey => $installations) {
            if ($count++ >= 5) break;
            $this->log("  Grid $gridKey: $installations installations");
        }
    }
    
    /**
     * Filter grid coordinates to only include those with existing data
     */
    private function filterGridsForUpdate($gridCoords) {
        $filteredGrids = [];
        
        foreach ($gridCoords as $coord) {
            $gridKey = $coord['lat'] . ',' . $coord['lng'];
            if (in_array($gridKey, $this->populatedGrids)) {
                $filteredGrids[] = $coord;
            }
        }
        
        return $filteredGrids;
    }
    
    /**
     * Log message
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}

// Note: CLI usage is now handled by scripts/crawl.php