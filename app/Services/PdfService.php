<?php

namespace App\Services;

use App\Models\Receipt;
use App\Support\Branding;

class PdfService
{
    /**
     * Build a small, dependency-free PDF document from plain text lines.
     *
     * @param  array<int, array{text:string, size?:int, bold?:bool, gap?:int, rule?:bool}>  $lines
     */
    public function document(array $lines, string $title = 'Document'): string
    {
        $pages = [];
        $content = '';
        $y = 800;

        foreach ($lines as $line) {
            $gap = (int) ($line['gap'] ?? 0);
            $size = (int) ($line['size'] ?? 10);
            $bold = (bool) ($line['bold'] ?? false);

            if (! empty($line['rule'])) {
                $content .= sprintf("0.75 w 0.2 0.2 0.2 RG 50 %.2f m 545 %.2f l S\n", $y, $y);
                $y -= 12;
            }

            if ($y < 60) {
                $pages[] = $content;
                $content = '';
                $y = 800;
            }

            $text = $this->escape($line['text'] ?? '');
            $font = $bold ? 'F2' : 'F1';
            $content .= "BT /{$font} {$size} Tf 1 0 0 1 50 {$y} Tm ({$text}) Tj ET\n";
            $y -= ($size + 5 + $gap);
        }

        $pages[] = $content;

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [] /Count 0 >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

        $kids = [];

        foreach ($pages as $page) {
            $stream = "0.95 0.95 0.95 rg 0 0 595 842 re f\n".$page;
            $contentId = count($objects) + 2;
            $pageId = count($objects) + 1;

            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentId.' 0 R >>';
            $objects[] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";
            $kids[] = $pageId.' 0 R';
        }

        $objects[1] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($kids).' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R /Info << /Title ({$this->escape($title)}) /Producer (Brew & Go) >> >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    public function receipt(Receipt $receipt): string
    {
        $receipt->loadMissing(['order.items', 'order.user', 'issuer', 'payment']);
        $order = $receipt->order;
        $snapshot = $receipt->snapshot ?? [];
        $brand = Branding::name();

        $lines = [
            ['text' => $brand, 'size' => 20, 'bold' => true],
            ['text' => 'Payment receipt', 'size' => 12, 'gap' => 6],
            ['text' => 'Receipt: '.$receipt->receipt_number, 'bold' => true],
            ['text' => 'Order: '.$order->order_number],
            ['text' => 'Issued: '.$receipt->issued_at?->timezone('Asia/Manila')->format('M d, Y h:i A')],
            ['text' => 'Service: '.$order->serviceLabel()],
            ['text' => 'Payment: '.$receipt->methodLabel().' ('.ucfirst((string) ($snapshot['payment_type'] ?? 'payment')).')'],
            ['text' => 'Reference: '.($snapshot['reference'] ?? 'N/A')],
            ['text' => 'Customer: '.($order->pickup_name ?: $order->user?->name ?: 'Guest')],
            ['rule' => true, 'text' => ''],
        ];

        foreach (($snapshot['items'] ?? []) as $item) {
            $options = collect($item['options'] ?? [])->pluck('name')->implode(', ');
            $addons = collect($item['addons'] ?? [])->pluck('name')->implode(', ');
            $details = trim($options.($addons !== '' ? ', '.$addons : ''));

            $lines[] = [
                'text' => (int) ($item['quantity'] ?? 0).' x '.($item['product_name'] ?? 'Item').'  -  PHP '.number_format((float) ($item['line_total'] ?? 0), 2),
            ];

            if ($details !== '') {
                $lines[] = ['text' => '   '.$details, 'size' => 8];
            }

            if (! empty($item['note'])) {
                $lines[] = ['text' => '   Note: '.$item['note'], 'size' => 8];
            }
        }

        $lines[] = ['rule' => true, 'text' => ''];
        $lines[] = ['text' => 'Subtotal: PHP '.number_format((float) ($snapshot['subtotal'] ?? $order->subtotal), 2)];
        $lines[] = ['text' => 'Discount: PHP '.number_format((float) ($snapshot['discount'] ?? $order->discount), 2)];
        $lines[] = ['text' => 'Total: PHP '.number_format((float) ($snapshot['total'] ?? $order->total), 2), 'bold' => true];
        $lines[] = ['text' => 'Amount paid: PHP '.number_format((float) $receipt->amount, 2)];
        $lines[] = ['text' => 'Balance due: PHP '.number_format((float) $receipt->balance_due, 2), 'bold' => true];
        $lines[] = ['rule' => true, 'text' => ''];
        $lines[] = ['text' => 'Thank you for choosing '.$brand.'.', 'gap' => 4];
        $lines[] = ['text' => 'This receipt was issued electronically and is valid without a signature.', 'size' => 8];

        return $this->document($lines, 'Receipt '.$receipt->receipt_number);
    }

    private function escape(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = str_replace(['₱', '×', '—', '–'], ['PHP ', 'x', '-', '-'], $text);

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
