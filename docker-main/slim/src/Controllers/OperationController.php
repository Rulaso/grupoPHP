<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Models/DB.php';
require_once __DIR__ . '/../Models/Operation.php';
require_once __DIR__ . '/../Helpers/OperationHelper.php';

class OperationController{

    public function comprarActivo(Request $request, Response $response){
        //Recibo datos del body
        $db = $request->getAttribute('db');
        $datos = $request -> getParsedBody();
        $assetId = trim($datos['asset_id'] ?? '');
        $quantity = trim($datos['quantity'] ?? '');
        //Validaciones
        $validacion = OperationHelper::validarDatos($assetId,$quantity);
        if($validacion !== true){
            $error = ["status" => "Bad Request", "message" => $validacion];
            $response->getBody()->write(json_encode($error));
            DB::closeConnection($db);
            return $response->withHeader('Content-Type','application/json')->withStatus(400);
        }
        else{
            $id = $request->getAttribute('userID');
            $asset = Asset::obtenerCurrent_price($assetId, $db);
            //Valido que sea un asset_id correcto
            if(!$asset){
                $error = ["status" => "Not found", "message" => "El asset_id no corresponde a un Activo"];
                $response->getBody()->write(json_encode($error));
                DB::closeConnection($db);
                return $response->withHeader('Content-Type','application/json')->withStatus(404);
            }
            else{
                $precioActual = $asset['current_price'];
                $balanceData = User::obtenerBalance($id, $db);
                $balance = $balanceData['balance'];
                $total = $precioActual * $quantity;
                if($balance < $total){
                    $error = ["status" => "Conflict", "message" => "No se pudo realizar la compra, saldo insuficiente"];
                    $response->getBody()->write(json_encode($error));
                    DB::closeConnection($db);
                    return $response->withHeader('Content-Type','application/json')->withStatus(409);
                }
                else{
                    $tiempoActual = date('Y-m-d H:i:s');
                    //Creo la transaction
                    Operation::crearTransaccion($id,$assetId,'buy',$quantity,$precioActual,$total,$tiempoActual,$db);
                    //Actualizo el balance
                    User::actualizarBalance($total, $id, $db);
                    //Verifico si es la primer compra del activo para crearla o hacer un update
                    $portfolio = $db->query("SELECT * FROM portfolio WHERE user_id = $id AND asset_id = $assetId")->fetch(PDO::FETCH_ASSOC);
                    if($portfolio){
                        $db->query("UPDATE portfolio SET quantity = quantity + $quantity WHERE user_id = $id AND asset_id = $assetId ");
                    }
                    else{
                        $db->query("INSERT INTO portfolio (user_id,asset_id,quantity)
                                    VALUES ($id, $assetId, $quantity)");
                    }
                    $ok = ["status" => 'OK', "message" => "Se realizó la compra con exito"];
                    $response->getBody()->write(json_encode($ok));
                    DB::closeConnection($db);
                    return $response->withHeader('Content-Type','application/json')->withStatus(200);
                }
            }
        }
    }
    public function venderActivo(Request $request, Response $response){
        $db = $request->getAttribute('db');
        //Recibo datos del body
        $datos = $request -> getParsedBody();
        $assetId = trim($datos['asset_id'] ?? '');
        $quantity = trim($datos['quantity'] ?? '');
        //Validaciones
        $validacion = OperationHelper::validarDatos($assetId,$quantity);
        if($validacion !== true){
            $error = ["status" => "Bad Request", "message" => $validacion];
            $response->getBody()->write(json_encode($error));
            DB::closeConnection($db);
            return $response->withHeader('Content-Type','application/json')->withStatus(400);
        }
        else{
            $id = $request->getAttribute('userID');
            //Valido que sea un asset_id correcto
            $asset = $db->query("SELECT current_price FROM assets WHERE id = $assetId")->fetch(PDO::FETCH_ASSOC);
            if(!$asset){
                $error = ["status" => "Not found", "message" => "El asset_id no corresponde a un Activo"];
                $response->getBody()->write(json_encode($error));
                DB::closeConnection($db);
                return $response->withHeader('Content-Type','application/json')->withStatus(404);
            }
            $portfolio = $db->query("SELECT quantity FROM portfolio WHERE user_id = $id AND asset_id = $assetId")->fetch(PDO::FETCH_ASSOC);
            if(!$portfolio){
                $error = ["status" => "Conflict", "message" => "No posee Activos de ese tipo"];
                $response->getBody()->write(json_encode($error));
                DB::closeConnection($db);
                return $response->withHeader('Content-Type','application/json')->withStatus(409);
            }
            else{
                $myQuantity = $portfolio['quantity'];
                if($myQuantity < $quantity){
                    $error = ["status" => "Conflict", "message" => "No se pudo realizar la venta, activo insuficiente"];
                    $response->getBody()->write(json_encode($error));
                    DB::closeConnection($db);
                    return $response->withHeader('Content-Type','application/json')->withStatus(409);
                }
                else{
                    $precioActual = $asset['current_price'];
                    $total = $precioActual * $quantity;
                    $tiempoActual = date('Y-m-d H:i:s');
                    //Creo la transaction
                    $db->query("INSERT INTO transactions (user_id,asset_id,transaction_type,quantity,price_per_unit,total_amount,transaction_date)
                                VALUES ($id, $assetId, 'sell', $quantity, $precioActual, $total, '$tiempoActual')");
                    //Actualizo el balance
                    $db->query("UPDATE users SET balance = balance + $total WHERE id = $id");
                    //Actualizo el portfolio
                    $db->query("UPDATE portfolio SET quantity = quantity - $quantity WHERE user_id = $id AND asset_id = $assetId ");
                    //Elimino del portfolio el activo si queda en 0
                    $db->query("DELETE FROM portfolio WHERE user_id = $id AND asset_id = $assetId AND quantity = 0");
                    $ok = ["status" => 'OK', "message" => "Se realizó la venta con exito"];
                    $response->getBody()->write(json_encode($ok));
                    DB::closeConnection($db);
                    return $response->withHeader('Content-Type','application/json')->withStatus(200);
                }
            }
        }
    }
}