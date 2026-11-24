<?php /** Cabeçalho HTML comum a todos os layouts. */ ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#d0192a">
<meta name="description" content="SUME — Sistema Único de Merenda Escolar: confirme sua presença nas refeições e ajude a reduzir o desperdício de alimentos.">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= isset($title) ? e($title) . ' · ' : '' ?><?= e(config('name')) ?></title>
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="<?= asset('js/app.js') ?>" defer></script>
