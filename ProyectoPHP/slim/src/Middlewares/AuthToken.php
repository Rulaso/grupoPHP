<?php

require_once __DIR__ . "/../Models/Usuario.php";
require_once __DIR__ ."/../Models/DB.php";
require_once __DIR__ . "/../Helpers/ResponseJson.php";
class AuthToken{
    public function __invoke($request, $handler){
        $db = null;
        
        try {
            $db = DB::getConnection();
            $authHeader = $request->getHeaderLine("Authorization");
            $token = trim(str_replace("Bearer", '', $authHeader));
           
            $id = self::estaLogueado($token, $db);
            if($id != null){
                return $handler->handle($request->withAttribute('userID', $id)->withAttribute('db', $db));
            } else {
                $response = new \Slim\Psr7\Response();
                return ResponseJson::json($response, 401,['Status'=>'Unauthorized', 'token'=>'Tenes que estar logueado para hacer esta accion']);
            }
        } catch (PDOException $e){
            $response = new \Slim\Psr7\Response();
            return ResponseJson::dbError($response, $e);
        } 
    }

    public static function estaLogueado($token, $db){
        $datos = Usuario::obtenerTokenExpired($token, $db);

        if(!$datos){
            return null;
        } 
        $id = $datos['id'];
        $tokenExpire = $datos['token_expired_at'];
        $tiempoActual = date('Y-m-d H:i:s');
        if($tokenExpire > $tiempoActual){
            Usuario::actualizarToken($id, $db);
            return $id;
        }
        else{
            Usuario::borrarToken($id,$db);
            return null;
        }
    }
}