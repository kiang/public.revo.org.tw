# Taiwan Solar Power Plant Data Crawler

A comprehensive crawler and analysis toolkit for Taiwan's solar power plant installations using the REVO GraphicAPI.

## Overview

This project systematically collects and processes data about solar power installations across Taiwan, providing tools for data collection, conversion, and geographic visualization.

## Features

- **Systematic Grid-Based Crawling**: Uses 850 grid points with 0.1° spacing (~11km) for complete Taiwan coverage
- **Smart Session Management**: Handles required pre-request authentication automatically
- **Multiple Output Formats**: Raw JSON, GeoJSON for mapping, and CSV for analysis
- **Visualization Tools**: Generate grid coverage maps and installation point maps
- **Efficient Data Collection**: Rate-limited requests with progress tracking and auto-save

## Project Structure

```
taiwan-solar-crawler/
├── src/                    # Source code
│   ├── Crawlers/          # Data collection scripts
│   ├── Converters/        # Data format converters
│   └── Generators/        # Utility generators
├── data/                  # Data storage
│   ├── raw/              # Original JSON data
│   ├── processed/        # Processed data (CSV, etc.)
│   └── geojson/          # GeoJSON files for mapping
├── config/               # Configuration files
├── logs/                 # Application logs
├── cache/                # Temporary files (cookies, etc.)
├── docs/                 # Documentation
└── scripts/              # Utility scripts
```

## Installation

1. Clone the repository:
```bash
git clone [repository-url]
cd taiwan-solar-crawler
```

2. Install PHP dependencies (requires PHP 7.4+):
```bash
composer install
```

3. Create required directories:
```bash
mkdir -p cache logs
chmod 755 cache logs
```

## Usage

### 1. Crawl Solar Installation Data

```bash
php scripts/crawl.php
```

This will:
- Start from the bottom-left corner of Taiwan (119.5°E, 21.9°N)
- Systematically scan 850 grid points
- Save progress automatically every 5 points
- Generate `data/raw/taiwan_solar_all.json`

### 2. Convert to GeoJSON

```bash
php scripts/convert.php
```

Converts raw JSON data to GeoJSON format for use in mapping applications.

### 3. Generate Grid Visualization

```bash
php scripts/generate-grid.php
```

Creates a GeoJSON file showing the 850 grid points used for crawling.

## Configuration

Edit `config/config.php` to customize:
- API endpoints and headers
- Crawling parameters (delay, grid spacing, search radius)
- Geographic bounds
- File paths
- Logging settings

## Data Format

### Raw JSON Structure
```json
{
    "timestamp": "2025-06-29 07:27:28",
    "total_points": 1068,
    "grid_bounds": {...},
    "points": [
        {
            "pointId": 940017,
            "name": "台南市安南區怡中段0327-0000地號",
            "x": "120.200072",
            "y": "23.045769",
            "groupContent": [...]
        }
    ]
}
```

### GeoJSON Properties
Each feature includes:
- Basic info: pointId, name, address
- Location data: coordinates, region IDs
- Installation details: installer name, status, capacity
- Equipment info: type, location type (ground/rooftop/floating)

## Current Dataset

As of the last crawl:
- **Total Installations**: 1,068
- **Total Capacity**: 8.8 GW
- **Coverage**: ~35% of Taiwan (300/850 grid points)
- **Status**: 565 operational, 503 with permits
- **Types**: 970 ground-mounted, 32 rooftop, 66 floating

## API Information

- **Base URL**: `https://public.revo.org.tw/GraphicAPI/api/Point`
- **Authentication**: Session-based via pre-request
- **Rate Limiting**: 0.5 second delay between requests
- **Search Method**: Radius-based queries (10km default)

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Data source: Taiwan REVO (Renewable Energy Voluntary Organization)
- Geographic data integration: TGOS API

## Contact

For questions or issues, please open an issue on GitHub.