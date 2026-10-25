<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Helpers/ResponseJson.php';
require_once __DIR__ . '/../Models/DB.php';
require_once __DIR__ . '/../Models/Chat.php';

class ChatController{
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
                if (!preg_match('/^[a-zA-Z0-9]{3,15}$/', $nombre)){
                    return ResponseJson::json($response,400,
                    ["status" => "Bad Request",
                    "nombre" => "El nombre solo puede contener letras, numeros y espacios, entre 3 y 15 caracteres"]);
                }
            }
            if(isset($datos['descripcion'])){
                $descripcion = trim ($datos['descripcion']);
                if (!preg_match('/^[a-zA-Z0-9]{20,40}$/', $descripcion)){
                    return ResponseJson::json($response,400,
                    ["status" => "Bad Request",
                    "descripcion" => "El nombre solo puede contener letras, numeros y espacios, entre 20 y 40 caracteres"]);
                }
            }
            if(isset($datos['color'])){
                $color = trim ($datos['color']);
                if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)){
                    return ResponseJson::json($response,400,
                    ["status" => "Bad Request",
                    "color" => "El color debe ser un código hexadecimal"]);  
                }
            }
            if (isset($datos['esta_bloqueado'])) {
                $esta_bloqueado = $datos['esta_bloqueado'];
                if (!is_bool($esta_bloqueado)) {
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
                    return ResponseJson::json($response, 400, [
                    "status" => "Bad Request",
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
}