<?php

require_once __DIR__ . "/../Models/User.php";
require_once __DIR__ ."/../Models/DB.php";
class AuthToken{
    public function __invoke($request, $handler){
        //Abro la coneccion a la base de datos
        $db = DB::getConnection();

        //recupero el token desde el header
        $authHeader = $request->getHeaderLine("Authorization");
        $token = trim(str_replace("Bearer", '', $authHeader));

        //Llamo a la funcion esta logueado
        $id = self::estaLogueado($token, $db);
        //Si esta logueado, continuo con la ejecucion y envio el id del usuario que envio el token 
        if($id != null){
            return $handler->handle($request->withAttribute('userID', $id));
        } else {
            //Si no esta logueado creo el objeto response y envio la respuesta a postman 
            $response = new \Slim\Psr7\Response();
        
            $error = ["Status"=>"Bad request", "message"=>"El usuario no se encuentra logueado"];
            $response->getBody()->write(json_encode($error));
            DB::closeConnection($db);
            return $response->withHeader("Content-Type", "application/json")->withStatus(400);
        }
    }

    public static function estaLogueado($token, $db){
        //Verifico si el token existe en la db, y traigo la id de ese token.
        $datos = $db->query("SELECT id, token_expired_at FROM users WHERE token = '$token'")->fetch(PDO::FETCH_ASSOC);

        if(!$datos){
            return null;
        } 
        $id = $datos['id'];
        $tokenExpire = $datos['token_expired_at'];
        $tiempoActual = date('Y-m-d H:i:s');
        //Si todavia no expiro..
        if($tokenExpire > $tiempoActual){
            User::updateToken($id, $db);
            return $id;
        }
        else{
            User::deleteToken($id,$db);
            return null;
        }
    }
}