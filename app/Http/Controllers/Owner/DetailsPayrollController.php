<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Revenue;
use App\Models\Route;
use App\Models\User;
use App\Models\PercentageType;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use App\Exports\SimpleArrayExport;

class DetailsPayrollController extends Controller
{

    // --- Core Data Fetching Logic (Unchanged) ---
    private function fetchRevenueDetails(array $validated): \Illuminate\Support\Collection
    {
        // 1. Get the date range... (same logic)
        $paymentDate = isset($validated['payment_date']) ? $validated['payment_date'] : Carbon::now()->toDateString();
        $period = isset($validated['period']) ? $validated['period'] : 'daily';

        // Ensure the payment_date is URL-decoded before parsing (Crucial for export)
        $cleanPaymentDate = urldecode($paymentDate);
        [$startDate, $endDate] = $this->parseDateRange($cleanPaymentDate, $period);

        // 2. Build the Revenue Query
        $query = Revenue::query()
            ->with([
                'revenueBreakdowns.percentageType',
                'driver',
                'franchise',
                // Load the 'route' relation, selecting only the necessary columns
                'route' => function ($q) {
                    $q->select([
                        'revenue_id', // MUST include the foreign key for the relation to work
                        'start_trip', 'end_trip',
                        'start_lat', 'start_lng',
                        'end_lat', 'end_lng',
                        'distance_km', 'average_speed_kmh', 'max_speed_kmh'
                    ]);
                }
            ])
            ->where('driver_id', $validated['driver_id'])
            ->whereHas('status', fn ($q) => $q->where('name', 'paid'))
            ->whereBetween('payment_date', [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->where('service_type', 'Trips')
            ->orderBy('payment_date', 'asc');

        // 3. Apply Contextual Filters... (same logic)
        if (isset($validated['tab']) && $validated['tab'] === 'franchise' && !empty($validated['franchise']) && $validated['franchise'] !== 'all') {
            $query->where('franchise_id', $validated['franchise']);
        }

        $revenues = $query->get();

        // --- TEMPORARY DEBUGGING LOG INSERTED HERE ---
        Log::info('Payroll Details: Route Data Check (First 5 records):', $revenues->take(5)->map(function($r) {
            return [
                'id' => $r->id,
                'invoice_no' => $r->invoice_no,
                'route_present' => !is_null($r->route),
                'route_data_snippet' => $r->route ? "Trip: {$r->route->start_trip} to {$r->route->end_trip}" : 'NULL'
            ];
        })->toArray());
        // --- END DEBUGGING LOG ---

        // *** IMPORTANT: Map the 'route' relation to 'route_data' for the frontend interface ***
        return $revenues->map(function ($revenue) {
            $revenue->route_data = $revenue->route;
            unset($revenue->route);
            return $revenue;
        });
    }

    // --- NEW: Route Fetching Method (Unchanged) ---
    /**
     * Fetches route details for a specific revenue ID and driver ID.
     */
    public function fetchRouteDetails(Request $request)
    {
        // 1. Validate inputs
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'revenue_id' => ['required', 'integer', 'exists:revenues,id'],
        ]);

        // 2. Query the routes table
        $route = Route::where('driver_id', $validated['driver_id'])
            ->where('revenue_id', $validated['revenue_id'])
            // Select only the necessary columns
            ->select([
                'start_trip', 'end_trip',
                'start_lat', 'start_lng',
                'end_lat', 'end_lng',
                'distance_km', 'average_speed_kmh', 'max_speed_kmh'
            ])
            ->first();

        if (!$route) {
            return response()->json(['route' => null], 200);
        }

        // 3. Return the route data as a JSON response
        return response()->json(['route' => $route]);
    }

    // --- Show Method (Unchanged) ---
    public function show(Request $request): Response
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'string', 'exists:users,id'],
            'payment_date' => ['required', 'string'],
            'period' => ['required', 'string', 'in:daily,weekly,monthly'],
            'tab' => ['sometimes', 'string', 'in:franchise'],
            'franchise' => ['sometimes', 'nullable', 'string'],
        ]);

        $details = $this->fetchRevenueDetails($validated);

        $driver = User::find($validated['driver_id'], ['id', 'username']);
        if (!$driver) {
            return Inertia::location(route('404'));
        }

        $breakdownTypes = PercentageType::pluck('name')
            ->map(fn($name) => ucwords(str_replace('_', ' ', $name)))
            ->toArray();

        return Inertia::render('owner/payroll/Details', [
            'driver' => $driver->only(['id', 'username']),
            'periodLabel' => $validated['payment_date'],
            'details' => $details,
            'breakdownTypes' => $breakdownTypes,
            'filters' => $validated,
        ]);
    }

    // --- Date Parsing Helper (Unchanged) ---
    private function parseDateRange(string $dateString, string $period): array
    {
        $now = Carbon::now();
        $startDate = clone $now;
        $endDate = clone $now;

        try {
            switch ($period) {
                case 'daily':
                    $date = Carbon::createFromFormat('F j, Y', $dateString) ?: Carbon::parse($dateString);
                    $startDate = $date->copy()->startOfDay();
                    $endDate = $date->copy()->endOfDay();
                    break;

                case 'monthly':
                    if (str_contains($dateString, ' ')) {
                        $date = Carbon::createFromFormat('F Y', $dateString) ?: Carbon::parse($dateString);
                    } else {
                        $date = Carbon::parse($dateString);
                    }
                    $startDate = $date->copy()->startOfMonth();
                    $endDate = $date->copy()->endOfMonth();
                    break;

                case 'weekly':
                    if (str_contains($dateString, ' - ')) {
                        if (preg_match('/^(.+)\s-\s(.+),\s(\d{4})$/', $dateString, $matches) && str_contains($matches[2], ' ')) {
                            $startStrSegment = trim($matches[1]);
                            $endStrSegment = trim($matches[2]);
                            $year = trim($matches[3]);
                            $fullStartStr = $startStrSegment . ', ' . $year;
                            $fullEndDateStr = $endStrSegment . ', ' . $year;
                            $start = Carbon::parse($fullStartStr);
                            $end = Carbon::parse($fullEndDateStr);
                            $startDate = $start->copy()->startOfDay();
                            $endDate = $end->copy()->endOfDay();

                        } elseif (preg_match('/^(.+)\s-\s(.+),\s(\d{4})$/', $dateString, $matches)) {
                            $startStrSegment = trim($matches[1]);
                            $endStrSegment = trim($matches[2]);
                            $year = trim($matches[3]);
                            $month = explode(' ', $startStrSegment)[0];
                            $fullStartStr = $startStrSegment . ', ' . $year;
                            $fullEndDateStr = $month . ' ' . $endStrSegment . ', ' . $year;
                            $start = Carbon::parse($fullStartStr);
                            $end = Carbon::parse($fullEndDateStr);
                            $startDate = $start->copy()->startOfDay();
                            $endDate = $end->copy()->endOfDay();
                        }
                    } else {
                        $date = Carbon::parse($dateString);
                        $startDate = $date->copy()->startOfWeek();
                        $endDate = $date->copy()->endOfWeek();
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Failed to parse date string: {$dateString} with period {$period}. Error: " . $e->getMessage());
        }

        return [$startDate, $endDate];
    }
    // ---------------------------------------------------------------------

    // --- Export Method (Updated for formal PDF with custom ID format) ---
    public function exportDetails(Request $request)
    {
        // Fetch the authenticated user's primary franchise name for the ID suffix
        $userFranchise = auth()->user()->ownerDetails?->franchises()->first();
        $authFranchiseName = $userFranchise?->name;

        // 1. Validate inputs (Unchanged)
        $validated = $request->validate([
            'driver_id' => ['required', 'string', 'exists:users,id'],
            'payment_date' => ['required', 'string'],
            'period' => ['required', 'string', 'in:daily,weekly,monthly'],
            'tab' => ['sometimes', 'string', 'in:franchise'],
            'franchise' => ['sometimes', 'nullable', 'string'],
            'export_type' => ['required', 'string', Rule::in(['pdf', 'excel', 'csv'])],
        ]);

        $cleanPaymentDate = urldecode($validated['payment_date']);
        $validated['payment_date'] = $cleanPaymentDate;

        $driverId = $validated['driver_id'];
        $exportType = $validated['export_type'];

        // --- 2. Data Fetching ---
        $details = $this->fetchRevenueDetails($validated);

        $driver = User::find($driverId, ['id', 'username']);
        if (!$driver) {
            return response()->json(['message' => 'Driver not found.'], 404);
        }

        // Determine the franchise name to use for the ID suffix
        $idFranchiseName = null;

        // If filtering by franchise, use the filtered franchise name
        if (isset($validated['tab']) && $validated['tab'] === 'franchise' && !empty($validated['franchise']) && $validated['franchise'] !== 'all') {
             // Try to get the name from the revenue details, otherwise fall back to the authenticated user's primary franchise
             $idFranchiseName = $details->first()?->franchise->name ?? $authFranchiseName;
        // If not explicitly filtering by a franchise, use the authenticated user's primary franchise name
        } else {
             $idFranchiseName = $authFranchiseName;
        }

        // Prepare the formatted ID string (e.g., 34_franchise_name)
        $formattedDriverId = $driver->id;
        if ($idFranchiseName) {
            // Remove spaces from the franchise name and replace with underscores
            $cleanedFranchiseName = preg_replace('/\s+/', '_', $idFranchiseName);
            // Ensure no duplicate underscores if the name already contains them (optional, but clean)
            $cleanedFranchiseName = trim($cleanedFranchiseName, '_');
            $formattedDriverId .= '_' . $cleanedFranchiseName;
        }

        $breakdownTypesDb = PercentageType::pluck('name')->toArray();

        // --- 3. Prepare Data Rows for Export (including totals) ---
        $exportRows = collect([]);
        $grandTotals = [
            'amount' => 0.0,
            'total_deduction' => 0.0,
            'driver_earning' => 0.0,
        ];

        foreach ($details as $row) {
            $totalDeduction = 0;
            $rowArray = [
                'invoice_no' => $row->invoice_no,
                'payment_date' => Carbon::parse($row->payment_date)->format('M d, Y h:i A'),
                'total_trip_amount' => (float) $row->amount,
            ];

            // Calculate the total deduction by summing all breakdown amounts for this row
            foreach ($breakdownTypesDb as $dbName) {
                $breakdown = $row->revenueBreakdowns->firstWhere('percentageType.name', $dbName);
                $amount = (float) ($breakdown->total_earning ?? 0);
                $totalDeduction += $amount;
            }

            $rowArray['total_deduction'] = $totalDeduction;
            $driverEarning = max(0, $rowArray['total_trip_amount'] - $totalDeduction);
            $rowArray['driver_earning'] = $driverEarning;

            $grandTotals['amount'] += $rowArray['total_trip_amount'];
            $grandTotals['total_deduction'] += $totalDeduction;
            $grandTotals['driver_earning'] += $driverEarning;

            $exportRows->push($rowArray);
        }

        // Add Grand Total Row
        $grandTotalRow = [
            'invoice_no' => 'GRAND TOTAL',
            'payment_date' => '',
            'total_trip_amount' => $grandTotals['amount'],
        ];
        $grandTotalRow['total_deduction'] = $grandTotals['total_deduction'];
        $grandTotalRow['driver_earning'] = $grandTotals['driver_earning'];
        $exportRows->push($grandTotalRow);

        // --- 4. Define Headings and Data Keys (Simplified for the table) ---
        $headings = [
            'Invoice No.',
            'Date/Time',
            'Total Trip Amount',
            'Deduction',
            'Driver Earning'
        ];

        $dataKeys = [
            'invoice_no',
            'payment_date',
            'total_trip_amount',
            'total_deduction',
            'driver_earning'
        ];

        // --- 5. Dispatch Download (Updated PDF data) ---
        $fileName = 'driver_payroll_' . $driver->username . '_' . date('Ymd_His');
        $title = "DRIVER TRANSACTION REPORT";

        if ($exportType === 'pdf') {
            return PDF::loadView('exports.driver_payroll', [
                'rows' => $exportRows,
                'title' => $title,
                'headings' => $headings,
                'dataKeys' => $dataKeys,
                // NEW: Formal payroll data
                'payrollData' => [
                    'driver_name' => $driver->username,
                    'driver_id_display' => $formattedDriverId,
                    'period' => ucwords($validated['period']) . ' Period',
                    'period_label' => $validated['payment_date'],
                    'grand_totals' => $grandTotals,
                ],
            ])
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->download($fileName.'.pdf');
        }

        if (in_array($exportType, ['excel', 'csv'])) {
            // (Excel/CSV logic remains the same)
            $exportDataKeys = ['total_trip_amount', 'total_deduction', 'driver_earning'];

            $excelRows = $exportRows->map(function ($row) use ($exportDataKeys, $dataKeys) {
                $formattedRow = [];
                foreach ($dataKeys as $key) {
                    $value = $row[$key] ?? '';
                    if (in_array($key, $exportDataKeys)) {
                        $formattedRow[] = number_format((float) $value, 2, '.', '');
                    } else {
                        $formattedRow[] = $value;
                    }
                }
                return $formattedRow;
            });

            $titleForExcel = "Transaction Details for {$driver->username} ({$validated['period']}: {$validated['payment_date']})";
            $export = new SimpleArrayExport(
                $excelRows,
                $headings,
                $titleForExcel
            );

            $fileExtension = ($exportType === 'excel' ? 'xlsx' : 'csv');
            return Excel::download($export, $fileName . '.' . $fileExtension);
        }
    }
}
