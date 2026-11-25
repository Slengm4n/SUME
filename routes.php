<?php

declare(strict_types=1);

use App\Controllers\Admin\ConfirmationController;
use App\Controllers\Admin\FeedbackController;
use App\Controllers\Admin\MenuController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\WasteController;
use App\Controllers\AuthController;
use App\Controllers\ContactController;
use App\Controllers\DashboardController;
use App\Controllers\GoogleController;
use App\Controllers\MealController;
use App\Controllers\PageController;
use App\Controllers\PasswordResetController;
use App\Controllers\ProfileController;
use App\Controllers\RankingController;
use App\Core\Router;

/** @var Router $router */

// Públicas
$router->get('/', [PageController::class, 'landing']);
$router->get('/termos', [PageController::class, 'terms']);
$router->get('/contato', [ContactController::class, 'show']);
$router->post('/contato', [ContactController::class, 'send']);

// Autenticação
$router->get('/entrar', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/entrar', [AuthController::class, 'login'], ['guest']);
$router->get('/cadastro', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/cadastro', [AuthController::class, 'register'], ['guest']);
$router->post('/sair', [AuthController::class, 'logout'], ['auth']);

// Recuperação de senha
$router->get('/esqueci-senha', [PasswordResetController::class, 'showRequest'], ['guest']);
$router->post('/esqueci-senha', [PasswordResetController::class, 'sendLink'], ['guest']);
$router->get('/redefinir-senha', [PasswordResetController::class, 'showReset'], ['guest']);
$router->post('/redefinir-senha', [PasswordResetController::class, 'reset'], ['guest']);

// Login com Google (o callback serve tanto para entrar quanto para vincular pelo perfil)
$router->get('/auth/google', [GoogleController::class, 'redirect']);
$router->get('/auth/google/callback', [GoogleController::class, 'callback']);
$router->get('/cadastro/google', [GoogleController::class, 'showComplete'], ['guest']);
$router->post('/cadastro/google', [GoogleController::class, 'complete'], ['guest']);
$router->post('/perfil/google/desconectar', [GoogleController::class, 'unlink'], ['auth']);

// Área do usuário
$router->get('/painel', [DashboardController::class, 'index'], ['auth']);
$router->post('/refeicao/confirmar', [MealController::class, 'confirm'], ['auth']);
$router->post('/refeicao/cancelar', [MealController::class, 'cancel'], ['auth']);
$router->get('/avaliar', [MealController::class, 'showFeedback'], ['auth']);
$router->post('/avaliar', [MealController::class, 'storeFeedback'], ['auth']);
$router->get('/ranking', [RankingController::class, 'index'], ['auth']);
$router->get('/perfil', [ProfileController::class, 'show'], ['auth']);
$router->post('/perfil', [ProfileController::class, 'update'], ['auth']);
$router->post('/perfil/senha', [ProfileController::class, 'updatePassword'], ['auth']);
$router->post('/perfil/foto', [ProfileController::class, 'updateAvatar'], ['auth']);
$router->post('/perfil/foto/remover', [ProfileController::class, 'removeAvatar'], ['auth']);

// Administração
$router->get('/admin/cardapio', [MenuController::class, 'index'], ['admin']);
$router->post('/admin/cardapio', [MenuController::class, 'store'], ['admin']);
$router->post('/admin/cardapio/excluir', [MenuController::class, 'destroy'], ['admin']);
$router->get('/admin/confirmacoes', [ConfirmationController::class, 'index'], ['admin']);
$router->get('/admin/confirmacoes/dados', [ConfirmationController::class, 'json'], ['admin']);
$router->get('/admin/confirmacoes/exportar', [ConfirmationController::class, 'export'], ['admin']);
$router->post('/admin/confirmacoes/retirada', [ConfirmationController::class, 'pickup'], ['admin']);
$router->post('/admin/confirmacoes/encerrar', [ConfirmationController::class, 'close'], ['admin']);
$router->get('/admin/desperdicio', [WasteController::class, 'index'], ['admin']);
$router->get('/admin/desperdicio/registrar', [WasteController::class, 'edit'], ['admin']);
$router->post('/admin/desperdicio/registrar', [WasteController::class, 'update'], ['admin']);
$router->get('/admin/desperdicio/exportar', [WasteController::class, 'export'], ['admin']);
$router->get('/admin/usuarios', [UserController::class, 'index'], ['admin']);
$router->get('/admin/usuarios/editar', [UserController::class, 'edit'], ['admin']);
$router->post('/admin/usuarios/editar', [UserController::class, 'update'], ['admin']);
$router->post('/admin/usuarios/excluir', [UserController::class, 'destroy'], ['admin']);
$router->get('/admin/avaliacoes', [FeedbackController::class, 'index'], ['admin']);
$router->post('/admin/pontos/deduzir', [RankingController::class, 'deduct'], ['admin']);
$router->post('/admin/pontos/zerar', [RankingController::class, 'reset'], ['admin']);
