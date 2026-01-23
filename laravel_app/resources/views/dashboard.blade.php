<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SDP Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* ========================================
           UNIFIED COLOR PALETTE
           ======================================== */
        :root {
            /* Primary gradient for branding */
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --primary-color: #667eea;
            
            /* Semantic category colors - Used consistently throughout */
            --stock-color: #10b981;          /* Green - In Stock */
            --stock-bg: #dcfce7;
            --rented-color: #f59e0b;         /* Amber - Rented */
            --rented-bg: #fef3c7;
            --service-color: #ef4444;        /* Red - In Service */
            --service-bg: #fee2e2;
            --reserve-color: #ec4899;        /* Pink - Reserved */
            --reserve-bg: #fce7f3;
            --info-color: #3b82f6;           /* Blue - Info/Original */
            --info-bg: #dbeafe;
            --vendor-color: #06b6d4;         /* Cyan - Vendor */
            --vendor-bg: #cffafe;
            
            /* Neutral colors */
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
            --bg-body: #f1f5f9;
            
            /* Shadows */
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 12px 24px rgba(0,0,0,0.15);
            
            /* Spacing */
            --card-radius: 12px;
            --card-padding: 1rem;
        }
        
        body { 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; 
            background: var(--bg-body); 
            color: var(--text-primary);
        }
        
        /* ========================================
           HERO SECTION
           ======================================== */
        .hero-card { 
            background: var(--primary-gradient); 
            border-radius: 16px; 
            border: none; 
            transition: all 0.3s ease; 
        }
        .hero-card:hover { 
            transform: scale(1.01); 
            box-shadow: 0 8px 30px rgba(102,126,234,0.3); 
        }
        
        /* ========================================
           STAT CARDS - Consistent sizing
           ======================================== */
        .stat-card { 
            border-radius: var(--card-radius); 
            border: none; 
            box-shadow: var(--shadow-sm); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            cursor: pointer;
            min-height: 90px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .stat-card:hover { 
            transform: translateY(-4px) scale(1.02); 
            box-shadow: var(--shadow-lg); 
        }
        
        /* Stat values with consistent sizing */
        .stat-value { 
            font-size: 1.75rem; 
            font-weight: 700; 
            line-height: 1.2;
            transition: color 0.3s;
        }
        .stat-value.animate { animation: countUp 0.8s ease-out; }
        @keyframes countUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stat-label { 
            font-size: 0.7rem; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            font-weight: 600;
            opacity: 0.8; 
        }
        
        /* ========================================
           CATEGORY-SPECIFIC COLORS
           ======================================== */
        /* Stock category */
        .category-stock { border-left: 4px solid var(--stock-color) !important; }
        .category-stock .stat-label { color: var(--stock-color); }
        
        /* Rented category */
        .category-rented { border-left: 4px solid var(--rented-color) !important; }
        .category-rented .stat-label { color: var(--rented-color); }
        
        /* Service category */
        .category-service { border-left: 4px solid var(--service-color) !important; }
        .category-service .stat-label { color: var(--service-color); }
        
        /* Reserve category */
        .category-reserve { border-left: 4px solid var(--reserve-color) !important; }
        .category-reserve .stat-label { color: var(--reserve-color); }
        
        /* ========================================
           SECTION HEADERS - Consistent styling
           ======================================== */
        .section-header { 
            font-size: 0.9rem; 
            font-weight: 600; 
            color: var(--text-secondary); 
            margin-bottom: 1rem; 
            padding-bottom: 0.5rem; 
            border-bottom: 2px solid var(--border-color);
            transition: color 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-header:hover { color: var(--text-primary); }
        
        .section-header.text-success { color: var(--stock-color) !important; border-color: var(--stock-color); }
        .section-header.text-warning { color: var(--rented-color) !important; border-color: var(--rented-color); }
        .section-header.text-danger { color: var(--service-color) !important; border-color: var(--service-color); }
        
        /* ========================================
           BREAKDOWN ITEMS - Consistent appearance
           ======================================== */
        .breakdown-item { 
            background: #fff; 
            border-radius: 8px; 
            padding: 0.65rem 1rem; 
            margin-bottom: 0.5rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            text-decoration: none; 
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
            border: 1px solid var(--border-color); 
            position: relative;
            overflow: hidden;
        }
        .breakdown-item::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: var(--primary-gradient);
            transform: scaleY(0);
            transition: transform 0.25s ease;
        }
        .breakdown-item:hover { 
            background: var(--bg-light); 
            border-color: var(--primary-color); 
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
        }
        .breakdown-item:hover::before { transform: scaleY(1); }
        .breakdown-item .badge { transition: all 0.25s; }
        .breakdown-item:hover .badge { transform: scale(1.1); }
        
        /* ========================================
           BADGES - Unified sizing and colors
           ======================================== */
        .badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35em 0.65em;
            border-radius: 6px;
        }
        
        /* Category-specific badge colors */
        .badge-stock { background: var(--stock-color) !important; color: white; }
        .badge-rented { background: var(--rented-color) !important; color: white; }
        .badge-service { background: var(--service-color) !important; color: white; }
        .badge-reserve { background: var(--reserve-color) !important; color: white; }
        .badge-info { background: var(--info-color) !important; color: white; }
        .badge-vendor { background: var(--vendor-color) !important; color: white; }
        .badge-secondary { background: #64748b !important; color: white; }
        
        /* ========================================
           CHART CARDS - Equal heights in row
           ======================================== */
        .chart-card { 
            background: #fff; 
            border-radius: var(--card-radius); 
            box-shadow: var(--shadow-sm); 
            transition: all 0.3s;
            height: 100%;
        }
        .chart-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
        
        /* Ensure equal height cards in breakdowns row */
        .row.g-3 > [class*="col-"] > .chart-card { height: 100%; }
        
        /* ========================================
           MINI STAT CARDS (Stock Rental Status)
           ======================================== */
        .mini-stat {
            text-decoration: none;
            display: block;
            text-align: center;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.3s;
            border: 1px solid transparent;
        }
        .mini-stat:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .mini-stat .value { font-weight: 700; font-size: 1.1rem; margin-bottom: 2px; }
        .mini-stat .label { font-size: 0.65rem; text-transform: uppercase; font-weight: 600; opacity: 0.9; }
        
        .mini-stat-stock { background: var(--stock-bg); color: var(--stock-color); }
        .mini-stat-stock:hover { border-color: var(--stock-color); }
        
        .mini-stat-original { background: var(--info-bg); color: var(--info-color); }
        .mini-stat-original:hover { border-color: var(--info-color); }
        
        .mini-stat-reserve { background: var(--reserve-bg); color: var(--reserve-color); }
        .mini-stat-reserve:hover { border-color: var(--reserve-color); }
        
        /* ========================================
           SEARCH AND FILTERS
           ======================================== */
        .search-box { 
            border-radius: 50px; 
            border: 2px solid var(--border-color); 
            padding: 0.5rem 1rem; 
            transition: all 0.3s; 
        }
        .search-box:focus { 
            border-color: var(--primary-color); 
            box-shadow: 0 0 0 4px rgba(102,126,234,0.15); 
        }
        .filter-btn { 
            border-radius: 50px; 
            font-size: 0.8rem; 
            transition: all 0.3s; 
        }
        .filter-btn:hover { transform: scale(1.05); }
        
        .timestamp-badge { 
            font-size: 0.75rem; 
            background: rgba(255,255,255,0.2); 
            border-radius: 50px; 
            padding: 0.25rem 0.75rem; 
        }
        
        /* ========================================
           TOOLTIPS
           ======================================== */
        [data-tooltip] { position: relative; cursor: help; }
        [data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
            background: var(--text-primary); color: white; padding: 0.5rem 0.75rem;
            border-radius: 6px; font-size: 0.75rem; white-space: nowrap;
            opacity: 0; visibility: hidden; transition: all 0.3s;
            pointer-events: none; z-index: 100;
        }
        [data-tooltip]:hover::after { opacity: 1; visibility: visible; bottom: calc(100% + 8px); }
        
        /* ========================================
           UTILITY CLASSES
           ======================================== */
        .quick-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .quick-actions .btn { transition: all 0.25s; }
        .quick-actions .btn:hover { transform: translateY(-2px); }
        
        .collapsible-header { cursor: pointer; user-select: none; }
        .collapsible-header i.toggle-icon { transition: transform 0.3s; }
        .collapsible-header.collapsed i.toggle-icon { transform: rotate(-90deg); }
        
        .shimmer { 
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        /* Divider for breakdown sections */
        .breakdown-divider {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin: 0.75rem 0 0.5rem;
            padding-bottom: 0.25rem;
            border-bottom: 1px dashed var(--border-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>SDP DASHBOARD</a>
            <div class="d-flex gap-2">
                @if(isset($summary))
                <a href="{{ route('print') }}" class="btn btn-outline-light btn-sm" target="_blank"><i class="bi bi-printer me-1"></i> Print</a>
                @endif
                <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal"><i class="bi bi-cloud-upload me-1"></i> Import</button>
            </div>
        </div>
    </nav>

    <div class="container py-3">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(isset($summary))
        <!-- Search Bar -->
        <div class="mb-4">
            <form action="{{ route('details') }}" method="GET" class="d-flex gap-2 flex-wrap align-items-center">
                <input type="hidden" name="category" value="search">
                <input type="text" name="q" class="form-control search-box" placeholder="Search by lot number, product name..." style="max-width: 400px;">
                <button type="submit" class="btn btn-primary filter-btn"><i class="bi bi-search me-1"></i> Search</button>
                <div class="ms-auto d-flex gap-2">
                    <a href="{{ route('details', ['category' => 'vendor_rent']) }}" class="btn btn-outline-secondary filter-btn">Vendor Rent</a>
                    <a href="{{ route('details', ['category' => 'in_stock']) }}" class="btn btn-outline-success filter-btn">In Stock</a>
                    <a href="{{ route('details', ['category' => 'rented']) }}" class="btn btn-outline-warning filter-btn">Rented</a>
                </div>
            </form>
        </div>

        <!-- Hero -->
        <div class="hero-card text-white p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label text-white-50">Total Active Stock</div>
                    <div class="display-4 fw-bold">{{ number_format($summary['sdp_stock']) }}</div>
                    @if(isset($metadata['imported_at']))
                    <div class="timestamp-badge mt-2">
                        <i class="bi bi-clock me-1"></i> Last updated: {{ \Carbon\Carbon::parse($metadata['imported_at'])->format('M d, Y \a\t g:i A') }}
                    </div>
                    @endif
                </div>
                <i class="bi bi-box-seam display-4 opacity-25"></i>
            </div>
        </div>

        <!-- Stats Row (4 Columns) -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('details', ['category' => 'in_stock']) }}" class="text-decoration-none">
                    <div class="stat-card bg-white p-3 h-100" style="border-left: 4px solid var(--success-color);">
                        <div class="stat-label text-success">In Stock</div>
                        <div class="stat-value text-dark">{{ number_format($summary['in_stock']['total']) }}</div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('details', ['category' => 'rented']) }}" class="text-decoration-none">
                    <div class="stat-card bg-white p-3 h-100" style="border-left: 4px solid var(--warning-color);">
                        <div class="stat-label text-warning">Rented In Customer</div>
                        <div class="stat-value text-dark">{{ number_format($summary['rented_in_customer']['total']) }}</div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card bg-white p-3 h-100" style="border-left: 4px solid var(--danger-color);">
                    <div class="stat-label text-danger">In Service</div>
                    <div class="stat-value text-dark">{{ number_format($summary['stock_external_service']['total'] + $summary['stock_internal_service']['total'] + ($summary['stock_insurance']['total'] ?? 0)) }}</div>
                    <small class="text-muted">Ext: {{ $summary['stock_external_service']['total'] }} | Int: {{ $summary['stock_internal_service']['total'] }} | Ins: {{ $summary['stock_insurance']['total'] ?? 0 }}</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                @if(isset($summary['rental_pairs_count']) && $summary['rental_pairs_count'] > 0)
                <a href="{{ route('rental.pairs') }}" class="text-decoration-none">
                    <div class="stat-card bg-white p-3 h-100" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid var(--warning-color);">
                        <div class="d-flex justify-content-between align-items-center h-100">
                            <div>
                                <div class="stat-label" style="color: var(--rented-color);">Rental Pairs</div>
                                <div class="stat-value text-dark">{{ $summary['rental_pairs_count'] }}</div>
                                <div class="text-muted small">active pairs</div>
                            </div>
                            <i class="bi bi-arrow-left-right fs-4 text-warning" style="color: var(--rented-color) !important;"></i>
                        </div>
                    </div>
                </a>
                @else
                <div class="stat-card bg-white p-3 h-100" style="background: var(--bg-light); border-left: 4px solid var(--text-muted);">
                    <div class="stat-label text-muted">Rental Pairs</div>
                    <div class="stat-value text-muted">-</div>
                    <div class="text-muted small">No active pairs</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Historical Trends -->
        @if(count($history) > 1)
        <div class="chart-card p-3 mb-4">
            <div class="section-header"><i class="bi bi-graph-up me-2"></i>Historical Trends</div>
            <div id="trendsChart" style="height: 250px;"></div>
        </div>
        @endif

        <!-- Chart & Sidebar Grid Row -->
        <div class="row g-3 mb-4">
            <!-- Left: Pie Chart -->
            <div class="col-lg-7">
                <div class="chart-card p-3 h-100">
                    <div class="section-header"><i class="bi bi-pie-chart me-2"></i>Stock Distribution (Click to drill down)</div>
                    <div id="drilldownChart" style="height: 320px;"></div>
                </div>
            </div>
            
            <!-- Right: Sidebar Grids -->
            <div class="col-lg-5">
                <div class="chart-card p-3 h-100">
                    <!-- Ownership Grid -->
                    <div class="section-header"><i class="bi bi-building me-2"></i>Ownership</div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="text-center p-3 rounded h-100 d-flex flex-column justify-content-center" style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);">
                                <div class="stat-value text-indigo">{{ number_format($summary['sdp_stock'] - $summary['vendor_rent']) }}</div>
                                <div class="small text-muted">SDP Owned</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('details', ['category' => 'vendor_rent']) }}" class="text-decoration-none">
                                <div class="text-center p-3 rounded h-100 d-flex flex-column justify-content-center" style="background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%);">
                                    <div class="stat-value text-info">{{ number_format($summary['vendor_rent']) }}</div>
                                    <div class="small text-muted">Vendor Rent</div>
                                </div>
                            </a>
                        </div>
                    </div>
                    
                    @if(($summary['uncategorized']['total'] ?? 0) > 0)
                    <a href="{{ route('details', ['category' => 'uncategorized']) }}" class="btn btn-danger btn-sm w-100 mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i> {{ $summary['uncategorized']['total'] }} Uncategorized
                    </a>
                    @endif

                    <!-- Rental Types 2x2 Grid -->
                    <div class="section-header mt-4"><i class="bi bi-calendar-check me-2"></i>Rental Type</div>
                    <div class="row g-2">
                        <!-- Row 1 -->
                        <div class="col-6">
                            <a href="{{ route('details', ['category' => 'rental_type', 'sub' => 'Subscription']) }}" class="text-decoration-none">
                                <div class="stat-card p-3 h-100" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);">
                                    <div class="text-primary fw-bold mb-1">SUBSCRIPTION</div>
                                    <div class="h4 mb-0 text-dark">{{ number_format($summary['rental_type_summary']['Subscription'] ?? 0) }}</div>
                                    <div class="small text-muted">{{ number_format($summary['unique_rental_contracts']['Subscription'] ?? 0) }} contracts</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('details', ['category' => 'rental_type', 'sub' => 'Regular']) }}" class="text-decoration-none">
                                <div class="stat-card p-3 h-100" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);">
                                    <div class="text-warning fw-bold mb-1">REGULAR</div>
                                    <div class="h4 mb-0 text-dark">{{ number_format($summary['rental_type_summary']['Regular'] ?? 0) }}</div>
                                    <div class="small text-muted">{{ number_format($summary['unique_rental_contracts']['Regular'] ?? 0) }} contracts</div>
                                </div>
                            </a>
                        </div>
                        
                        <!-- Row 2 -->
                        <div class="col-6">
                            @if(isset($summary['reserved_subscription']) && $summary['reserved_subscription'] > 0)
                            <a href="{{ route('details', ['category' => 'reserved_subscription']) }}" class="text-decoration-none">
                                <div class="stat-card p-3 h-100" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border: 1px dashed var(--primary-color);">
                                    <div class="text-primary fw-bold mb-1">RESERVED</div>
                                    <div class="h4 mb-0 text-dark">{{ number_format($summary['reserved_subscription']) }}</div>
                                    <div class="small text-muted">Future</div>
                                </div>
                            </a>
                            @else
                            <div class="stat-card p-3 h-100 opacity-50" style="background: var(--bg-light);">
                                <div class="text-muted fw-bold mb-1">RESERVED</div>
                                <div class="h4 mb-0">-</div>
                            </div>
                            @endif
                        </div>
                        <div class="col-6">
                            @if(isset($summary['inactive_subscription']) && $summary['inactive_subscription'] > 0)
                            <a href="{{ route('details', ['category' => 'inactive_subscription']) }}" class="text-decoration-none">
                                <div class="stat-card p-3 h-100" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);">
                                    <div class="text-danger fw-bold mb-1">EXPIRED</div>
                                    <div class="h4 mb-0 text-dark">{{ number_format($summary['inactive_subscription']) }}</div>
                                    <div class="small text-muted">Past</div>
                                </div>
                            </a>
                            @else
                            <div class="stat-card p-3 h-100 opacity-50" style="background: var(--bg-light);">
                                <div class="text-muted fw-bold mb-1">EXPIRED</div>
                                <div class="h4 mb-0">-</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        
        <!-- Breakdowns -->
        <div class="row g-3">
            <!-- In Stock -->
            <div class="col-md-4">
                <div class="chart-card p-3">
                    <a href="{{ route('details', ['category' => 'in_stock']) }}" class="section-header text-success text-decoration-none d-block"><i class="bi bi-box-seam me-2"></i>In Stock <small class="text-muted">({{ $summary['in_stock']['total'] }})</small></a>
                    
                    <!-- Stock by Rental Status - Mini Stats -->
                    @if(isset($stockByRentalStatus))
                    <div class="breakdown-divider">By Rental Status</div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <a href="{{ route('details', ['category' => 'stock_pure']) }}" class="mini-stat mini-stat-stock" data-tooltip="Available for rent">
                                <div class="value">{{ $stockByRentalStatus['pure_stock'] ?? 0 }}</div>
                                <div class="label">Pure Stock</div>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="{{ route('details', ['category' => 'stock_original']) }}" class="mini-stat mini-stat-original" data-tooltip="Original In Stock">
                                <div class="value">{{ $stockByRentalStatus['original'] ?? 0 }}</div>
                                <div class="label">Original</div>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="{{ route('details', ['category' => 'stock_reserve']) }}" class="mini-stat mini-stat-reserve" data-tooltip="Reserve for Future Rental">
                                <div class="value">{{ $stockByRentalStatus['reserve'] ?? 0 }}</div>
                                <div class="label">Reserve</div>
                            </a>
                        </div>
                    </div>
                    @endif
                    
                    <div class="breakdown-divider">By Location</div>
                    @if(isset($summary['in_stock']['details']['SDP/OPERATION']))
                    <a href="{{ route('details', ['category' => 'in_stock', 'sub' => 'Operation']) }}" class="breakdown-item">
                        <span>Operation</span>
                        <span class="badge badge-stock">{{ $summary['in_stock']['details']['SDP/OPERATION']['count'] }}</span>
                    </a>
                    @endif

                    <!-- Stock for Sold Custom Placement -->
                    @if(isset($summary['in_stock']['details']['locations']['SDP/STOCK SOLD']) && $summary['in_stock']['details']['locations']['SDP/STOCK SOLD'] > 0)
                    <a href="{{ route('details', ['category' => 'in_stock', 'sub' => 'SDP/STOCK SOLD']) }}" class="breakdown-item">
                        <span>Stock for Sold</span>
                        <span class="badge badge-info">{{ $summary['in_stock']['details']['locations']['SDP/STOCK SOLD'] }}</span>
                    </a>
                    @endif

                    @if(isset($summary['in_stock']['details']['locations']))
                        @foreach($summary['in_stock']['details']['locations'] as $loc => $val)
                        @if($val > 0 && $loc !== 'SDP/STOCK SOLD')
                        <a href="{{ route('details', ['category' => 'in_stock', 'sub' => $loc]) }}" class="breakdown-item">
                            <span>{{ $loc }}</span>
                            <span class="badge badge-secondary">{{ $val }}</span>
                        </a>
                        @endif
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Rented In Customer -->
            <div class="col-md-4">
                <div class="chart-card p-3">
                    <a href="{{ route('details', ['category' => 'rented']) }}" class="section-header text-warning text-decoration-none d-block"><i class="bi bi-person-badge me-2"></i>Rented In Customer <small class="text-muted">({{ $summary['rented_in_customer']['total'] }})</small></a>
                    
                    <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Vendor Rent']) }}" class="breakdown-item">
                        <span>Vendor Rent</span>
                        <span class="badge badge-vendor">{{ $summary['rented_in_customer']['details']['Vendor Rent'] ?? 0 }}</span>
                    </a>
                    <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Original in Customer']) }}" class="breakdown-item">
                        <span>Original In Customer</span>
                        <span class="badge badge-rented">{{ $summary['rented_in_customer']['details']['Original in Customer'] ?? $summary['rented_in_customer']['details']['Orig in Customer'] ?? 0 }}</span>
                    </a>
                    
                    <!-- Replacement Parent -->
                    <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Replacement']) }}" class="breakdown-item d-flex justify-content-between align-items-center" style="background: var(--info-bg); border-left: 3px solid var(--info-color);">
                        <span class="fw-semibold text-dark">Replacement</span>
                        <span class="badge badge-info">{{ ($summary['rented_in_customer']['details']['Replacement - Service'] ?? 0) + ($summary['rented_in_customer']['details']['Replacement - RBO'] ?? 0) }}</span>
                    </a>
                    <!-- Replacement Sub-items -->
                    <div style="margin-left: 1rem; border-left: 2px solid var(--border-color); padding-left: 0.5rem; margin-bottom: 0.5rem;">
                        <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Replacement - Service']) }}" class="breakdown-item">
                            <span>Service/In Stock</span>
                            <span class="badge badge-info">{{ $summary['rented_in_customer']['details']['Replacement - Service'] ?? 0 }}</span>
                        </a>
                        <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Replacement - RBO']) }}" class="breakdown-item">
                            <span>RBO</span>
                            <span class="badge badge-info">{{ $summary['rented_in_customer']['details']['Replacement - RBO'] ?? 0 }}</span>
                        </a>
                    </div>
                    
                    <a href="{{ route('details', ['category' => 'rented', 'sub' => 'Check Rent position']) }}" class="breakdown-item mt-2">
                        <span>Check Rent position</span>
                        <span class="badge bg-dark">{{ $summary['rented_in_customer']['details']['Check Rent position'] ?? 0 }}</span>
                    </a>
                </div>
            </div>

            <!-- In Service -->
            <div class="col-md-4">
                <div class="chart-card p-3">
                    <a href="{{ route('details', ['category' => 'in_service']) }}" class="section-header text-danger text-decoration-none d-block"><i class="bi bi-tools me-2"></i>In Service <small class="text-muted">({{ $summary['stock_external_service']['total'] + $summary['stock_internal_service']['total'] + ($summary['stock_insurance']['total'] ?? 0) }})</small></a>
                    
                    <div class="breakdown-divider">External ({{ $summary['stock_external_service']['total'] }})</div>
                    @foreach($summary['stock_external_service']['details'] as $desc => $val)
                    <a href="{{ route('details', ['category' => 'external_service', 'sub' => $desc]) }}" class="breakdown-item">
                        <span>{{ str_replace(['Orig ', 'Original Rented '], ['Original ', 'Original '], $desc) }}</span>
                        <span class="badge badge-service">{{ $val }}</span>
                    </a>
                    @endforeach
                    
                    <div class="breakdown-divider">Internal ({{ $summary['stock_internal_service']['total'] }})</div>
                    @foreach($summary['stock_internal_service']['details'] as $desc => $val)
                    <a href="{{ route('details', ['category' => 'internal_service', 'sub' => $desc]) }}" class="breakdown-item">
                        <span>{{ str_replace(['Orig ', 'Original Rented '], ['Original ', 'Original '], $desc) }}</span>
                        <span class="badge badge-info">{{ $val }}</span>
                    </a>
                    @endforeach
                    
                    <div class="breakdown-divider">Insurance ({{ $summary['stock_insurance']['total'] ?? 0 }})</div>
                    @foreach(($summary['stock_insurance']['details'] ?? []) as $desc => $val)
                    <a href="{{ route('details', ['category' => 'insurance', 'sub' => $desc]) }}" class="breakdown-item">
                        <span>{{ str_replace(['Orig ', 'Original Rented '], ['Original ', 'Original '], $desc) }}</span>
                        <span class="badge bg-secondary">{{ $val }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>

        @else
        <div class="text-center py-5">
            <i class="bi bi-cloud-upload display-1 text-muted"></i>
            <h4 class="mt-3">No Data</h4>
            <p class="text-muted">Upload your Excel file to begin</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">Import Data</button>
        </div>
        @endif
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('summary.generate') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Import Data</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input class="form-control" type="file" name="file" required>
                        <div class="form-text text-danger mt-2"><i class="bi bi-exclamation-triangle"></i> Replaces existing data</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    @if(isset($summary))
    <script>
        // Historical Trends Chart
        @if(count($history) > 1)
        const trendsOptions = {
            chart: { type: 'area', height: 250, toolbar: { show: false }, zoom: { enabled: false } },
            series: [
                { name: 'Total Stock', data: [@foreach($history as $h){{ $h['sdp_stock'] }},@endforeach] },
                { name: 'In Stock', data: [@foreach($history as $h){{ $h['in_stock'] }},@endforeach] },
                { name: 'Rented', data: [@foreach($history as $h){{ $h['rented'] }},@endforeach] },
                { name: 'In Service', data: [@foreach($history as $h){{ $h['in_service'] }},@endforeach] }
            ],
            xaxis: { categories: [@foreach($history as $h)'{{ \Carbon\Carbon::parse($h['date'])->format("M d") }}',@endforeach] },
            colors: ['#667eea', '#10b981', '#f59e0b', '#ef4444'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.1 } },
            legend: { position: 'top', fontSize: '12px' },
            dataLabels: { enabled: false }
        };
        new ApexCharts(document.getElementById('trendsChart'), trendsOptions).render();
        @endif

        // Drilldown Chart
        const mainData = {
            series: [{{ $summary['in_stock']['total'] }}, {{ $summary['rented_in_customer']['total'] }}, {{ $summary['stock_external_service']['total'] + $summary['stock_internal_service']['total'] + ($summary['stock_insurance']['total'] ?? 0) }}],
            labels: ['In Stock', 'Rented', 'In Service'],
            colors: ['#10b981', '#f59e0b', '#ef4444']
        };

        const drillData = {
            'In Stock': {
                series: [
                    @if(isset($summary['in_stock']['details']['SDP/OPERATION'])){{ $summary['in_stock']['details']['SDP/OPERATION']['count'] }},@endif
                    @if(isset($summary['in_stock']['details']['locations']))
                        @foreach($summary['in_stock']['details']['locations'] as $loc => $val){{ $val }},@endforeach
                    @endif
                ],
                labels: [
                    @if(isset($summary['in_stock']['details']['SDP/OPERATION']))'Operation',@endif
                    @if(isset($summary['in_stock']['details']['locations']))
                        @foreach($summary['in_stock']['details']['locations'] as $loc => $val)'{{ $loc }}',@endforeach
                    @endif
                ]
            },
            'Rented': {
                series: [@foreach($summary['rented_in_customer']['details'] as $val){{ $val }},@endforeach],
                labels: [@foreach($summary['rented_in_customer']['details'] as $desc => $val)'{{ $desc }}',@endforeach]
            },
            'In Service': {
                series: [
                    @foreach($summary['stock_external_service']['details'] as $val){{ $val }},@endforeach
                    @foreach($summary['stock_internal_service']['details'] as $val){{ $val }},@endforeach
                    @foreach(($summary['stock_insurance']['details'] ?? []) as $val){{ $val }},@endforeach
                ],
                labels: [
                    @foreach($summary['stock_external_service']['details'] as $desc => $val)'Ext: {{ $desc }}',@endforeach
                    @foreach($summary['stock_internal_service']['details'] as $desc => $val)'Int: {{ $desc }}',@endforeach
                    @foreach(($summary['stock_insurance']['details'] ?? []) as $desc => $val)'Ins: {{ $desc }}',@endforeach
                ]
            }
        };

        let isDrilled = false;

        const chartOptions = {
            chart: {
                type: 'donut',
                height: 320,
                events: {
                    dataPointSelection: (event, chartContext, config) => {
                        if (!isDrilled) {
                            const label = mainData.labels[config.dataPointIndex];
                            if (drillData[label]) {
                                chart.updateOptions({
                                    series: drillData[label].series,
                                    labels: drillData[label].labels,
                                    title: { text: label + ' Breakdown', align: 'center' },
                                    colors: undefined
                                });
                                isDrilled = true;
                            }
                        }
                    }
                }
            },
            series: mainData.series,
            labels: mainData.labels,
            colors: mainData.colors,
            plotOptions: {
                pie: {
                    donut: {
                        size: '55%',
                        labels: { show: true, total: { show: true, label: 'Total', fontSize: '14px', fontWeight: 600 } }
                    }
                }
            },
            legend: { position: 'bottom', fontSize: '12px' },
            title: { text: 'Click segment to drill down', align: 'center', style: { fontSize: '12px', color: '#94a3b8' } },
            dataLabels: { enabled: true, formatter: (val, opts) => opts.w.config.series[opts.seriesIndex] }
        };

        const chart = new ApexCharts(document.getElementById('drilldownChart'), chartOptions);
        chart.render();

        document.getElementById('drilldownChart').addEventListener('dblclick', () => {
            if (isDrilled) {
                chart.updateOptions({
                    series: mainData.series,
                    labels: mainData.labels,
                    colors: mainData.colors,
                    title: { text: 'Click segment to drill down', align: 'center', style: { fontSize: '12px', color: '#94a3b8' } }
                });
                isDrilled = false;
            }
        });
    </script>
    @endif

    <!-- Animated Counter Script -->
    <script>
        // Animate numbers on page load
        document.addEventListener('DOMContentLoaded', function() {
            const statValues = document.querySelectorAll('.stat-value');
            
            statValues.forEach(el => {
                el.classList.add('animate');
                
                // Parse the number from the text (handle formatted numbers like "3,322")
                const text = el.textContent.trim();
                const match = text.match(/^[\d,]+/);
                if (match) {
                    const targetNum = parseInt(match[0].replace(/,/g, ''));
                    const suffix = text.substring(match[0].length);
                    
                    // Animate from 0 to target
                    let current = 0;
                    const duration = 800;
                    const increment = targetNum / (duration / 16);
                    
                    const animate = () => {
                        current += increment;
                        if (current < targetNum) {
                            el.textContent = Math.floor(current).toLocaleString() + suffix;
                            requestAnimationFrame(animate);
                        } else {
                            el.textContent = targetNum.toLocaleString() + suffix;
                        }
                    };
                    
                    // Only animate if number is significant
                    if (targetNum > 0) {
                        el.textContent = '0' + suffix;
                        requestAnimationFrame(animate);
                    }
                }
            });

            // Add smooth scroll to breakdown items
            document.querySelectorAll('.breakdown-item').forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.zIndex = '10';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.zIndex = '';
                });
            });
        });
    </script>
</body>
</html>
