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
?>
<section class="paper-form">
    <div class="paper-header">
        <div class="skyline-mark">
            <img class="skyline-logo" src="/job-order-system/assets/images/15b4f773-f103-4f7d-ac7f-de286683823c.png?v=3" alt="Skyline logo">
            <div class="skyline-wordmark">
                <strong>SKYLINE</strong>
                <span>HOSPITAL AND MEDICAL CENTER</span>
            </div>
        </div>
        <div class="bdmc-pill">BUSINESS DEVELOPMENT AND<br>MARKETING COMMUNICATIONS</div>
    </div>

    <div class="paper-title">JOB ORDER FORM</div>

    <div class="paper-meta">
        <div class="meta-left">
            <div class="paper-cell"><strong>Requesting Department:</strong> <?= e($order['requesting_department'] ?? '') ?></div>
            <div class="paper-cell tall"><strong>Project Name:</strong> <?= e($order['project_name'] ?? '') ?></div>
        </div>
        <div class="meta-right">
            <div class="paper-cell"><strong>J.O No.:</strong> <?= e($order['jo_number'] ?? '') ?></div>
            <div class="paper-cell"><strong>Date Filed:</strong> <?= e($order['date_filed'] ?? '') ?></div>
            <div class="paper-cell"><strong>Date Needed:</strong> <?= e($order['date_needed'] ?? '') ?></div>
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
        <div>
            <strong>DATE RECEIVED</strong>
            <p><?= e($order['date_received'] ?? '') ?></p>
        </div>
        <div>
            <strong>DATE ACCOMPLISHED</strong>
            <p><?= e($order['date_accomplished'] ?? '') ?></p>
        </div>
    </div>

    <div class="paper-footer">
        <span>SALES AND MARKETING FORM 101 - JOB ORDER FORM<br>VERSION 4</span>
        <span>SALES AND MARKETING DEPARTMENT<br>10.23.24</span>
    </div>
</section>
