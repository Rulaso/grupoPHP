<?php

require_once __DIR__ . '/Controllers/LoginController.php';
require_once __DIR__ . '/Controllers/UserController.php';
require_once __DIR__ . '/Middlewares/AuthToken.php';
require_once __DIR__ . '/Controllers/AssetController.php';
//login
$app->post('/login', [LoginController::class ,'login']);
//logout
$app->post('/logout', [LoginController::class ,'logout']);
//create
$app->post('/users', [UserController::class,'create']);


//editar nombre/password
$app->put('/users/{user_id}', [UserController::class , 'editar'])->add(AuthToken::class);

//devuelve los datos mi perfil o siendo admin de UN perfil en especifico
$app->get('/users/{user_id}', [UserController::class, 'getProfile'])->add(AuthToken::class);

//SOLO ADMIN, lista los inversores actuales
$app->get('/users', [UserController::class, 'getUsers'])->add(AuthToken::class);

//SOLO ADMIN, actualizo los valores de los assets
$app->put('/assets', [AssetController::class, 'actualizarValores'])->add(AuthToken::class);

//Cambios de precio de un activo en especifico
$app->get('/assets/{asset_id}/history/{quantity}', [AssetController::class, 'activoPrecio']);

//NO REQUIERE LOGIN, segun los parametros de busqueda devuelve nombre y precio de los activos
$app->get('/assets', [AssetController::class, 'buscarAssets']);
