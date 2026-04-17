<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Models/DB.php';
require_once __DIR__ . '/../Models/User.php';
class loginController {

public function login(Request $request, Response $response, array $args){
    //recibo los datos en $datos
    $datos = $request->getParsedBody();
    //Guardo el email y contraseña en sus respectivas varaibles
    $email = trim($datos['email'] ?? '');
    $password = trim($datos['password'] ?? '');
    if(empty($email) || empty($password)){
        //Si falta email o contraseña envio un Bad request
        $error = ["status" => "Bad request", "message" => "Falto ingresar email o contraseña, debe completar todos los campos"];        //<-creo el mensaje
        $response->getBody()->write(json_encode($error));       
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);      //<-envio el json con el Bad request
    } else {
        $db = DB::getConnection();

        $datos = User::getLoginData($email, $password, $db); //<- Busco y recupero email, password e id del usuario (si es que existe)
        
        //verifico que la variable datos contenga los datos del usuario. Si el mismo no existe en la base de datos $datos = false                                                                                                                                       
        if($datos){ 
            
            //me guardo el id en una variable
            $id = $datos['id'];
        
            $token = User::crearToken($id, $db); //<- Creo el token en la base de datos utulizando el id del usuario

            $cumple = ["status" => "200 OK", "message" => "Se completo existosamente el login", "token" => $token, "id" => $id];    //<-creo el mensaje
            $response->getBody()->write(json_encode($cumple)); 
            $db =  null; 
            return $response->withHeader("Content-Type", "application/json")->withStatus(200);      //<-envio el json con el OK
        } else {
            //Si no cumplen con un usuario existente envio un Bad request
            $error = ["Status" => "400 Bad request", "message" => "El email o contraseña no coinciden con un usuario existente"];       //<-creo el mensaje
            $response->getBody()->write(json_encode($error));       
            $db =  null;
            return $response->withHeader("Content-Type", "application/json")->withStatus(400);      //<-envio el json con el Bad request
        }
    }
}

public function logout(Request $request, Response $response){
    //recupero los datos enviados por el body
    $datos = $request->getParsedBody();
    $token = trim($datos['token'] ?? '');
    //si no hay datos devuelvo un error 400
    if(empty($token)){
        $error = ["status " => "400 Bad request", "message" => "Necesitas estar logueado para hacer esta accion"];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader("Content-Type", "application/json")->withStatus(400);
    } else {
        $db = DB::getConnection();
        //recupero el token de $datos
        User::deleteToken($token, $db);  //<- utilizo deleteToken para borrar el token 

        //devuelvo codigo 200
        $exito = ["status" => "200 OK", "message"=> "Se deslogueo correctamente"];
        $response->getBody()->write(json_encode($exito));
        $db =  null;
        return $response->withHeader("Content-Type", "application/json")->withStatus(200);
        }
}
}