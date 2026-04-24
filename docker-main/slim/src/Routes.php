<?php

require_once __DIR__ . '/Controllers/LoginController.php';
require_once __DIR__ . '/Controllers/UserController.php';
require_once __DIR__ . '/Middlewares/AuthToken.php';
//login
$app->post('/login', [LoginController::class ,'login']);
//logout
$app->post('/logout', [LoginController::class ,'logout']);
//create
$app->post('/users', [UserController::class,'create']);


//editar nombre/password
$app->put('/user/{user_id}', [UserController::class , 'editar'])->add(AuthToken::class);