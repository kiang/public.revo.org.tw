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
    
    // Taiwan Geographic Bounds (including outer islands)
    'bounds' => [
        // Taiwan mainland
        'mainland' => [
            'minLat' => 21.9,   // Kaohsiung area
            'maxLat' => 25.3,   // Taipei area
            'minLng' => 119.5,  // Western coast
            'maxLng' => 121.9   // Eastern coast
        ],
        
        // Outer islands
        'penghu' => [
            'minLat' => 23.2,   // Penghu (澎湖) southern islands
            'maxLat' => 23.8,   // Penghu northern islands  
            'minLng' => 119.2,  // Penghu western islands
            'maxLng' => 119.9   // Penghu eastern islands
        ],
        
        'kinmen' => [
            'minLat' => 24.2,   // Kinmen (金門) southern area
            'maxLat' => 24.6,   // Kinmen northern area
            'minLng' => 118.1,  // Kinmen western area
            'maxLng' => 118.6   // Kinmen eastern area
        ],
        
        'matsu' => [
            'minLat' => 25.9,   // Matsu (馬祖) southern islands
            'maxLat' => 26.4,   // Matsu northern islands
            'minLng' => 119.8,  // Matsu western islands
            'maxLng' => 120.5   // Matsu eastern islands
        ]
    ],
    
    // File Paths
    'files' => [
        'raw_data' => 'data/raw/taiwan_solar_all.json',
        'geojson_data' => 'data/geojson/taiwan_solar_all.geojson',
        'grid_geojson' => 'data/geojson/taiwan_grid_962.geojson',
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