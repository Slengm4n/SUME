<?php
use App\Services\WasteReport;

/** Teto "redondo" do eixo Y para a taxa de desperdício (em fração). */
$maxRate = max(array_merge([0.0], array_column($weekly, 'rate')));
$axisMax = 0.1;
foreach ([0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.8, 1.0] as $step) { // metades sempre redondas
    if ($maxRate <= $step) { $axisMax = $step; break; }
}
$labelEvery = max(1, (int) ceil(count($weekly) / 12));

$delta = ($summary['waste_rate'] !== null && $previous['waste_rate'] !== null)
    ? $summary['waste_rate'] - $previous['waste_rate'] : null;
$pendingRows = array_values(array_filter($rows, fn ($r) => $r['prepared_portions'] === null && $r['menu_date'] < today()));
?>
<header class="page-header">
    <div>
        <h1>Desperdício</h1>
        <p class="muted">Quanto do que foi preparado voltou para o lixo.</p>
    </div>
    <div class="toolbar">
        <nav class="segmented" aria-label="Período">
            <?php foreach ($periods as $value => $label): ?>
                <a href="<?= url('/admin/desperdicio', ['periodo' => $value]) ?>" class="segmented__item<?= $value === $days ? ' is-active' : '' ?>" <?= $value === $days ? 'aria-current="true"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <a href="<?= url('/admin/desperdicio/exportar', ['periodo' => $days]) ?>" class="btn btn--secondary btn--sm"><?= icon('download') ?> Excel</a>
    </div>
</header>

<?php if ($pendingRows): ?>
    <div class="notice notice--warning">
        <?= icon('alert') ?>
        <p>
            <strong><?= count($pendingRows) ?> refeição(ões)</strong> sem registro de sobras neste período.
            <a href="<?= url('/admin/desperdicio/registrar', ['id' => $pendingRows[0]['id']]) ?>">Registrar a de <?= e(format_date($pendingRows[0]['menu_date'], true)) ?></a>
        </p>
    </div>
<?php endif; ?>

<section class="stats stats--4" aria-label="Indicadores do período">
    <div class="stat">
        <span class="stat__icon"><?= icon('leaf') ?></span>
        <span class="stat__value"><?= percent($summary['waste_rate']) ?></span>
        <span class="stat__label">
            de desperdício
            <?php if ($delta !== null && abs($delta) >= 0.0005): ?>
                <span class="delta <?= $delta < 0 ? 'delta--good' : 'delta--bad' ?>" title="Comparado aos <?= (int) $days ?> dias anteriores (<?= percent($previous['waste_rate']) ?>)">
                    <?= icon($delta < 0 ? 'trending-down' : 'trending-up') ?><?= number_br(abs($delta) * 100, 1) ?> p.p.
                </span>
            <?php endif; ?>
        </span>
    </div>
    <div class="stat">
        <span class="stat__icon"><?= icon('trash') ?></span>
        <span class="stat__value"><?= number_br($summary['leftover']) ?></span>
        <span class="stat__label">porções sobraram de <?= number_br($summary['prepared']) ?></span>
    </div>
    <div class="stat">
        <span class="stat__icon"><?= icon('scale') ?></span>
        <span class="stat__value"><?= $summary['kg'] !== null ? number_br($summary['kg'], 1) . ' kg' : '—' ?></span>
        <span class="stat__label">descartados</span>
    </div>
    <div class="stat">
        <span class="stat__icon"><?= icon('users') ?></span>
        <span class="stat__value"><?= percent($summary['attendance'], 0) ?></span>
        <span class="stat__label" title="Porções servidas ÷ alunos que confirmaram">comparecimento dos confirmados</span>
    </div>
</section>

<section class="card chart-card" aria-labelledby="grafico-titulo">
    <div class="card__head">
        <h2 id="grafico-titulo" class="card__title"><?= icon('chart') ?> Desperdício por semana</h2>
        <span class="muted small">% das porções preparadas que sobraram</span>
    </div>

    <?php if ($weekly): ?>
        <div class="barchart" role="img" aria-label="Gráfico de barras: taxa de desperdício por semana. Os valores estão na tabela abaixo.">
            <div class="barchart__grid" aria-hidden="true">
                <?php foreach ([1, 0.5, 0] as $f): ?>
                    <div class="barchart__gridline" style="bottom: <?= $f * 100 ?>%"><span><?= percent($axisMax * $f, 0) ?></span></div>
                <?php endforeach; ?>
            </div>
            <div class="barchart__bars">
                <?php foreach ($weekly as $i => $w): $last = $i === count($weekly) - 1; ?>
                    <div class="barchart__col">
                        <div class="barchart__track">
                            <?php if ($last): ?><span class="barchart__value" style="bottom: <?= min(100, $w['rate'] / $axisMax * 100) ?>%"><?= percent($w['rate']) ?></span><?php endif; ?>
                            <span class="barchart__bar" tabindex="0" style="height: <?= max(0.5, min(100, $w['rate'] / $axisMax * 100)) ?>%"
                                  data-tip="<?= e('Semana de ' . format_date($w['week'])) ?>|<?= e(percent($w['rate']) . ' de desperdício') ?>|<?= e(number_br($w['leftover']) . ' de ' . number_br($w['prepared']) . ' porções · ' . $w['meals'] . ' refeição(ões)') ?>"
                                  aria-label="<?= e('Semana de ' . format_date($w['week']) . ': ' . percent($w['rate'])) ?>"></span>
                        </div>
                        <span class="barchart__label"><?= $i % $labelEvery === 0 || $last ? e(date('d/m', strtotime($w['week']))) : '' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="empty empty--inline">
            <?= icon('chart') ?>
            <p>Registre as sobras das refeições servidas para ver a evolução aqui.</p>
        </div>
    <?php endif; ?>
</section>

<section class="section-block" aria-labelledby="refeicoes-titulo">
    <h2 id="refeicoes-titulo" class="section-block__title"><?= icon('utensils') ?> Refeições do período</h2>
    <?php if ($rows): ?>
        <div class="table-wrap">
            <table class="table table--responsive">
                <thead>
                    <tr>
                        <th>Data</th><th>Refeição</th>
                        <th class="num">Confirmados</th><th class="num">Preparadas</th><th class="num">Sobras</th>
                        <th class="num">Desperdício</th><th class="num">kg</th><th><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): $rate = WasteReport::rowRate($r); $done = $r['prepared_portions'] !== null; ?>
                        <tr>
                            <td data-label="Data" class="nowrap"><strong><?= e(format_date($r['menu_date'], true)) ?></strong></td>
                            <td data-label="Refeição"><?= e($r['dish']) ?></td>
                            <td data-label="Confirmados" class="num"><?= (int) $r['confirmations'] ?></td>
                            <td data-label="Preparadas" class="num"><?= $done ? number_br($r['prepared_portions']) : '—' ?></td>
                            <td data-label="Sobras" class="num"><?= $done ? number_br($r['leftover_portions']) : '—' ?></td>
                            <td data-label="Desperdício" class="num"><?= $done ? percent($rate) : ($r['menu_date'] === today() ? '<span class="badge badge--muted">hoje</span>' : '<span class="badge badge--warning">pendente</span>') ?></td>
                            <td data-label="kg" class="num"><?= $r['waste_kg'] !== null ? number_br((float) $r['waste_kg'], 1) : '—' ?></td>
                            <td class="table__actions">
                                <a href="<?= url('/admin/desperdicio/registrar', ['id' => $r['id']]) ?>" class="btn btn--sm <?= $done ? 'btn--ghost' : 'btn--secondary' ?>">
                                    <?= $done ? icon('pencil') . ' Editar' : icon('plus') . ' Registrar' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty empty--card">
            <?= icon('calendar') ?>
            <p>Nenhuma refeição servida neste período.</p>
        </div>
    <?php endif; ?>
</section>

<p class="muted small method-note">
    <strong>Como calculamos:</strong> desperdício = sobras ÷ porções preparadas.
    Comparecimento = porções servidas (preparadas − sobras) ÷ alunos que confirmaram;
    é esse número que a previsão de demanda usa para sugerir quanto preparar.
</p>
