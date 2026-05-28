<?php
declare(strict_types=1);

$selectedCategoryNames = [];
foreach ($categories as $category) {
    $selectedCategoryNames[] = $category['category_name'];
}

$categoryRows = [
    ['Publicity Campaign', 'Collaterals', 'Crisis Management'],
    ['Marketing Campaign', 'Video', 'Event Management'],
    ['Forms', 'Social Media Campaign/Announcement', 'Event Coverage'],
    ['Press Release', 'Off-site Billboard', 'Others'],
];

$otherCategory = '';
foreach ($categories as $category) {
    if ($category['category_name'] === 'Others') {
        $otherCategory = (string) $category['other_category_text'];
    }
}

$footerDate = '';
if (!empty($order['date_filed'])) {
    $dateObj = DateTime::createFromFormat('Y-m-d', (string) $order['date_filed']);
    if ($dateObj && $dateObj->format('Y-m-d') === (string) $order['date_filed']) {
        $footerDate = $dateObj->format('m.d.y');
    }
}
?>
<section class="paper-form">
    <div class="paper-header">
        <img class="skyline-header-logo" src="/job-order-system/assets/images/Skyline%20Logo.png?v=1" alt="Skyline Hospital and Medical Center">
        <div class="bdmc-pill">BUSINESS DEVELOPMENT AND<br>MARKETING COMMUNICATIONS</div>
    </div>

    <div class="paper-title">JOB ORDER FORM</div>

    <div class="paper-meta">
        <div class="meta-left meta-left-compact">
            <div class="paper-cell">
                <span class="meta-label">Requesting Department:</span>
                <span class="meta-value"><?= e($order['requesting_department'] ?? '') ?></span>
            </div>
            <div class="paper-cell">
                <span class="meta-label">Project Name:</span>
                <span class="meta-value"><?= e($order['project_name'] ?? '') ?></span>
            </div>
        </div>
        <div class="meta-right meta-right-compact">
            <div class="paper-cell">
                <span class="meta-label">J.O No.:</span>
                <span class="meta-value"><?= e($order['jo_number'] ?? '') ?></span>
            </div>
            <div class="paper-cell">
                <span class="meta-label">Date Filed:</span>
                <span class="meta-value"><?= e($order['date_filed'] ?? '') ?></span>
            </div>
            <div class="paper-cell">
                <span class="meta-label">Date Needed:</span>
                <span class="meta-value"><?= e($order['date_needed'] ?? '') ?></span>
            </div>
        </div>
    </div>

    <table class="paper-categories">
        <tbody>
        <?php foreach ($categoryRows as $row): ?>
            <tr>
                <?php foreach ($row as $label): ?>
                    <td class="check-cell"><?= in_array($label, $selectedCategoryNames, true) ? '&#10003;' : '' ?></td>
                    <td><?= e($label) ?><?= $label === 'Others' && $otherCategory ? ': ' . e($otherCategory) : '' ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="paper-section-title">JOB DESCRIPTION</h2>
    <div class="description-box"><?= nl2br(e($order['job_description'] ?? '')) ?></div>

    <p class="paper-note">Note: For events or other detailed requests, please attach a separate sheet to this form.</p>

    <div class="signature-row">
        <div>
            <p>Requested by:<br><em>Requesting Department</em></p>
            <p class="signature-value"><?= e($order['requested_by'] ?? '') ?> <?= e($order['requested_by_date'] ?? '') ?></p>
            <span>Signature over Printed Name / Date</span>
        </div>
        <div>
            <p>Noted by:<br><em>Requesting Department's Head</em></p>
            <p class="signature-value"><?= e($order['noted_by'] ?? '') ?></p>
            <span>Signature over Printed Name</span>
        </div>
    </div>

    <div class="bdmc-divider"></div>
    <h2 class="paper-section-title">FOR BD&MC DEPARTMENT USE ONLY</h2>

    <div class="bdmc-grid">
        <div>
            <strong>Approved by:</strong>
            <p class="signature-value"><?= e($order['approved_by'] ?? '') ?></p>
            <span>Signature over Printed Name</span>
        </div>
        <div>
            <strong>Assigned To:</strong>
            <p class="signature-value"><?= e($order['assigned_to'] ?? '') ?></p>
            <span>Signature over Printed Name</span>
        </div>
        <div class="bdmc-date-box">
            <div class="bdmc-date-label">DATE RECEIVED</div>
            <p class="bdmc-date-value"><?= e($order['date_received'] ?? '') ?></p>
        </div>
        <div class="bdmc-date-box">
            <div class="bdmc-date-label">DATE ACCOMPLISHED</div>
            <p class="bdmc-date-value"><?= e($order['date_accomplished'] ?? '') ?></p>
        </div>
    </div>

    <div class="paper-footer">
        <span>SALES AND MARKETING DEPARTMENT<br><?= e($footerDate) ?></span>
    </div>
</section>
