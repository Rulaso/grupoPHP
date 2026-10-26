<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Helpers/ResponseJson.php';
require_once __DIR__ . '/../Models/DB.php';
require_once __DIR__ . '/../Models/Mensaje.php';
require_once __DIR__ . '/../Models/Chat.php';

class ChatController{

    public function crearChat(Request $request, Response $response){
        $userID = $request->getAttribute('userID');
        $db = $request->getAttribute('db');
        $queryParams = $request->getQueryParams();
        $enviadoID = $queryParams['user_id'];
        $mensaje = $request->getParsedBody();

        if(!$mensaje || strlen($mensaje) > 255){
            return ResponseJson::json($response, 400, ['Status'=>'Bad request', 'mensaje'=>'El mensaje se encuentra vacio o supera el limite de carcteres(255)']);
        }
        
        $chat = Chat::buscarChat($userID, $db);
        if(!Usuario::obtenerUsuario($userID, $db)){
            return ResponseJson::json($response, 400, ['Status'=>'400', 'user_id'=>'El usuario ingresado no existe']);
        }

        if(!$chat){
            Chat::crearChat($enviadoID, $userID, $db);
            Mensaje::subirMensaje($mensaje, $userID, $chat['id'], $db);
            return ResponseJson::json($response, 200, ['Status'=> 'OK', 'Message'=>'Se creo el chat y se envio el mensaje']);
        }
        if($chat['esta_bloquado']){
            return ResponseJson::json($response, 400, ['Status'=>'Bad request', 'esta_bloqueado'=>'Se encuentra bloqueado en este chat, no puede enviar mensajes aqui']);
        }
        $cantMensajes = Mensaje::contarMensajes($chat['id'], $db);
        if($cantMensajes == 1){
                
                if($chat['usuario_id'] == Mensaje::buscarCreador($chat['chatID'])){
                    return ResponseJson::json($response, 400,['Status'=>'Bad request','user_id'=>'No puedes enviar mensajes en este chat hasta que te respondan']);
                }

        } else {
            Mensaje::subirMensaje($mensaje, $userID, $chat['id'], $db);
            return ResponseJson::json($response, 200, ['Status'=>'OK', 'Message'=>'Se envio el mensaje']);
        }
        /*if(Usuario::obtenerUsuario($userID, $db)){
            if($chat){
                if($chat['esta_bloquado']){
                    return ResponseJson::json($response, 400, ['Status'=>'Bad request', 'esta_bloqueado'=>'Se encuentra bloqueado en este chat, no puede enviar mensajes aqui']);
                } else {
                    $cantMensajes = Mensaje::contarMensajes($chat['id'], $db);
                    if($cantMensajes > 1){
                        Mensaje::subirMensaje($mensaje, $userID, $chat['id'], $db);
                        return ResponseJson::json($response, 200, ['Status'=>'OK', 'Message'=>'Se envio el mensaje']);
                    } else {
                        if($chat['usuario_id' != $enviadoID]){
                            Mensaje::subirMensaje($mensaje, $userID, $chat['id'], $db);
                            return ResponseJson::json($response, 200, ['Status'=>'OK', 'Message'=>'Se envio el mensaje']);
                        } else {
                            return ResponseJson::json($response, 400,['Status'=>'Bad request','user_id'=>'No puedes enviar mensajes en este chat hasta que te respondan']);
                        }
                    }
                }
            } else {
                Chat::crearChat($userID, $userID, $db);
                Mensaje::subirMensaje($mensaje, $userID, $chat['id'], $db);
                return ResponseJson::json($response, 200, ['Status'=> 'OK', 'Message'=>'Se creo el chat y se envio el mensaje']);
            }
        } else {
            return ResponseJson::json($response, 400, ['Status'=>'400', 'user_id'=>'El usuario ingresado no existe']);
        }*/
    }

}