<?php
require_once __DIR__ . '/Controllers/Autenticacion.php';
require_once __DIR__ . '/Middlewares/AuthToken.php';

$app->post('/login', [Autenticacion::class, 'login']);

$app->post('/logout', [Autenticacion::class, 'logout'])->add(new AuthToken());