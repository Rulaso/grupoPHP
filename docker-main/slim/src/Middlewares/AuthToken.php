<?php

require_once __DIR__ . "/../Models/User.php";
require_once __DIR__ ."/../Models/DB.php";
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
        
                $error = ["Status"=>"Bad request", "message"=>"El usuario no se encuentra logueado"];
                $response->getBody()->write(json_encode($error));
                DB::closeConnection($db);
                return $response->withHeader("Content-Type", "application/json")->withStatus(400);
            }
        } catch (PDOException $e){
            $error = $e->getCode();
            if($error == 42000){
                $mensaje = ["Status" => "Internal Server Error", "Message"=>"Error de sintaxis en la base de datos"];
                $response->getBody()->write(json_encode($mensaje));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            } else {
                if($error == 28000){
                    $mensaje = ["Status"=> "Internal Server Error", "Message"=> "Error al consultar la base de datos, se te rebocaron los permisos"];
                    $response->getBody()->write(json_encode($mensaje));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
                } else {
                    $mensaje = ["Status"=>"Internal Server Error", "Message"=> "Error con la base de datos"];
                    $response->getBody()->write(json_encode($mensaje));
                    return $response->withHeader("Content-Type", "application/json")->withStatus(500);
                }
            } 
        } finally {
            if($db != null){
                DB::closeConnection($db);
            }
        }   
    }

    //funcion encargada de verificar la fecha de expiracion del token
    public static function estaLogueado($token, $db){
        //Verifico si el token existe en la db, y traigo el id de ese token.
        $datos = User::obtenerTokenExpired($token, $db);

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