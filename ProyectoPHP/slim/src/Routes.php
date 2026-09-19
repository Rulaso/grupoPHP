<?php
require_once __DIR__ . '/Controllers/Autenticacion.php';

$app->post('/login', [Autenticacion::class, 'login']);
