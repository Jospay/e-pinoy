<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Revenue;
use App\Models\User;
use App\Models\PercentageType;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf; // Use Barryvdh\DomPDF\Facade\Pdf instead of PDF
use App\Exports\SimpleArrayExport;

class DetailsDriverController extends Controller
{
    // --- Core Data Fetching Logic (Updated) ---

    /**
     * Fetches detailed revenue transactions based on validated request data,
     * restricted to the 'branch' context.
     */
    private function fetchRevenueDetails(array $validated): \Illuminate\Support\Collection
    {
        // 1. Get the date range from the passed string
        $paymentDate = isset($validated['payment_date']) ? $validated['payment_date'] : Carbon::now()->toDateString();
        $period = isset($validated['period']) ? $validated['period'] : 'daily';

        // Ensure the payment_date is URL-decoded before parsing (Crucial for export)
        $cleanPaymentDate = urldecode($paymentDate);
        [$startDate, $endDate] = $this->parseDateRange($cleanPaymentDate, $period);

        // 2. Build the Revenue Query
        $query = Revenue::query()
            // Removed 'franchise' from with() since it's no longer needed
            ->with(['revenueBreakdowns.percentageType', 'driver', 'branch'])
            ->where('driver_id', $validated['driver_id'])
            ->whereHas('status', fn ($q) => $q->where('name', 'paid'))
            // Filter using the calculated start and end date
            ->whereBetween('payment_date', [$startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->where('service_type', 'Trips')
            ->orderBy('payment_date', 'asc');

        // 3. Apply Contextual Filters (Only Branch)
        // Check for 'branch' filter. We default to 'branch' tab implicitly.
        if (!empty($validated['branch']) && $validated['branch'] !== 'all') {
            $query->where('branch_id', $validated['branch']);
        }

        // Removed franchise filter logic completely.

        return $query->get();
    }

    // --- Show Method (Updated validation and removed franchise references) ---

    /**
     * Shows the detailed transactions for a specific driver and period.
     */
    public function show(Request $request): Response
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'string', 'exists:users,id'],
            'payment_date' => ['required', 'string'],
            'period' => ['required', 'string', 'in:daily,weekly,monthly'],
            // Removed 'tab' and 'franchise' from validation
            'branch' => ['sometimes', 'nullable', 'string'],
        ]);

        // Use the new reusable fetcher
        $details = $this->fetchRevenueDetails($validated);

        // Ensure the driver object is found
        $driver = User::find($validated['driver_id'], ['id', 'username']);
        if (!$driver) {
            return Inertia::location(route('404'));
        }

        $breakdownTypes = PercentageType::pluck('name')
            ->map(fn($name) => ucwords(str_replace('_', ' ', $name)))
            ->toArray();

        // Ensure the filters array passed back to Inertia only contains relevant keys
        $filters = [
            'driver_id' => $validated['driver_id'],
            'payment_date' => $validated['payment_date'],
            'period' => $validated['period'],
            'branch' => $validated['branch'] ?? null,
            'tab' => 'branch' // Set implicit tab for front-end consistency if needed
        ];


        return Inertia::render('manager/driver-report/Details', [
            'driver' => $driver->only(['id', 'username']),
            'periodLabel' => $validated['payment_date'],
            'details' => $details,
            'breakdownTypes' => $breakdownTypes,
            'filters' => $filters,
        ]);
    }

    // --- Date Parsing Helper (Unchanged, no franchise/branch logic here) ---

    /**
     * Parses the formatted payment_date string into two Carbon instances (start date and end date).
     */
    private function parseDateRange(string $dateString, string $period): array
    {
        // ... (This function remains entirely unchanged as date parsing logic is universal)
        // Paste the original parseDateRange function here.

        // Default to today if parsing fails as a safety net
        $now = Carbon::now();
        $startDate = clone $now;
        $endDate = clone $now;

        try {
            switch ($period) {
                case 'daily':
                    // Expected formats: "November 20, 2025" OR "2025-11-20"
                    $date = Carbon::createFromFormat('F j, Y', $dateString) ?: Carbon::parse($dateString);
                    $startDate = $date->copy()->startOfDay();
                    $endDate = $date->copy()->endOfDay();
                    break;

                case 'monthly':
                    // Expected formats: "November 2025" OR "Nov 2025"
                    if (str_contains($dateString, ' ')) {
                        $date = Carbon::createFromFormat('F Y', $dateString) ?: Carbon::parse($dateString);
                    } else {
                        $date = Carbon::parse($dateString);
                    }
                    $startDate = $date->copy()->startOfMonth();
                    $endDate = $date->copy()->endOfMonth();
                    break;

                case 'weekly':
                    // Two formats to handle:
                    // 1. Single-month: "Nov 19 - 20, 2025"
                    // 2. Cross-month: "Oct 28 - Nov 2, 2025" (The second month is explicitly included)

                    if (str_contains($dateString, ' - ')) {

                        // Regex 1: Checks for a cross-month or full format like "M D - M D, Y"
                        // It separates the START part (M D) and END part (M D) with the year.
                        if (preg_match('/^(.+)\s-\s(.+),\s(\d{4})$/', $dateString, $matches) && str_contains($matches[2], ' ')) {
                            // This matches Cross-Month: "Oct 28 - Nov 2, 2025"
                            $startStrSegment = trim($matches[1]);
                            $endStrSegment = trim($matches[2]);
                            $year = trim($matches[3]);

                            // Construct full, unambiguous date strings for parsing
                            $fullStartStr = $startStrSegment . ', ' . $year; // e.g., "Oct 28, 2025"
                            $fullEndDateStr = $endStrSegment . ', ' . $year; // e.g., "Nov 2, 2025"

                            $start = Carbon::parse($fullStartStr);
                            $end = Carbon::parse($fullEndDateStr);

                            $startDate = $start->copy()->startOfDay();
                            $endDate = $end->copy()->endOfDay();

                        }
                        // Regex 2: Checks for a single-month format like "M D - D, Y"
                        elseif (preg_match('/^(.+)\s-\s(.+),\s(\d{4})$/', $dateString, $matches)) {
                            // This matches Single-Month: "Nov 19 - 20, 2025"
                            $startStrSegment = trim($matches[1]); // e.g., "Nov 19"
                            $endStrSegment = trim($matches[2]); // e.g., "20" (Day only)
                            $year = trim($matches[3]); // e.g., "2025"

                            // Extract the month from the start string segment
                            $month = explode(' ', $startStrSegment)[0];

                            // Construct the full strings
                            $fullStartStr = $startStrSegment . ', ' . $year; // e.g., "Nov 19, 2025"
                            $fullEndDateStr = $month . ' ' . $endStrSegment . ', ' . $year; // e.g., "Nov 20, 2025"

                            $start = Carbon::parse($fullStartStr);
                            $end = Carbon::parse($fullEndDateStr);

                            $startDate = $start->copy()->startOfDay();
                            $endDate = $end->copy()->endOfDay();
                        }
                    } else {
                        // Fallback (if the format is just one date)
                        $date = Carbon::parse($dateString);
                        $startDate = $date->copy()->startOfWeek();
                        $endDate = $date->copy()->endOfWeek();
                    }
                    break;
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error("Failed to parse date string: {$dateString} with period {$period}. Error: " . $e->getMessage());
        }

        return [$startDate, $endDate];
    }

    // --- Export Method (Updated validation and export title) ---

    /**
     * Exports the detailed transactions for a specific driver and period.
     */
    public function exportDetails(Request $request)
    {
        // 1. Validate inputs
        $validated = $request->validate([
            'driver_id' => ['required', 'string', 'exists:users,id'],
            'payment_date' => ['required', 'string'],
            'period' => ['required', 'string', 'in:daily,weekly,monthly'],
            // Removed 'tab' and 'franchise' from validation
            'branch' => ['sometimes', 'nullable', 'string'],
            'export_type' => ['required', 'string', Rule::in(['pdf', 'excel', 'csv'])],
        ]);

        // CRUCIAL FIX: Decode the payment_date for use in the title and fetching logic
        $cleanPaymentDate = urldecode($validated['payment_date']);
        $validated['payment_date'] = $cleanPaymentDate; // Update validated array for fetching

        $driverId = $validated['driver_id'];
        $exportType = $validated['export_type'];

        // --- 2. Data Fetching (Using the shared logic) ---
        $details = $this->fetchRevenueDetails($validated);

        $driver = User::find($driverId, ['id', 'username']);
        if (!$driver) {
            return response()->json(['message' => 'Driver not found.'], 404);
        }

        $breakdownTypesDb = PercentageType::pluck('name')->toArray();

        // --- 3. Prepare Data Rows for Export (including totals) ---
        $exportRows = collect([]);
        $grandTotals = [
            'amount' => 0.0,
            'breakdowns' => array_fill_keys($breakdownTypesDb, 0.0),
            'driver_earning' => 0.0,
        ];

        foreach ($details as $row) {
            $totalBreakdowns = 0;
            $rowArray = [
                'invoice_no' => $row->invoice_no,
                'payment_date' => Carbon::parse($row->payment_date)->format('M d, Y h:i A'),
                'total_trip_amount' => (float) $row->amount,
            ];

            // Dynamically add breakdown amounts
            foreach ($breakdownTypesDb as $dbName) {
                $breakdown = $row->revenueBreakdowns->firstWhere('percentageType.name', $dbName);
                $amount = (float) ($breakdown->total_earning ?? 0);

                $rowArray[$dbName] = $amount;
                $totalBreakdowns += $amount;
                $grandTotals['breakdowns'][$dbName] += $amount;
            }

            // Calculate driver earning
            $driverEarning = max(0, $rowArray['total_trip_amount'] - $totalBreakdowns);
            $rowArray['driver_earning'] = $driverEarning;

            // Update grand totals
            $grandTotals['amount'] += $rowArray['total_trip_amount'];
            $grandTotals['driver_earning'] += $driverEarning;

            $exportRows->push($rowArray);
        }

        // Add Grand Total Row
        $grandTotalRow = [
            'invoice_no' => 'GRAND TOTAL',
            'payment_date' => '',
            'total_trip_amount' => $grandTotals['amount'],
        ];
        foreach ($breakdownTypesDb as $dbName) {
            $grandTotalRow[$dbName] = $grandTotals['breakdowns'][$dbName];
        }
        $grandTotalRow['driver_earning'] = $grandTotals['driver_earning'];
        $exportRows->push($grandTotalRow);


        // --- 4. Define Headings and Data Keys ---
        $breakdownHeadings = collect($breakdownTypesDb)
            ->map(fn($name) => ucwords(str_replace('_', ' ', $name)))
            ->toArray();

        $headings = array_merge(
            ['Invoice No.', 'Date/Time', 'Total Trip Amount'],
            $breakdownHeadings,
            ['Driver Earning']
        );

        $dataKeys = array_merge(
            ['invoice_no', 'payment_date', 'total_trip_amount'],
            $breakdownTypesDb,
            ['driver_earning']
        );

        // --- 5. Dispatch Download ---
        $fileName = 'driver_details_' . $driver->username . '_' . date('Ymd_His');

        // Use the CLEAN date string for the title
        $title = "Transaction Details for {$driver->username} ({$validated['period']}: {$validated['payment_date']})";

        // Add branch information to the title if filtered
        if (!empty($validated['branch']) && $validated['branch'] !== 'all') {
             // Fetch branch name to include in title
             $branch = \App\Models\Branch::find($validated['branch']);
             if ($branch) {
                $title .= " - Branch: {$branch->name}";
             }
        }

        if ($exportType === 'pdf') {
            return Pdf::loadView('exports.driver_details', [
                'rows' => $exportRows,
                'title' => $title,
                'headings' => $headings,
                'dataKeys' => $dataKeys,
            ])
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->download($fileName.'.pdf');
        }

        if (in_array($exportType, ['excel', 'csv'])) {
            $excelRows = $exportRows->map(function ($row) use ($breakdownTypesDb) {
                foreach ($row as $key => $value) {
                    // Check if it's a numeric column to format
                    if (in_array($key, ['total_trip_amount', 'driver_earning']) || in_array($key, $breakdownTypesDb)) {
                        $row[$key] = number_format((float) $value, 2, '.', '');
                    }
                }
                return array_values($row);
            });

            $export = new SimpleArrayExport(
                $excelRows,
                $headings,
                $title
            );

            $fileExtension = ($exportType === 'excel' ? 'xlsx' : 'csv');
            return Excel::download($export, $fileName . '.' . $fileExtension);
        }
    }
}
