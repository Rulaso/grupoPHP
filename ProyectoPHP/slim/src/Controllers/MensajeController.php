<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Helpers/ResponseJson.php';
require_once __DIR__ . '/../Models/DB.php';
require_once __DIR__ . '/../Models/Mensaje.php';

class MensajeController{

    public function delete(Request $request, Response $response){
        $db = $request->getAttribute('db');
        $userID = $request->getAttribute('userID');
        $queryParams = $request->getQueryParams();
        $mensajeID = $queryParams['message_id'] ?? null;
        try{
            $enviadoID = Mensaje::buscarMensaje($mensajeID, $db);
            if($enviadoID){
                if(Usuario::esAdmin($userID, $db)){
                    Mensaje::eliminarMensaje($mensajeID, $db);
                    return ResponseJson::json($response, 200, ['Status'=>'OK','Message'=>'El mensaje fue eliminado']);
                } else {
                    if($enviadoID['enviado_por'] == $userID){
                        Mensaje::eliminarMensaje($mensajeID, $db);
                        return ResponseJson::json($response, 200, ['Status'=>'OK', 'Message' => 'El mensaje fue eliminado']);
                    } else {
                        return ResponseJson::json($response, 400, ['Status'=>'Bad request', 'message_id'=> 'El mensaje no pertenece al usuario que lo quiere eliminar']);
                    }
                }
            } else {
                return ResponseJson::json($response, 400, ['Status'=>'Bad request', 'message_id'=> 'El id del mensaje no pertenece a ningun mensaje registrado']);
            }
        } catch(PDOException $e){
            return responseJson::dbError($response, $e);
        }finally{
            DB::closeConnection($db);
        }

    }

}

