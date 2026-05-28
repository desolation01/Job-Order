<?php
declare(strict_types=1);

$checklistItems = normalize_checklist_tasks($order['checklist_tasks'] ?? null);
$checklistProgress = checklist_progress($order['checklist_tasks'] ?? null);
$checklistDialogId = 'checklist-progress-' . (int) $order['id'];
?>
<td class="progress-cell">
    <button type="button" class="progress-button" data-open-dialog="<?= e($checklistDialogId) ?>" aria-label="Open checklist progress for <?= e($order['jo_number']) ?>">
        <span class="progress-track" aria-hidden="true">
            <span class="progress-fill <?= e(checklist_progress_class($checklistProgress)) ?>" style="width: <?= (int) $checklistProgress['percent'] ?>%"></span>
        </span>
        <span class="progress-percent"><?= (int) $checklistProgress['percent'] ?>%</span>
    </button>

    <dialog class="checklist-dialog" id="<?= e($checklistDialogId) ?>">
        <form method="post" action="/job-order-system/checklist_action">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
            <h2>Checklist Tasks</h2>
            <p class="muted"><?= (int) $checklistProgress['done'] ?> of <?= (int) $checklistProgress['total'] ?> completed</p>

            <?php if ($checklistItems): ?>
                <div class="checklist-dialog-list">
                    <?php foreach ($checklistItems as $taskIndex => $task): ?>
                        <label class="checklist-dialog-row">
                            <input type="checkbox" name="done_tasks[]" value="<?= (int) $taskIndex ?>" <?= checked($task['done']) ?>>
                            <span><?= e($task['text']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-state">No checklist tasks yet.</p>
            <?php endif; ?>

            <div class="dialog-actions">
                <button type="submit">Save Progress</button>
                <button type="button" class="button secondary" data-close-dialog>Close</button>
            </div>
        </form>
    </dialog>
</td>
