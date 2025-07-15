<?php
/**
 * Taiwan Solar Power Crawler Configuration
 */

return [
    // API Configuration
    'api' => [
        'base_url' => 'https://public.revo.org.tw/GraphicAPI/api/Point',
        'pre_request_url' => 'https://public.revo.org.tw/GraphicAPI/api/Point/GetOnePointByQuery',
        'timeout' => 30,
        'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',
        'headers' => [
            'Accept' => 'application/json',
            'Accept-Language' => 'zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
            'Origin' => 'https://public.revo.org.tw',
            'Pragma' => 'no-cache',
            'Referer' => 'https://public.revo.org.tw/GraphicWeb'
        ]
    ],
    
    // Crawler Configuration
    'crawler' => [
        'delay_seconds' => 0.5,
        'grid_spacing' => 0.1, // degrees (~11km)
        'search_radius' => 10000, // meters
        'cookie_file' => 'cache/cookies.txt',
        'save_frequency' => 5 // Save progress every N grid points
    ],
    
    // Taiwan Geographic Bounds
    'bounds' => [
        'minLat' => 21.9,   // Kaohsiung area
        'maxLat' => 25.3,   // Taipei area
        'minLng' => 119.5,  // Exclude far western waters
        'maxLng' => 121.9   // Exclude far eastern waters
    ],
    
    // File Paths
    'files' => [
        'raw_data' => 'data/raw/taiwan_solar_all.json',
        'geojson_data' => 'data/geojson/taiwan_solar_all.geojson',
        'grid_geojson' => 'data/geojson/taiwan_grid_850.geojson',
        'log_file' => 'logs/crawler.log',
        'csv_export' => 'data/processed/taiwan_solar_all.csv'
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'max_size' => 10485760 // 10MB
    ]
];