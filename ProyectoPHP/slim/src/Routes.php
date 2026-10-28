<?php
require_once __DIR__ . '/Controllers/Autenticacion.php';
require_once __DIR__ . '/Middlewares/AuthToken.php';
require_once __DIR__ . '/Middlewares/AuthOpcionalToken.php';
require_once __DIR__ . '/Controllers/UsuarioController.php';
require_once __DIR__ . '/Controllers/MensajeController.php';
require_once __DIR__ . '/Controllers/ChatController.php';

$app->post('/login', [Autenticacion::class, 'login']);

$app->post('/logout', [Autenticacion::class, 'logout'])->add(new AuthToken());

$app->get('/usuarios', [UsuarioController::class, 'listarUsuarios'])->add(new AuthOpcionalToken());

$app->delete('/mensajes', [MensajeController::class, 'delete'])->add(new AuthToken());

$app->post('/chats/{user_id}', [ChatController::class, 'crearChat'])->add(new AuthToken());

$app->get('/chats', [ChatController::class, 'listarChats'])->add(new AuthToken());

$app->put('/chats/{user_id}', [ChatController::class, 'modificarChat'])->add(new AuthToken());

$app->get('/chats/{user_id}/historia/{quantity}', [ChatController::class, 'obtenerHistorial'])->add(new AuthToken());

$app->post('/usuarios', [UsuarioController::class, 'create']);

$app->get('/usuarios/{user_id}', [UsuarioController::class, 'getUsuarios'])->add(new AuthToken());

$app->put('/usuarios/{user_id}', [UsuarioController::class, 'editarUsuario'])->add(new AuthToken());

$app->delete('/usuarios/{user_id}', [UsuarioController::class, 'eliminarUsuario'])->add(new AuthToken());