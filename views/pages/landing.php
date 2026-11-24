<section class="hero">
    <div class="container hero__inner">
        <div class="hero__text">
            <span class="eyebrow"><?= icon('leaf') ?> Merenda escolar sem desperdício</span>
            <h1>Cozinhe só o que vai ser <em>servido</em>.</h1>
            <p class="lead">
                O SUME conecta alunos e cozinha: cada aluno confirma se vai comer,
                a escola prepara a quantidade certa e o desperdício cai.
            </p>
            <div class="hero__cta">
                <?php if ($user): ?>
                    <a href="<?= url('/painel') ?>" class="btn btn--primary btn--lg">Ir para o painel <?= icon('arrow-right') ?></a>
                <?php else: ?>
                    <a href="<?= url('/cadastro') ?>" class="btn btn--primary btn--lg">Criar minha conta <?= icon('arrow-right') ?></a>
                    <a href="<?= url('/entrar') ?>" class="btn btn--secondary btn--lg">Já tenho conta</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero__visual" aria-hidden="true">
            <div class="mock">
                <div class="mock__head">
                    <span class="mock__label"><?= icon('calendar') ?> Hoje</span>
                    <span class="badge badge--success"><?= icon('check') ?> Confirmado</span>
                </div>
                <p class="mock__dish">Arroz, feijão, frango assado e salada de alface</p>
                <div class="mock__stats">
                    <div><strong>312</strong><span>confirmados</span></div>
                    <div><strong>4,6</strong><span>nota média</span></div>
                    <div><strong>27</strong><span>avaliações</span></div>
                </div>
            </div>
            <div class="mock mock--small">
                <?= icon('trophy') ?>
                <div><strong>+10 pontos</strong><span>pela confirmação</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <header class="section__header">
            <h2>Como funciona</h2>
            <p>Três passos simples, todos os dias.</p>
        </header>
        <ol class="steps">
            <li class="step">
                <span class="step__icon"><?= icon('calendar') ?></span>
                <h3>A escola publica o cardápio</h3>
                <p>A equipe da cozinha cadastra as refeições da semana com antecedência.</p>
            </li>
            <li class="step">
                <span class="step__icon"><?= icon('check-circle') ?></span>
                <h3>Você confirma presença</h3>
                <p>Com um toque, avise se vai comer. Mudou de ideia? É só cancelar dentro do prazo.</p>
            </li>
            <li class="step">
                <span class="step__icon"><?= icon('star') ?></span>
                <h3>Avalie e ganhe pontos</h3>
                <p>Dê sua nota para a refeição, ajude a melhorar o cardápio e suba no ranking.</p>
            </li>
        </ol>
    </div>
</section>

<section class="section section--muted">
    <div class="container features">
        <article class="feature">
            <?= icon('leaf') ?>
            <h3>Menos desperdício</h3>
            <p>Preparar a quantidade certa evita que comida boa vá para o lixo.</p>
        </article>
        <article class="feature">
            <?= icon('chart') ?>
            <h3>Planejamento com dados</h3>
            <p>A cozinha vê em tempo real quantos alunos confirmaram para cada dia.</p>
        </article>
        <article class="feature">
            <?= icon('message') ?>
            <h3>A voz dos alunos</h3>
            <p>Avaliações mostram o que agrada e o que precisa melhorar no cardápio.</p>
        </article>
    </div>
</section>

<section class="section">
    <div class="container cta-band">
        <div>
            <h2>Pronto para começar?</h2>
            <p>Crie sua conta com seu RM e o código da sua ETEC.</p>
        </div>
        <a href="<?= url($user ? '/painel' : '/cadastro') ?>" class="btn btn--light btn--lg"><?= $user ? 'Abrir painel' : 'Criar conta grátis' ?></a>
    </div>
</section>
