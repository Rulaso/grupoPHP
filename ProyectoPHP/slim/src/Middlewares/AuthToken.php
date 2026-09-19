<?php

require_once __DIR__ . "/../Models/Usuario.php";
require_once __DIR__ ."/../Models/DB.php";
require_once __DIR__ . "/../Helpers/ResponseJson.php";
class AuthToken{
    public function __invoke($request, $handler){
        $db = null;
        
        try {
            //Abro la coneccion a la base de datos
            $db = DB::getConnection();

            //recupero el token desde el header
            $authHeader = $request->getHeaderLine("Authorization");
            $token = trim(str_replace("Bearer", '', $authHeader));
           
            //Llamo a la funcion esta logueado
            $id = self::estaLogueado($token, $db);
            //Si esta logueado, continuo con la ejecucion y envio el id del usuario que envio el token 
            if($id != null){
                return $handler->handle($request->withAttribute('userID', $id)->withAttribute('db', $db));
            } else {
                //Si no esta logueado creo el objeto response y envio la respuesta a postman 
                $response = new \Slim\Psr7\Response();
                return ResponseJson::json($response, 400,['Status'=>'Bad request', 'message'=>'Tenes que estar logueado para hacer esta accion']);
            }
        } catch (PDOException $e){
             return ResponseJson::dbError($response, $e);
        } finally {
            if($db != null){
                DB::closeConnection($db);
            }
        }   
    }

    //funcion encargada de verificar la fecha de expiracion del token
    public static function estaLogueado($token, $db){
        //Verifico si el token existe en la db, y traigo el id de ese token.
        $datos = Usuario::obtenerTokenExpired($token, $db);

        if(!$datos){
            return null;
        } 
        $id = $datos['id'];
        $tokenExpire = $datos['token_expired_at'];
        $tiempoActual = date('Y-m-d H:i:s');
        //Si todavia no expiro..
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