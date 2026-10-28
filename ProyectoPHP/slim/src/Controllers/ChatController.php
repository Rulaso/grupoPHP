<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Helpers/ResponseJson.php';
require_once __DIR__ . '/../Models/DB.php';
require_once __DIR__ . '/../Models/Mensaje.php';
require_once __DIR__ . '/../Models/Chat.php';
require_once __DIR__ . '/../Models/Usuario.php';

class ChatController{

/*
    public function crearChat(Request $request, Response $response){
        $userID = $request->getAttribute('userID');
        $db = $request->getAttribute('db');
        $queryParams = $request->getQueryParams();
        $enviadoID = $queryParams['user_id'];
        $mensaje = $request->getParsedBody();

        if(!$mensaje || strlen($mensaje) > 255){
            return ResponseJson::json($response, 400, ['Status'=>'Bad request', 'mensaje'=>'El mensaje se encuentra vacio o supera el limite de carcteres(255)']);
        }
        try{
            $chat = Chat::buscarChat($userID, $db);
            $enviado = Usuario::obtenerUsuarioPorId($enviadoID, $db);
            if(!$enviado){
                return ResponseJson::json($response, 400, ['Status'=>'400', 'user_id'=>'El usuario ingresado no existe']);
            }

            if(!$chat){
                Chat::crearChat($enviadoID, $userID, $enviado['username'], $db);
                Mensaje::subirMensaje($mensaje, $userID, $chat['id'], $db);
                return ResponseJson::json($response, 200, ['Status'=> 'OK', 'Message'=>'Se creo el chat y se envio el mensaje']);
            }
            if($chat['esta_bloquado']){
                return ResponseJson::json($response, 400, ['Status'=>'Bad request', 'esta_bloqueado'=>'Se encuentra bloqueado en este chat, no puede enviar mensajes aqui']);
            }
            $cantMensajes = Mensaje::contarMensajes($chat['id'], $db);
            if($cantMensajes == 1){
                
                    if($chat['usuario_id'] == Mensaje::buscarCreador($chat['chatID'], $db)){
                        return ResponseJson::json($response, 400,['Status'=>'Bad request','user_id'=>'No puedes enviar mensajes en este chat hasta que te respondan']);
                    }

            } else {
                Mensaje::subirMensaje($mensaje, $userID, $chat['id'], $db);
                return ResponseJson::json($response, 200, ['Status'=>'OK', 'Message'=>'Se envio el mensaje']);
            }
        } catch(PDOException $e){
            return ResponseJson::dbError($response,$e);
        } finally {
            DB::closeConnection($db);
        }

    }
*/
    public function crearChat(Request $request, Response $response, array $args)
    {
        $userID = $request->getAttribute('userID');
        $db = $request->getAttribute('db');
        $enviadoID = $args['user_id'];
        $datos = $request->getParsedBody();
        $mensaje = $datos['mensaje'] ?? '';

        if (!is_numeric($enviadoID)) {
            return ResponseJson::json($response, 400, [
            'Status' => 'Bad request',
            'user_id' => 'El ID debe ser numérico']);
        }
        if (empty(trim($mensaje)) || strlen($mensaje) > 255) {
            return ResponseJson::json($response, 400, [
            'Status' => 'Bad request',
            'mensaje' => 'El mensaje se encuentra vacío o supera el límite de 255 caracteres']);
        }
        
        try {
            $chat = Chat::buscarChat($userID, $enviadoID, $db);
            $enviado = Usuario::obtenerUsuarioPorID($enviadoID, $db);

            if (!$enviado) {
                return ResponseJson::json($response, 400, [
                'Status' => 'Bad request',
                'user_id' => 'El usuario ingresado no existe']);
            }

            if (!$chat) {
                $chatID = Chat::crearChat($userID, $enviadoID, $enviado['nombre'], $db);
                Mensaje::subirMensaje($mensaje, $userID, $chatID, $db);
                return ResponseJson::json($response, 201, [
                'Status' => 'OK',
                'Message' => 'Se creó el chat y se envió el mensaje']);
            }

            if ($chat['esta_bloqueado'] == 1) {
                return ResponseJson::json($response, 400, [
                'Status' => 'Bad request',
                'esta_bloqueado' => 'El chat está bloqueado, no se pueden enviar mensajes']);
            }

            $cantMensajes = Mensaje::contarMensajes($chat['id'], $db);

            if ($cantMensajes == 1) {
                $primerMensaje = Mensaje::buscarCreador($chat['id'], $db);

                if ($primerMensaje['enviado_por'] == $userID) {
                    return ResponseJson::json($response, 400, [
                    'Status' => 'Bad request',
                    'mensaje' => 'Debes esperar a que el otro usuario responda']);
                }
            }

            Mensaje::subirMensaje($mensaje, $userID, $chat['id'], $db);
            return ResponseJson::json($response, 200, [
            'Status' => 'OK',
            'Message' => 'Se envió el mensaje']);
        } 
        catch (PDOException $e) {
            return ResponseJson::dbError($response, $e);
        }
        finally{
            if ($db != null) {
                DB::closeConnection($db);
            }
        }
    }


    public function listarChats(Request $request, Response $response){
        $db = $request->getAttribute('db');
        $userID = $request->getAttribute('userID');
        try{
            $chats = Chat::obtenerChats($userID,$db);
            return ResponseJson::json($response,200,$chats);
        }
        catch(PDOException $e){
            return ResponseJson::dbError($response,$e);
        }
        finally{
            if($db != null){
                DB::closeConnection($db);
            }
        }
    }

    public function modificarChat(Request $request, Response $response, array $args){
        $db = $request->getAttribute('db');
        $user_id = $args['user_id'];

        if(!is_numeric($user_id)){
            DB::closeConnection($db);
            return ResponseJson::json($response,400,
            ["status"=> "Bad Request",
            "user_id"=> "Id invalido"]);
        }
        else{
            $myID = $request->getAttribute('userID');
            $datos = $request->getParsedBody();

            if(isset($datos['nombre'])){
                $nombre = trim($datos['nombre']);
                if (!preg_match('/^[a-zA-Z0-9 ]{3,15}$/', $nombre)){
                    DB::closeConnection($db);
                    return ResponseJson::json($response,400,
                    ["status" => "Bad Request",
                    "nombre" => "El nombre solo puede contener letras y números, entre 3 y 15 caracteres"]);
                }
            }
            if(isset($datos['descripcion'])){
                $descripcion = trim ($datos['descripcion']);
                if (!preg_match('/^[a-zA-Z0-9 ]{20,40}$/', $descripcion)){
                    DB::closeConnection($db);
                    return ResponseJson::json($response,400,
                    ["status" => "Bad Request",
                    "descripcion" => "La descripción solo puede contener letras y números, entre 20 y 40 caracteres"]);
                }
            }
            if(isset($datos['color'])){
                $color = trim ($datos['color']);
                if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)){
                    DB::closeConnection($db);
                    return ResponseJson::json($response,400,
                    ["status" => "Bad Request",
                    "color" => "El color debe ser un código hexadecimal"]);  
                }
            }
            if (isset($datos['esta_bloqueado'])) {
                $esta_bloqueado = $datos['esta_bloqueado'];
                if (!is_bool($esta_bloqueado)) {
                    DB::closeConnection($db);
                    return ResponseJson::json($response, 400, [
                    "status" => "Bad Request",
                    "esta_bloqueado" => "Debe ser true o false"]);
                }
            }

            try{
                $chatID = Chat::obtenerIdChat($myID, $user_id, $db);
                if($chatID !== false){
                    if (isset($datos['esta_bloqueado'])){
                        $bloqueo = Chat::editarBloqueo($chatID,$db,$esta_bloqueado);
                        if(!$bloqueo){
                            return ResponseJson::json($response,400, 
                            ["status" => "Bad Request", 
                            "esta_bloqueado" => "No se permite desbloquear el chat"]);
                        }
                    }
                    if(isset($datos['nombre'])){
                        Chat::editarNombre($chatID,$db,$nombre);
                    }
                    if(isset($datos['descripcion'])){
                        Chat::editarDescripcion($chatID,$db,$descripcion);
                    }
                    if(isset($datos['color'])){
                        Chat::editarColor($chatID,$db,$color);
                    }
                    return ResponseJson::json($response,200,
                        ["status" => "OK",
                        "message" => "Los datos fueron actualizados"]);
                }
                else{
                    return ResponseJson::json($response, 404, [
                    "status" => "Not Found",
                    "user_id" => "Chat no encontrado"]);
                }
            }
            catch(PDOException $e){
                return ResponseJson::dbError($response, $e);
            }
            finally{
                if ($db != null) {
                    DB::closeConnection($db);
                }
            }
        }
    }

    public function obtenerHistorial(Request $request, Response $response, array $args){
        $db = $request->getAttribute('db');
        $user_id = $args['user_id'];
        if(!is_numeric($user_id)){
            DB::closeConnection($db);
            return ResponseJson::json($response,400,
            ["status"=> "Bad Request",
            "user_id"=> "Id invalido"]);
        }

        $quantity = $args['quantity'] ?? 5;
        if(!is_numeric($quantity) || $quantity <= 0){
            DB::closeConnection($db);
            return ResponseJson::json($response, 400, [
            "status" => "Bad Request",
            "quantity" => "La cantidad debe ser un entero positivo"]);
        }
        if ($quantity > 10) {
            DB::closeConnection($db);
            return ResponseJson::json($response, 400, [
            "status" => "Bad Request",
            "quantity" => "La cantidad máxima es 10"]);
        }
        $params = $request->getQueryParams(); 
        $offset = $params['offset'] ?? 0;
        if(!is_numeric($offset) || $offset < 0){
            DB::closeConnection($db);
            return ResponseJson::json($response, 400, [
            "status" => "Bad Request",
            "offset" => "El offset debe ser un entero positivo"]);
        }
        $myID = $request->getAttribute('userID');
        try{
            $chatID = Chat::obtenerIdChat($myID, $user_id, $db);
            if ($chatID === false) {
                return ResponseJson::json($response, 404, [
                "status" => "Not Found",
                "chat" => "Chat no encontrado"]);
            }
            $mensajes = Chat::obtenerHistorial($chatID, $quantity, $offset, $db);
            return ResponseJson::json($response, 200, $mensajes);
        }
        catch(PDOException $e){
            return ResponseJson::dbError($response, $e);
        }
        finally{
            if ($db != null) {
                DB::closeConnection($db);
            }
        }
    }
}