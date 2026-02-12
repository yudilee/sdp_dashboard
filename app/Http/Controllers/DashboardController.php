<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\History;
use App\Services\SummaryGenerator;
use App\Services\InventoryService;
use App\Constants\Location;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index()
    {
        // Get latest summary from History
        $latest = History::orderBy('snapshot_date', 'desc')->first();
        
        $summary = $latest ? $latest->summary_json : null;
        if (!$summary) {
            // Empty state
             return view('dashboard', ['summary' => null, 'metadata' => null, 'history' => [], 'reserveRentalData' => [], 'stockByRentalStatus' => []]);
        }
        
        // Metadata mock for compatibility (or fetch from DB updated_at)
        $metadata = ['imported_at' => $latest->updated_at->format('Y-m-d H:i:s')];
        
        // History for charts
        $history = History::orderBy('snapshot_date', 'asc')->take(30)->get()->map(function($h) {
            return [
                'date' => $h->snapshot_date,
                'sdp_stock' => $h->sdp_stock,
                'in_stock' => $h->in_stock,
                'rented' => $h->rented,
                'in_service' => $h->in_service,
            ];
        })->toArray();
        
        // --- DYNAMIC CALCULATIONS (DB) ---
        $today = now()->format('Y-m-d');
        
        // Reserve Rental: Has rental_id but start date > today
        $reserveRentalData = [
            'count' => Item::whereNotNull('rental_id')->where('actual_start_rental', '>', $today)->count(),
            'items' => Item::whereNotNull('rental_id')->where('actual_start_rental', '>', $today)->take(20)->get()->toArray(),
        ];
        
        // Stock breakdown
        // Pure Stock: InStock=1, RentalID=null
        $pureStock = Item::where('in_stock', true)->whereNull('rental_id')->count();
        
        // Original Stock: InStock=1, RentalID!=null, Start <= Today (Active)
        $originalStock = Item::where('in_stock', true)
                             ->whereNotNull('rental_id')
                             ->where('actual_start_rental', '<=', $today)
                             ->count();
                             
        // Reserve Stock: InStock=1, RentalID!=null, Start > Today (Future)
        $reserveStock = Item::where('in_stock', true)
                            ->whereNotNull('rental_id')
                            ->where('actual_start_rental', '>', $today)
                            ->count();
        
        $stockByRentalStatus = [
            'pure_stock' => $pureStock,
            'original' => $originalStock,
            'reserve' => $reserveStock,
        ];

        // --- Active Rental Logic ---
        // Exclude RESERVE (future start date) from all Active Rental calculations
        // 1. Rented In Customer - need to recalculate excluding reserve
        $activeCustomer = Item::where('location', Location::RENTAL_CUSTOMER)
                              ->where('is_sold', false)
                              ->where(function($q) use ($today) {
                                  $q->whereNull('actual_start_rental')
                                    ->orWhere('actual_start_rental', '<=', $today);
                              })
                              ->count();
        
        // 2. In Stock Active (Original w/o Replace, Count=1, NOT Reserve)
        $inStockActive = Item::where('in_stock', true)
                             ->whereColumn('lot_number', 'reserved_lot')
                             ->whereNotNull('rental_id')
                             ->where('rental_id_count', 1)
                             ->where(function($q) use ($today) {
                                 $q->whereNull('actual_start_rental')
                                   ->orWhere('actual_start_rental', '<=', $today);
                             })
                             ->count();
        
        // 3. In Service Active (Original w/o Replace, Count=1, NOT Reserve)
        // Group by type for detail view (Ext, Int, Ins)
        $serviceActiveQuery = function($q) use ($today) {
             $q->whereColumn('lot_number', 'reserved_lot')
               ->whereNotNull('rental_id')
               ->where('rental_id_count', 1)
               ->where(function($sub) use ($today) {
                   $sub->whereNull('actual_start_rental')
                       ->orWhere('actual_start_rental', '<=', $today);
               });
        };

        $inServiceActiveExt = Item::where('location', 'like', Location::SERVICE_EXTERNAL . '%')->tap($serviceActiveQuery)->count();
        $inServiceActiveInt = Item::where('location', Location::SERVICE_INTERNAL)->tap($serviceActiveQuery)->count();
        $inServiceActiveIns = Item::where('location', 'like', Location::INSURANCE . '%')->tap($serviceActiveQuery)->count();
        
        // 4. Overdue Rentals - Still at customer but actual_end_rental is today or past (includes today's end)
        $overdueRentals = Item::where('location', Location::RENTAL_CUSTOMER)
                              ->where('is_sold', false)
                              ->whereNotNull('actual_end_rental')
                              ->whereDate('actual_end_rental', '<=', $today)
                              ->count();
        
        $activeRentalData = [
            'total' => $activeCustomer + $inStockActive + $inServiceActiveExt + $inServiceActiveInt + $inServiceActiveIns,
            'customer' => $activeCustomer,
            'stock' => $inStockActive,
            'service' => [
                'total' => $inServiceActiveExt + $inServiceActiveInt + $inServiceActiveIns,
                'external' => $inServiceActiveExt,
                'internal' => $inServiceActiveInt,
                'insurance' => $inServiceActiveIns
            ],
            'overdue' => $overdueRentals
        ];

        $dashboardLayout = \App\Models\Setting::get('dashboard_layout', 'kpi_progress');
        $showHistory = \App\Models\Setting::get('dashboard_show_history', 'true') === 'true';

        return view('dashboard', compact('summary', 'metadata', 'history', 'reserveRentalData', 'stockByRentalStatus', 'activeRentalData', 'dashboardLayout', 'showHistory'));
    }
    
    public function summary()
    {
        return view('summary');
    }
    
    public function upload(Request $request, SummaryGenerator $generator)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        // $file = $request->file('file')->getRealPath(); // This fails because no extension
        $file = $request->file('file');
        
        // Generate summary and items array from Excel
        $result = $generator->generate($file);
        
        // Save to Database
        $generator->saveToDatabase($result['items'], $result['summary']);

        return redirect()->back()->with('success', 'Data imported successfully.');
    }

    public function print()
    {
        $latest = History::orderBy('snapshot_date', 'desc')->first();
        $summary = $latest ? $latest->summary_json : null;
        
        if (!$summary) {
            return redirect()->route('dashboard');
        }

        $metadata = ['imported_at' => $latest->updated_at->format('Y-m-d H:i:s')];

        return view('print', compact('summary', 'metadata'));
    }

    public function details(Request $request, InventoryService $inventory)
    {
        $category = $request->query('category');
        $sub = $request->query('sub');
        $searchQuery = $request->query('q');
        
        $query = $this->buildFilterQuery($inventory, $category, $sub, $searchQuery);
        $query = $this->buildFilterQuery($inventory, $category, $sub, $searchQuery);
        $items = $query->limit(5000)->get();
        
        // Filter Options Data
        $locations = Item::select('location')->distinct()->orderBy('location')->pluck('location');
        $roles = ['Main', 'Replacement']; // Hardcoded enum-like values
        $types = Item::whereNotNull('rental_type')->where('rental_type', '!=', '')->select('rental_type')->distinct()->pluck('rental_type');

        return view('details', compact('items', 'category', 'sub', 'locations', 'roles', 'types'));
    }

    public function export(Request $request, InventoryService $inventory)
    {
        $category = $request->query('category');
        $sub = $request->query('sub');
        $searchQuery = $request->query('q'); 
        $format = $request->query('format', 'csv');
        
        $query = $this->buildFilterQuery($inventory, $category, $sub, $searchQuery);
        
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "sdp_export_{$category}_{$timestamp}.{$format}";
        
        // Use Maatwebsite Excel
        $export = new \App\Exports\ItemsExport($query);
        
        if ($format === 'pdf') {
             return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::DOMPDF);
        } elseif ($format === 'xlsx') {
             return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
        } else {
             return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
        }
    }
    
    private function buildFilterQuery(InventoryService $inventory, $category, $sub, $searchQuery = null) {
        $query = Item::query();
        $today = now()->format('Y-m-d');
        
        if ($category !== 'search') {
            $query->where('is_sold', false);
        }

        if ($category == 'search' && $searchQuery) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('lot_number', 'like', "%$searchQuery%")
                  ->orWhere('product', 'like', "%$searchQuery%")
                  ->orWhere('location', 'like', "%$searchQuery%")
                  ->orWhere('internal_reference', 'like', "%$searchQuery%");
            });
        } elseif ($category == 'active_rentals') {
            // Complex logic: "Active Rental"
            // 1. All Rented In Customer (Start <= Today)
            // 2. In Stock Original Without Replace (Count=1, Start <= Today)
            // 3. In Service (Ext+Int+Ins) Original Without Replace (Count=1, Start <= Today)
            
            $query->where(function($q) use ($today) {
                // 1. Customer Location
                $q->where(function($sub) use ($today) {
                    $sub->where('location', Location::RENTAL_CUSTOMER)
                        ->where(function($d) use ($today) {
                             $d->whereNull('actual_start_rental')
                               ->orWhere('actual_start_rental', '<=', $today);
                        });
                });
                
                // 2. In Stock Active (Original, Count 1)
                $q->orWhere(function($sub) use ($today) {
                    $sub->where('in_stock', true)
                        ->whereColumn('lot_number', 'reserved_lot')
                        ->whereNotNull('rental_id')
                        ->where('rental_id_count', 1)
                        ->where(function($d) use ($today) {
                             $d->whereNull('actual_start_rental')
                               ->orWhere('actual_start_rental', '<=', $today);
                        });
                });
                
                // 3. Service/Insurance Active (Original, Count 1)
                $q->orWhere(function($sub) use ($today) {
                    $sub->where(function($l) {
                            $l->where('location', 'like', Location::SERVICE_EXTERNAL . '%')
                              ->orWhere('location', Location::SERVICE_INTERNAL)
                              ->orWhere('location', 'like', Location::INSURANCE . '%');
                        })
                        ->whereColumn('lot_number', 'reserved_lot')
                        ->whereNotNull('rental_id')
                        ->where('rental_id_count', 1)
                        ->where(function($d) use ($today) {
                             $d->whereNull('actual_start_rental')
                               ->orWhere('actual_start_rental', '<=', $today);
                        });
                });
            });
            
        } elseif ($category == 'rented_original_customer') {
             // Explicit filter for "Original in Customer" (SDP Owned)
             $inventory->scopeRented($query);
             $query->whereColumn('lot_number', 'reserved_lot')
                   ->whereNotNull('reserved_lot')
                   ->where('reserved_lot', '!=', '')
                   ->where('is_vendor_rent', false);
        
        } elseif ($category == 'overdue_rentals') {
            // Vehicles still at customer location with rental end date today or past
            $query->where('location', Location::RENTAL_CUSTOMER)
                  ->whereNotNull('actual_end_rental')
                  ->whereDate('actual_end_rental', '<=', $today);
        } elseif ($category == 'vendor_rent') {
            $query->where('is_vendor_rent', true);
        } elseif ($category == 'in_stock') {
             if ($sub) {
                 if ($sub == 'Operation') $query->where('location', 'like', '%Operation%'); // Keep loose match or use constant?
                 elseif ($sub == 'in-Transit') $query->where('location', 'like', '%Transit%');
                 elseif ($sub == 'Jakarta') $query->where(function($q) { $q->where('location', 'like', '%JKT%')->orWhere('location', 'like', '%Jakarta%'); });
                 elseif ($sub == 'Surabaya') $query->where(function($q) { $q->where('location', 'like', '%SBY%')->orWhere('location', 'like', '%SUB%')->orWhere('location', 'like', '%Surabaya%'); });
                 elseif ($sub == 'Semarang') $query->where(function($q) { $q->where('location', 'like', '%SMG%')->orWhere('location', 'like', '%Semarang%'); });
                 elseif ($sub == 'Bandung') $query->where(function($q) { $q->where('location', 'like', '%BDG%')->orWhere('location', 'like', '%Bandung%'); });
                 elseif ($sub == 'Cirebon') $query->where(function($q) { $q->where('location', 'like', '%CRB%')->orWhere('location', 'like', '%CBN%')->orWhere('location', 'like', '%Cirebon%'); });
                 elseif ($sub == 'Cilegon') $query->where(function($q) { $q->where('location', 'like', '%CLG%')->orWhere('location', 'like', '%Cilegon%'); });
                 else $query->where('location', $sub);
             }
             $inventory->scopeInStock($query);
        } elseif ($category == 'stock_pure') {
            $inventory->scopeInStock($query)->where(function($q) {
                $q->whereNull('rental_id')->orWhere('rental_id', '');
            });
        } elseif ($category == 'stock_original') {
            $inventory->scopeInStock($query)->whereNotNull('rental_id')->where('rental_id', '!=', '')->where('actual_start_rental', '<=', $today);
            if ($sub == 'with_replace') $query->where('rental_id_count', '>', 1);
            if ($sub == 'no_replace') $query->where('rental_id_count', '<=', 1);
        } elseif ($category == 'stock_reserve') {
             $inventory->scopeInStock($query)->whereNotNull('rental_id')->where('rental_id', '!=', '')->where('actual_start_rental', '>', $today);
        } elseif ($category == 'rented') {
             $inventory->scopeRented($query);
             if ($sub == 'Vendor Rent') $query->where('is_vendor_rent', true);
             elseif ($sub == 'Original in Customer') $query->whereColumn('lot_number', 'reserved_lot')->whereNotNull('reserved_lot')->where('reserved_lot', '!=', '')->where('is_vendor_rent', false);
             elseif ($sub == 'Replacement') $query->whereNotNull('rental_id')->where('rental_id', '!=', '')->whereColumn('lot_number', '!=', 'reserved_lot')->where('is_vendor_rent', false);
             elseif ($sub == 'Replacement - Service') $query->whereNotNull('rental_id')->where('rental_id', '!=', '')->whereColumn('lot_number', '!=', 'reserved_lot')->where('is_vendor_rent', false)->where('rental_id_count', '>', 1);
             elseif ($sub == 'Replacement - RBO') $query->whereNotNull('rental_id')->where('rental_id', '!=', '')->whereColumn('lot_number', '!=', 'reserved_lot')->where('is_vendor_rent', false)->where('rental_id_count', 1);
             elseif ($sub == 'Check Rent position') $query->where(function($q) { $q->whereNull('rental_id')->orWhere('rental_id', ''); })->where('is_vendor_rent', false);
        } elseif ($category == 'external_service' || $category == 'service_external') {
             $inventory->scopeExternalService($query);
              if ($sub) {
                if (str_contains($sub, 'Original') || $sub == 'original_no_replace') {
                     $query->whereColumn('lot_number', 'reserved_lot')->whereNotNull('rental_id')->where('rental_id', '!=', '');
                     if (str_contains($sub, 'with Replace')) $query->where('rental_id_count', '>', 1);
                     if (str_contains($sub, 'without Replace') || $sub == 'original_no_replace') $query->where('rental_id_count', 1);
                }
                if ($sub == 'Rented Replacement') $query->whereNotNull('rental_id')->where('rental_id', '!=', '')->whereColumn('lot_number', '!=', 'reserved_lot');
                if ($sub == 'Stock in Service') $query->where(function($q) { $q->whereNull('rental_id')->orWhere('rental_id', ''); });
              }
        } elseif ($category == 'internal_service' || $category == 'service_internal') {
             $inventory->scopeInternalService($query);
             if ($sub) {
                if (str_contains($sub, 'Original') || $sub == 'Original Rented without Replace') {
                    $query->whereColumn('lot_number', 'reserved_lot')->whereNotNull('rental_id')->where('rental_id', '!=', '');
                    if (str_contains($sub, 'with Replace')) $query->where('rental_id_count', '>', 1);
                    if (str_contains($sub, 'without Replace') || $sub == 'Original Rented without Replace') $query->where('rental_id_count', 1);
                }
                if ($sub == 'Rented Replacement') $query->whereNotNull('rental_id')->where('rental_id', '!=', '')->whereColumn('lot_number', '!=', 'reserved_lot');
                if ($sub == 'Stock in Internal Service') $query->where(function($q) { $q->whereNull('rental_id')->orWhere('rental_id', ''); });
             }
        } elseif ($category == 'insurance' || $category == 'service_insurance') {
             $inventory->scopeInsurance($query);
             if ($sub) {
                if (str_contains($sub, 'Original') || $sub == 'original_no_replace') {
                    $query->whereColumn('lot_number', 'reserved_lot')->whereNotNull('rental_id')->where('rental_id', '!=', '');
                    if (str_contains($sub, 'with Replace')) $query->where('rental_id_count', '>', 1);
                    if (str_contains($sub, 'without Replace') || $sub == 'original_no_replace') $query->where('rental_id_count', 1);
                }
                if ($sub == 'Rented Replacement') $query->whereNotNull('rental_id')->where('rental_id', '!=', '')->whereColumn('lot_number', '!=', 'reserved_lot'); 
                if ($sub == 'Stock in Insurance') $query->where(function($q) { $q->whereNull('rental_id')->orWhere('rental_id', ''); });
             }
        } elseif ($category == 'rental_type') {
             $query->where('is_active_rental', true);
             if ($sub) $query->where('rental_type', $sub);
             else $query->whereNotNull('rental_type')->where('rental_type', '!=', '');
        } elseif ($category == 'reserved_subscription') {
             $query->where('rental_type', 'Subscription')->where('actual_start_rental', '>', $today);
        } elseif ($category == 'inactive_subscription') {
             $query->where('rental_type', 'Subscription')->where('actual_end_rental', '<', $today);
        } elseif ($category == 'in_service') {
             $inventory->scopeInService($query);
        } elseif ($category == 'sdp_owned') {
             $inventory->scopeSdpOwned($query);
        } elseif ($category == 'uncategorized') {
             $query->where('in_stock', false)
                   ->where('location', '!=', Location::RENTAL_CUSTOMER)
                   ->where('location', 'not like', Location::SERVICE_EXTERNAL . '%')
                   ->where('location', '!=', Location::SERVICE_INTERNAL)
                   ->where('location', 'not like', Location::INSURANCE . '%');
        }
        
        return $query;
    }

    public function rentalPairs()
    {
        // Get all items not sold
        $items = Item::where('is_sold', false)->whereNotNull('rental_id')->where('rental_id', '!=', '')->get();
        // Group by rental_id
        $grouped = $items->groupBy('rental_id');
        
        $rentalPairs = [];
        foreach ($grouped as $rid => $group) {
            if ($group->count() > 1) {
                // Sort Main first
                $sorted = $group->sortBy(function($item) {
                     return $item->vehicle_role === 'Main' ? 0 : 1;
                });
                
                $main = $sorted->firstWhere('vehicle_role', 'Main');
                
                // Determine Category based on Main Unit Location
                $category = 'other';
                if ($main) {
                    $loc = $main->location;
                    if (stripos($loc, 'Service') !== false || stripos($loc, 'Insurance') !== false) {
                        $category = 'service';
                    } elseif ($main->in_stock) {
                        $category = 'stock';
                    } elseif ($loc === \App\Constants\Location::RENTAL_CUSTOMER) {
                        $category = 'customer';
                    }
                }

                $rentalPairs[$rid] = [
                    'rental_id' => $rid,
                    'vehicles' => $sorted->values(),
                    'main_vehicle' => $main,
                    'replacement_vehicles' => $sorted->where('vehicle_role', 'Replacement')->values(),
                    'category' => $category,
                ];
            }
        }
        
        $pairsCount = count($rentalPairs);
        
        // Count Stats
        $stats = [
            'total' => $pairsCount,
            'service' => collect($rentalPairs)->where('category', 'service')->count(),
            'stock' => collect($rentalPairs)->where('category', 'stock')->count(),
            'customer' => collect($rentalPairs)->where('category', 'customer')->count(),
            'other' => collect($rentalPairs)->where('category', 'other')->count(),
        ];
        
        // Metadata
        $latest = History::orderBy('snapshot_date', 'desc')->first();
        $metadata = ['imported_at' => $latest ? $latest->updated_at->format('Y-m-d H:i:s') : null];

        return view('rental_pairs', compact('rentalPairs', 'pairsCount', 'metadata', 'stats'));
    }
    public function totalStock()
    {
        // Get all unique values for filters
        $locations = Item::select('location')->distinct()->orderBy('location')->pluck('location');
        $products = Item::select('product')->distinct()->orderBy('product')->pluck('product');
        $types = Item::whereNotNull('rental_type')->where('rental_type', '!=', '')->select('rental_type')->distinct()->pluck('rental_type');
        
        return view('total_stock', compact('locations', 'products', 'types'));
    }

    public function filterTotalStock(Request $request, InventoryService $inventory)
    {
        $query = Item::query()->where('is_sold', false)->where('on_hand_quantity', '>', 0);
        $filters = $request->input('filters', []);

        if (!empty($filters)) {
            $inventory->applyAdvancedFilters($query, $filters);
        }

        // Apply sorting
        $sortCol = $request->input('sortCol', 'id');
        $sortAsc = $request->input('sortAsc', true);
        $direction = $sortAsc ? 'asc' : 'desc';

        if ($sortCol === 'status') {
             $query->orderBy('rental_id', $direction);
        } else {
             $query->orderBy($sortCol, $direction);
        }

        // Pagination
        $perPage = $request->input('perPage', 50);
        $items = $query->paginate($perPage);

        return response()->json($items);
    }

    public function exportTotalStock(Request $request)
    {
        $filters = $request->input('filters', []);
        
        // When sent via form.submit(), JSON might be a string
        if (is_string($filters)) {
            $filters = json_decode($filters, true) ?? [];
        }
        
        // Handle sorting from request
        $sortCol = $request->input('sortCol', 'lot_number');
        $sortAsc = filter_var($request->input('sortAsc', true), FILTER_VALIDATE_BOOLEAN);
        
        $filename = 'total_stock_' . now()->format('Ymd_His') . '.xlsx';
        
        return Excel::download(
            new \App\Exports\TotalStockExport($filters, $sortCol, $sortAsc), 
            $filename
        );
    }

    /**
     * Fetch repair history for a lot number from Odoo (live API call).
     */
    public function repairHistory(string $lotNumber)
    {
        try {
            $odoo = new \App\Services\OdooService();
            $result = $odoo->fetchRepairHistory($lotNumber);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch repair history: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }
}
