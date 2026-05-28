<?php
declare(strict_types=1);

$order = $order ?? [];
$errors = $errors ?? [];
$selectedCategoryIds = $selectedCategoryIds ?? [];
$otherText = $otherText ?? '';
$adminMode = $adminMode ?? false;
?>
<section class="panel">
    <div class="form-grid">
        <label>Requesting Department
            <input name="requesting_department" required value="<?= e($order['requesting_department'] ?? current_user()['department'] ?? '') ?>">
            <small><?= e($errors['requesting_department'] ?? '') ?></small>
        </label>
        <label>Project Name
            <input name="project_name" required value="<?= e($order['project_name'] ?? '') ?>">
            <small><?= e($errors['project_name'] ?? '') ?></small>
        </label>
        <label>J.O No.
            <input readonly value="<?= e($order['jo_number'] ?? 'Auto-generated') ?>">
            <small aria-hidden="true">&nbsp;</small>
        </label>
        <label>Date Filed
            <input type="date" name="date_filed" readonly value="<?= e($order['date_filed'] ?? date('Y-m-d')) ?>">
            <small><?= e($errors['date_filed'] ?? '') ?></small>
        </label>
        <label>Date Needed
            <input type="date" name="date_needed" required value="<?= e($order['date_needed'] ?? '') ?>">
            <small><?= e($errors['date_needed'] ?? '') ?></small>
        </label>
        <label>Urgency
            <select name="urgency">
                <?php foreach (['Low', 'Medium', 'High', 'Critical'] as $urgency): ?>
                    <option <?= selected($order['urgency'] ?? 'Low', $urgency) ?>><?= e($urgency) ?></option>
                <?php endforeach; ?>
            </select>
            <small aria-hidden="true">&nbsp;</small>
        </label>
    </div>

    <fieldset class="category-list">
        <legend>Categories</legend>
        <?php foreach ($categories as $category): ?>
            <label>
                <input type="checkbox" name="categories[]" value="<?= (int) $category['id'] ?>"
                    data-category-name="<?= e($category['category_name']) ?>"
                    <?= checked(in_array((int) $category['id'], $selectedCategoryIds, true)) ?>>
                <?= e($category['category_name']) ?>
            </label>
        <?php endforeach; ?>
        <label class="other-category">Others
            <input name="other_category_text" value="<?= e($otherText) ?>" placeholder="Specify other category">
        </label>
    </fieldset>

    <label>Job Description
        <textarea name="job_description" rows="8" required><?= e($order['job_description'] ?? '') ?></textarea>
        <small><?= e($errors['job_description'] ?? '') ?></small>
    </label>

    <div class="form-grid">
        <label>Requested By
            <input name="requested_by" value="<?= e($order['requested_by'] ?? current_user()['full_name'] ?? '') ?>">
        </label>
        <label>Requested By Date
            <input type="date" name="requested_by_date" value="<?= e($order['requested_by_date'] ?? date('Y-m-d')) ?>">
        </label>
        <label>Noted By
            <input name="noted_by" value="<?= e($order['noted_by'] ?? '') ?>">
        </label>
    </div>

    <?php if ($adminMode): ?>
        <hr>
        <div class="form-grid">
            <label>Approved By
                <input name="approved_by" value="<?= e($order['approved_by'] ?? '') ?>">
            </label>
            <label>Assigned To
                <input name="assigned_to" value="<?= e($order['assigned_to'] ?? '') ?>">
            </label>
            <label>Date Received
                <input type="date" name="date_received" value="<?= e($order['date_received'] ?? '') ?>">
            </label>
            <label>Date Accomplished
                <input type="date" name="date_accomplished" value="<?= e($order['date_accomplished'] ?? '') ?>">
            </label>
            <label>Status
                <select name="status">
                    <?php foreach (['Pending', 'Approved', 'Denied', 'Archived'] as $status): ?>
                        <option <?= selected($order['status'] ?? 'Pending', $status) ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Admin Comment
            <textarea name="admin_comment" rows="4"><?= e($order['admin_comment'] ?? '') ?></textarea>
        </label>
    <?php endif; ?>
</section>
