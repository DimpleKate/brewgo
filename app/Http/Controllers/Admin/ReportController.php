<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const EXCLUDED_STATUSES = [
        OrderStatus::Cancelled->value,
        OrderStatus::Rejected->value,
    ];

    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            ...$this->reportData($from, $to),
        ]);
    }

    public function export(Request $request, string $format, PdfService $pdf): Response
    {
        abort_unless(in_array($format, ['csv', 'excel', 'pdf'], true), 404);

        [$from, $to] = $this->range($request);
        $data = $this->reportData($from, $to);

        $rows = [
            ['Sales report', $from->format('M d, Y').' to '.$to->format('M d, Y')],
            [],
            ['Orders', $data['summary']['orders']],
            ['Gross sales', number_format($data['summary']['gross'], 2)],
            ['Collected', number_format($data['summary']['collected'], 2)],
            ['Discounts', number_format($data['summary']['discounts'], 2)],
            ['Average order value', number_format($data['summary']['average'], 2)],
            [],
            ['Daily sales'],
            ['Date', 'Orders', 'Collected'],
        ];

        foreach ($data['daily'] as $day) {
            $rows[] = [$day->day, (int) $day->orders, number_format((float) $day->revenue, 2)];
        }

        $rows[] = [];
        $rows[] = ['Top products'];
        $rows[] = ['Product', 'Quantity', 'Revenue'];

        foreach ($data['topProducts'] as $product) {
            $rows[] = [$product->product_name, (int) $product->quantity, number_format((float) $product->revenue, 2)];
        }

        $filename = 'sales-report-'.$from->format('Ymd').'-'.$to->format('Ymd');

        return match ($format) {
            'csv' => $this->csvResponse($rows, $filename.'.csv'),
            'excel' => $this->excelResponse($rows, $filename.'.xls'),
            'pdf' => $this->pdfResponse($rows, $filename.'.pdf', $pdf),
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : now()->startOfMonth();

        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }

    /**
     * @return array{
     *     summary: array{orders:int, gross:float, collected:float, discounts:float, average:float},
     *     daily: Collection<int, object>,
     *     topProducts: Collection<int, object>,
     *     paymentMethods: Collection<int, object>,
     *     services: Collection<int, object>
     * }
     */
    private function reportData(Carbon $from, Carbon $to): array
    {
        $base = fn () => Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', self::EXCLUDED_STATUSES);

        $orders = $base()->count();
        $gross = (float) $base()->sum('total');
        $collected = (float) $base()->sum('amount_paid');
        $discounts = (float) $base()->sum('discount');

        $daily = $base()
            ->where('amount_paid', '>', 0)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as orders, SUM(amount_paid) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $topProducts = OrderItem::query()
            ->whereHas('order', fn ($query) => $query
                ->whereBetween('created_at', [$from, $to])
                ->whereNotIn('status', self::EXCLUDED_STATUSES))
            ->selectRaw('product_name, SUM(quantity) as quantity, SUM(line_total) as revenue')
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $paymentMethods = $base()
            ->where('amount_paid', '>', 0)
            ->selectRaw('payment_method, COUNT(*) as orders, SUM(amount_paid) as revenue')
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get();

        $services = $base()
            ->where('amount_paid', '>', 0)
            ->selectRaw('service_type, COUNT(*) as orders, SUM(amount_paid) as revenue')
            ->groupBy('service_type')
            ->orderByDesc('revenue')
            ->get();

        return [
            'summary' => [
                'orders' => $orders,
                'gross' => round($gross, 2),
                'collected' => round($collected, 2),
                'discounts' => round($discounts, 2),
                'average' => $orders > 0 ? round($gross / $orders, 2) : 0.0,
            ],
            'daily' => $daily,
            'topProducts' => $topProducts,
            'paymentMethods' => $paymentMethods,
            'services' => $services,
        ];
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function csvResponse(array $rows, string $filename): Response
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Excel-compatible SpreadsheetML workbook without adding a package dependency.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function excelResponse(array $rows, string $filename): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>'."\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Worksheet ss:Name="Sales report"><Table>';

        foreach ($rows as $row) {
            $xml .= '<Row>';

            foreach ($row as $cell) {
                $value = htmlspecialchars((string) $cell, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $xml .= '<Cell><Data ss:Type="String">'.$value.'</Data></Cell>';
            }

            $xml .= '</Row>';
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function pdfResponse(array $rows, string $filename, PdfService $pdf): Response
    {
        $lines = [];

        foreach ($rows as $row) {
            $text = implode('   |   ', array_map(fn ($cell) => (string) $cell, $row));
            $lines[] = ['text' => $text === '' ? ' ' : $text, 'size' => 9];
        }

        return response($pdf->document($lines, 'Sales report'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
