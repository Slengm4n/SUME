<header class="page-header">
    <div>
        <h1>Usuários</h1>
        <p class="muted"><?= count($users) ?> resultado(s)</p>
    </div>
    <form method="get" action="<?= url('/admin/usuarios') ?>" class="search" role="search">
        <?= icon('search') ?>
        <label for="busca" class="sr-only">Buscar usuários</label>
        <input type="search" id="busca" name="busca" value="<?= e($search) ?>" placeholder="Nome, e-mail ou RM">
    </form>
</header>

<div class="table-wrap">
    <table class="table table--responsive">
        <thead>
            <tr><th>Nome</th><th>E-mail</th><th>RM</th><th>ETEC</th><th class="num">Pontos</th><th><span class="sr-only">Ações</span></th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td data-label="Nome">
                        <span class="person"><?= avatar($u, 'sm') ?> <?= e($u['name']) ?>
                            <?php if ($u['is_master']): ?><span class="badge badge--brand">admin</span><?php endif; ?>
                        </span>
                    </td>
                    <td data-label="E-mail"><?= e($u['email']) ?></td>
                    <td data-label="RM"><?= e($u['RM'] ?? '—') ?></td>
                    <td data-label="ETEC"><?= e($u['codigo'] ?? '—') ?></td>
                    <td data-label="Pontos" class="num"><?= (int) $u['points'] ?></td>
                    <td class="table__actions">
                        <a href="<?= url('/admin/usuarios/editar', ['id' => $u['id']]) ?>" class="icon-btn" aria-label="Editar <?= e($u['name']) ?>"><?= icon('pencil') ?></a>
                        <?php if ((int) $u['id'] !== (int) auth_user()['id']): ?>
                            <form method="post" action="<?= url('/admin/usuarios/excluir') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <button type="submit" class="icon-btn icon-btn--danger" aria-label="Excluir <?= e($u['name']) ?>"
                                        data-confirm="Excluir <?= e($u['name']) ?>? Todas as confirmações e avaliações dessa pessoa também serão apagadas.">
                                    <?= icon('trash') ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="6" class="table__empty">Nenhum usuário encontrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
