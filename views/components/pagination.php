<?php
$page = (int) ($page ?? 1);
$total = (int) ($total ?? 0);
$perPage = (int) ($per_page ?? 15);
$totalPages = max(1, (int) ceil($total / max(1, $perPage)));
$from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
$to = min($page * $perPage, $total);

$query = $_GET;
unset($query['page']);
$baseQuery = http_build_query($query);
$buildUrl = static function (int $p) use ($baseQuery): string {
    $qs = $baseQuery !== '' ? $baseQuery . '&' : '';
    return '?' . $qs . 'page=' . $p;
};
?>
<?php if ($total > $perPage): ?>
<nav class="pagination" aria-label="Paginación">
    <div class="pagination-info">
        Mostrando <?= $from ?>–<?= $to ?> de <?= $total ?> registros
    </div>
    <ul class="pagination-links">
        <li class="<?= $page <= 1 ? 'disabled' : '' ?>">
            <?php if ($page > 1): ?>
            <a href="<?= e($buildUrl($page - 1)) ?>" aria-label="Anterior">&lsaquo;</a>
            <?php else: ?>
            <span>&lsaquo;</span>
            <?php endif; ?>
        </li>
        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        for ($i = $start; $i <= $end; $i++):
        ?>
        <li class="<?= $i === $page ? 'active' : '' ?>">
            <?php if ($i === $page): ?>
            <span><?= $i ?></span>
            <?php else: ?>
            <a href="<?= e($buildUrl($i)) ?>"><?= $i ?></a>
            <?php endif; ?>
        </li>
        <?php endfor; ?>
        <li class="<?= $page >= $totalPages ? 'disabled' : '' ?>">
            <?php if ($page < $totalPages): ?>
            <a href="<?= e($buildUrl($page + 1)) ?>" aria-label="Siguiente">&rsaquo;</a>
            <?php else: ?>
            <span>&rsaquo;</span>
            <?php endif; ?>
        </li>
    </ul>
</nav>
<?php endif; ?>
