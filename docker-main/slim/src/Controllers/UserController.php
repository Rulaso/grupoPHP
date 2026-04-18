<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Models/DB.php';
require_once __DIR__ . '/../Models/User.php';

class userController
{
    public function create(Request $request, Response $response)
    {
        //Recibo los datos en $datos
        $datos = $request->getParsedBody();

        $email = trim($datos['email'] ?? '');
        $password = trim($datos['password'] ?? '');
        $name = trim($datos['name'] ?? '');
        //Esto lo que hace es tomar el valor valor y sino hay, lo deja en vacio. El trim elimina espacios, para evitar que tome como correcto '         '.

        //Validaciones
        if (empty($email) || !preg_match('/@/', $email)) {
            $error = ["status" => "Bad request", "message" => "Email invalido"];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } else if (
            empty($password) || strlen($password) < 8 || !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W]/', $password)
        ) {
            $error = ["status" => "Bad request", "message" => "La contraseña no cumple los requisitos"];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } else if (empty($name) || !preg_match('/^[a-zA-Z]+$/', $name)) {
            $error = ["status" => "Bad request", "message" => "Nombre invalido, solo letras"];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        // Valido mail no usado
        else {
            $db = DB::getConnection();
            $resultado = $db->query("SELECT email FROM users WHERE email = '$email'");
            $dato = $resultado->fetchAll(PDO::FETCH_ASSOC);
            if ($dato) {
                $error = ["status" => "Bad request", "message" => "El email ya se encuentra en uso"];
                $response->getBody()->write(json_encode($error));
                $db =  null;
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            // Guardo los datos.
            $db->query("INSERT INTO users (email, password, name, balance, is_admin) 
        VALUES ('$email', '$password', '$name', 1000, 0)");
            $id = $db->lastInsertId();
            User::crearToken($id, $db);
            $ok = ["status" => "OK", "message" => "Usuario creado"];
            $response->getBody()->write(json_encode($ok));
            $db =  null;
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    }

    public function editar(Request $request, Response $response, array $args){
        //recupero el id que viene por url
        $modificarID = ($args['user_id'] ?? '');
        //verifico que haya enviado un id
        if(!$modificarID) {
            $error = ["status" => "Bad request", "message" => "No se reconoce como usuario"];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader("Content-Type", "application/json")->withStatus(400);
        } else {
            //abro la base de datos
            $db = DB::getConnection();
            //recupero el token desde el header
            $authHeader = $request->getHeaderLine('Authorization');
            $token = trim(str_replace('Bearer', '', $authHeader));

            //verifico que se haya enviado token y que este corresponda a un usuario
            if($token) {
                $datos = User::obtenerUsuarioPorToken($token, $db);
                if($datos) {
                    $editorID = ($datos['id'] ?? '');
                    $admin = ($datos['is_admin']);
                } else {
                    $editorID = null;
                }
            } else {
                $error = ["status"=> "Bad request", "message"=> "El usuario debe estar logueado"];
                $response->getBody()->write(json_encode($error));
                return $response->withHeader("Content-Type", "application/json")->withStatus(400);
            }

            //verifico que el id enviado exista dentro de la base de datos
            if($editorID){
                //Verifico que el usuario este logueado 
                if(!User::estaLogueado($editorID, $db)){
                    $error = ["status"=> "Bad request", "message"=> "El usuario debe estar logueado para realizar modificaciones"];
                    $response->getBody()->write(json_encode($error));
                    return $response->withHeader("Content-Type", "application/json")->withStatus(400);
                }
                //verifico que sea admin o el mismo usuario
                if(!$admin && $editorID != $modificarID) {
                    $error = ["status"=> "Bad request", "message"=> "No cuenta con los permisos para realizar esta accion"];
                    $response->getBody()->write(json_encode($error));
                    $db = null;
                    return $response->withHeader("Content-Type", "application/json")->withStatus(400);
                }

                //recupero la nueva contraseña y el nuevo nombre del body
                $datos = $request->getParsedBody();
                $password = ($datos['password'] ?? '');
                $name = ($datos['name'] ?? '');

                //en el caso de que se haya enviado contraseña, verifico que esta sea valida
                if($password){
                    if ( strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) 
                        || !preg_match('/[0-9]/', $password) || !preg_match('/[\W]/', $password)) {
                    $error = ["status" => "Bad request", "message" => "La nueva contraseña no cumple los requisitos"];
                    $response->getBody()->write(json_encode($error));
                    $db = null;
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                    }  else {
                        $db->query("UPDATE users SET password = '$password' WHERE id = '$modificarID'");
                    }
                }
                //Si se envio un nombre, lo actualizo en la base de datos
                if($name){
                    $db->query("UPDATE users SET name = '$name' WHERE id = '$modificarID'");
                }

                //Envio el mensaje de 200 OK
                $mensaje = ["status"=> "OK", "message"=> "Los datos fueron actualizados"];
                $response->getBody()->write(json_encode($mensaje));
                $db = null;
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);


            } else {
                //Si el usuario que solicita la edicion no existe dentro de la base de datos, retorno un error 400
                $error = ["status"=> "Bad request", "message"=> "El id de usuario no correponde con ninguno dentro de la base de datos"];
                $response->getBody()->write(json_encode($error));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

    }
}
