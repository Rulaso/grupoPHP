<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . "/../Models/Asset.php";
class AssetController{
    public function actualizarValores(Request $request, Response $response){
        //recupero el id y el db que me mando el middleware
        $id = $request->getAttribute('userID');
        $db = $request->getAttribute('db');

        //verifico que el usuario sea admin
        if(User::esAdmin($id, $db)){
            //obtengo el id, current_price y last_update de TODOS los assets en la DB
            $datos = Asset::obtenerAsset($db);

            //recorro todo el vector actualizando uno por uno los valores de current_price y last_update
            foreach($datos as $asset){
                $assetID = $asset['id'];
                $precio = $asset['current_price'];
                $lastUpdate = $asset['last_update'];

                //invoco a la funcion del ejemplo obteniendo un nuevo precio para mi asset
                $nuevoPrecio = self::variarPrecioPorTiempo($precio, $lastUpdate);
                //obtengo la fecha actual de actualizacion
                $tiempoActual = date('Y-m-d H:i:s');
                //actualizo el asset en la db
                Asset::actualizarAsset($assetID, $nuevoPrecio, $tiempoActual, $db);
            }

            //envio el mensaje indicando que ya actualice todos los assets
            $mensaje = ['Status'=> 'OK', 'message'=> 'Los valores fueron actualizados correctamente'];
            $response->getBody()->write(json_encode($mensaje));
            DB::closeConnection($db);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);            
        } else {
            //envio el mensaje de error indicando que el usuario logueado no es admin
            $error = ['status'=> 'Bad request', 'message'=> 'no tiene los permisos para realizar esta accion'];
            $response->getBody()->write(json_encode($error));
            DB::closeConnection($db);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    private static function variarPrecioPorTiempo($precioActual, $timestampUltimaVez, $volatilidadPorSegundo = 0.05) {
        // 1. Calcular cuántos segundos han pasado
        $timestampUltimaVez = strtotime($timestampUltimaVez);
        $tiempoPasado = time() - $timestampUltimaVez; // Si no ha pasado tiempo, el precio no cambia
        if ($tiempoPasado <= 0) return $precioActual;
        // 2. Generar un cambio aleatorio (puede ser positivo o negativo)
        // mt_rand(-100, 100) / 100 nos da un número entre -1.0 y 1.0
        $direccion = mt_rand(-100, 100) / 100;
        // 3. El cambio total depende del tiempo que pasó
        $delta = $direccion * $volatilidadPorSegundo * $tiempoPasado;
        return $precioActual + $delta;
    }



}