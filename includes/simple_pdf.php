<?php
declare(strict_types=1);

function pdf_escape(string $text): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function pdf_text(float $x, float $y, string $text, int $size = 10): string
{
    return "BT /F1 {$size} Tf {$x} {$y} Td (" . pdf_escape($text) . ") Tj ET\n";
}

function pdf_line(float $x1, float $y1, float $x2, float $y2): string
{
    return "{$x1} {$y1} m {$x2} {$y2} l S\n";
}

function pdf_rect(float $x, float $y, float $w, float $h): string
{
    return "{$x} {$y} {$w} {$h} re S\n";
}

function pdf_wrap_text(string $text, int $maxChars = 92): array
{
    $lines = [];
    foreach (preg_split('/\R/', $text) as $paragraph) {
        $wrapped = wordwrap(trim($paragraph), $maxChars, "\n", true);
        foreach (explode("\n", $wrapped) as $line) {
            $lines[] = $line;
        }
    }
    return $lines ?: [''];
}

function build_job_order_pdf(array $order, array $categories): string
{
    $selected = [];
    $otherText = '';
    foreach ($categories as $category) {
        $selected[] = $category['category_name'];
        if ($category['category_name'] === 'Others') {
            $otherText = (string) $category['other_category_text'];
        }
    }

    $categoryRows = [
        ['Publicity Campaign', 'Collaterals', 'Crisis Management'],
        ['Marketing Campaign', 'Video', 'Event Management'],
        ['Forms', 'Social Media Campaign/Announcement', 'Event Coverage'],
        ['Press Release', 'Off-site Billboard', 'Others'],
    ];

    $stream = '';
    $stream .= pdf_rect(360, 760, 170, 42);
    $stream .= pdf_text(375, 785, 'BUSINESS DEVELOPMENT AND', 10);
    $stream .= pdf_text(374, 770, 'MARKETING COMMUNICATIONS', 10);

    $stream .= pdf_rect(54, 720, 504, 18);
    $stream .= pdf_text(235, 725, 'JOB ORDER FORM', 14);

    $stream .= pdf_rect(54, 666, 504, 54);
    $stream .= pdf_line(54, 693, 558, 693);
    $stream .= pdf_line(380, 666, 380, 720);
    $stream .= pdf_line(452, 666, 452, 720);
    $stream .= pdf_line(380, 684, 558, 684);
    $stream .= pdf_line(380, 702, 558, 702);
    $stream .= pdf_text(62, 703, 'Requesting Department: ' . (string) $order['requesting_department']);
    $stream .= pdf_text(62, 675, 'Project Name: ' . (string) $order['project_name']);
    $stream .= pdf_text(388, 707, 'J.O No.:');
    $stream .= pdf_text(458, 707, (string) $order['jo_number']);
    $stream .= pdf_text(388, 689, 'Date Filed:');
    $stream .= pdf_text(458, 689, (string) $order['date_filed']);
    $stream .= pdf_text(388, 671, 'Date Needed:');
    $stream .= pdf_text(458, 671, (string) $order['date_needed']);

    $y = 630;
    foreach ($categoryRows as $row) {
        $x = 54;
        foreach ($row as $label) {
            $stream .= pdf_rect($x, $y, 12, 16);
            if (in_array($label, $selected, true)) {
                $stream .= pdf_text($x + 2, $y + 4, 'X', 10);
            }
            $categoryLabel = $label === 'Others' && $otherText !== '' ? "Others: {$otherText}" : $label;
            $stream .= pdf_rect($x + 12, $y, 156, 16);
            $stream .= pdf_text($x + 18, $y + 5, $categoryLabel, 9);
            $x += 168;
        }
        $y -= 16;
    }

    $stream .= pdf_text(240, 548, 'JOB DESCRIPTION', 13);
    $stream .= pdf_rect(54, 330, 504, 206);
    $lineY = 516;
    foreach (array_slice(pdf_wrap_text((string) $order['job_description']), 0, 14) as $line) {
        $stream .= pdf_text(64, $lineY, $line, 10);
        $lineY -= 13;
    }

    $stream .= pdf_text(64, 305, 'Note: For events or other detailed requests, please attach a separate sheet to this form.', 10);
    $stream .= pdf_text(64, 265, 'Requested by:', 11);
    $stream .= pdf_text(64, 250, 'Requesting Department', 10);
    $stream .= pdf_line(64, 205, 245, 205);
    $stream .= pdf_text(64, 214, trim((string) $order['requested_by'] . ' ' . (string) $order['requested_by_date']), 10);
    $stream .= pdf_text(64, 192, 'Signature over Printed Name / Date', 9);

    $stream .= pdf_text(330, 265, 'Noted by:', 11);
    $stream .= pdf_text(330, 250, "Requesting Department's Head", 10);
    $stream .= pdf_line(330, 205, 520, 205);
    $stream .= pdf_text(330, 214, (string) $order['noted_by'], 10);
    $stream .= pdf_text(330, 192, 'Signature over Printed Name', 9);

    $stream .= pdf_line(54, 170, 558, 170);
    $stream .= pdf_text(205, 148, 'FOR BD&MC DEPARTMENT USE ONLY', 13);
    $stream .= pdf_rect(54, 60, 504, 70);
    $stream .= pdf_line(205, 60, 205, 130);
    $stream .= pdf_line(356, 60, 356, 130);
    $stream .= pdf_line(457, 60, 457, 130);
    $stream .= pdf_text(62, 115, 'Approved by:');
    $stream .= pdf_text(62, 78, (string) $order['approved_by']);
    $stream .= pdf_text(214, 115, 'Assigned To:');
    $stream .= pdf_text(214, 78, (string) $order['assigned_to']);
    $stream .= pdf_text(372, 115, 'DATE RECEIVED');
    $stream .= pdf_text(372, 92, (string) $order['date_received']);
    $stream .= pdf_text(468, 115, 'DATE ACCOMPLISHED');
    $stream .= pdf_text(468, 92, (string) $order['date_accomplished']);

    $objects = [];
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}endstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $number = $index + 1;
        $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
    }

    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

    return $pdf;
}

function output_job_order_pdf(array $order, array $categories): never
{
    $filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $order['jo_number']) . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    echo build_job_order_pdf($order, $categories);
    exit;
}
