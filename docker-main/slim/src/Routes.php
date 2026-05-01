<?php

require_once __DIR__ . '/Controllers/LoginController.php';
require_once __DIR__ . '/Controllers/UserController.php';
require_once __DIR__ . '/Middlewares/AuthToken.php';
require_once __DIR__ . '/Controllers/AssetController.php';
require_once __DIR__ . '/Controllers/PortfolioController.php';
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

//REQUIERE LOGIN, devuelve el valor de los activos que posee el usuario segun su id
$app->get('/portfolio', [PortfolioController::class, 'portfolioUsuario'])->add(AuthToken::class);

//REQUIERE LOGIN, elimina un asset del portfolio
$app->delete('/portfolio/{asset_id}', [PortfolioController::class,'eliminarElemento'])->add(AuthToken::class);

//REQUIERE LOGIN, devuelve el historial de compras del usuario
$app->get('/transactions', [PortfolioController::class, 'devolverTransacciones'])->add(AuthToken::class);

//REQUIERE LOGIN, realiza la compra de un activo
$app->post('/trade/buy', [OperationController::class, 'comprarActivo'])->add(AuthToken::class);

//REQUIERE LOGIN, realiza la venta de un activo
$app->post('/trade/sell', [OperationController::class, 'venderActivo'])->add(AuthToken::class);


//IMPORTANTE PARA NO OLVIDARME, LA BASE DE DATOS TIENE LA INFORMACION DE LOS ACTIVOS SIN NECESIDAD DE CREARLOS? Y SI ES ASI, TENEMOS QUE ACTUALIZAR SUS DATOS CADA VEZ QUE LLAMAMOS A LA FUNCION?
//PQ YO PARA HACER LA COMPRA-VENTA USE LOS DATOS DE ASSETS CURRENT_PRICE, PERO SI NO SE HACE ASI, TENGO QUE CAMBIARLO Y BUSCAR EL PRECIO EN LAS TRANSACCIONES QUE TENGA UNA COMPRA DE 0, COMO HICE CON LO OTRO.