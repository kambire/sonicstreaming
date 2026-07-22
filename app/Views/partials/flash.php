<?php $msg = flash('message'); ?>
<?php if ($msg): ?>
    <div class="alert alert-<?= e($msg['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($msg['text']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>
