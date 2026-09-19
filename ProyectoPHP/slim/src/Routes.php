<?php
require_once __DIR__ . '/Controllers/Autenticacion.php';
require_once __DIR__ . '/Middlewares/AuthToken.php';
require_once __DIR__ . '/Middlewares/AuthOpcionalToken.php';
require_once __DIR__ . '/Controllers/UsuarioController.php';

$app->post('/login', [Autenticacion::class, 'login']);

$app->post('/logout', [Autenticacion::class, 'logout'])->add(new AuthToken());

$app->get('/usuarios', [UsuarioController::class, 'listarUsuarios'])->add(new AuthOpcionalToken());