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
                DB::closeConnection($db);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            // Guardo los datos.
            $db->query("INSERT INTO users (email, password, name, balance, is_admin) 
            VALUES ('$email', '$password', '$name', 1000, 0)");
            $id = $db->lastInsertId();
            $token = User::crearToken($id, $db);
            $ok = ["status" => "OK", "message" => "Usuario creado", "token" => $token, "id" => $id];
            $response->getBody()->write(json_encode($ok));
            DB::closeConnection($db);
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
            $editorID = $request->getAttribute('userID');
            $datos = User::obtenerDatosDelUsuarioPorID($editorID, $db);
            if($datos){
                $admin = ($datos['is_admin']);
            } else {
                $admin = 0;
            }
            //verifico que sea admin o el mismo usuario
            if($admin == 0 && $editorID != $modificarID) {
                $error = ["status"=> "Bad request", "message"=> "No cuenta con los permisos para realizar esta accion"];
                $response->getBody()->write(json_encode($error));
                DB::closeConnection($db);
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
                DB::closeConnection($db);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }  else {
                    $db->query("UPDATE users SET password = '$password' WHERE id = '$modificarID'");
                }
            }
            //Si se envio un nombre, lo actualizo en la base de datos
            if(empty($name) || !preg_match('/^[a-zA-Z]+$/', $name)){
                $error = ['status'=> 'Bad request', 'message'=> 'El nombre ingresado no es valido'];
                $response->getBody()->write(json_encode($error));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            } else {

            }
            $db->query("UPDATE users SET name = '$name' WHERE id = '$modificarID'");
            //Envio el mensaje de 200 OK
            $mensaje = ["status"=> "OK", "message"=> "Los datos fueron actualizados"];
            $response->getBody()->write(json_encode($mensaje));
            DB::closeConnection($db);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    }

    public function getProfile(Request $request, Response $response, array $args)
    {
        //Recupero el id que viene por url
        $userId = $args['user_id'];
        //Si {user_id} no es un numero o esta vacio
        if(!is_numeric($userId)){
            $error = ["status" => "Bad Request", "message" => "Id invalido"];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        else{
            $id = $request->getAttribute('userID');
            $db = $request->getAttribute('db');
               
            if(User::esAdmin($id, $db) || $id == $userId){
                //Calculo el total del portfolio usando SUM(quantity).
                //Como busco por usuario, SUM puede no tener registros y me devuelve null, por lo que uso COALESCE para que me envie un 0 en vez de null.
                //La tabla principal es users y uno la tabla de portfolio usando la id de user y user_id de portfolio. 
                //Uso left join para traerme todos los usuarios aunque no tenga portfolio(que seria 0).
                //Uso where pq solo estoy buscando las de esa id en especifico.
                //Los agrupo por id para que cada usuario tenga un único resultado y poder calcular correctamente el total del portfolio con SUM, para que no queden los datos separados.
                //fetch es para que me devuelva solo uno, y me convierte ese objeto en un array.
                $userData = $db->query("SELECT u.name, u.balance, COALESCE(SUM(p.quantity*a.current_price), 0) AS total FROM users u 
                LEFT JOIN portfolio p ON u.id = p.user_id 
                LEFT JOIN assets a ON p.asset_id = a.id WHERE u.id = '$userId' GROUP BY u.id")->fetch(PDO::FETCH_ASSOC);
                $ok = ["status" => "OK", "user" => $userData];
                $response->getBody()->write(json_encode($ok));
                DB::closeConnection($db);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            }
            else{
                $error = ["status" => "Bad Request", "message" => "No tiene permisos"];
                $response->getBody()->write(json_encode($error));
                DB::closeConnection($db);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }
        }
    }

    public function getUsers(Request $request, Response $response){
        $id = $request->getAttribute('userID');
        $db = $request->getAttribute('db');    
        if(User::esAdmin($id, $db)){
        //Traigo los datos de la db y calculo el total
            $datosUser = $db->query("SELECT u.name, COALESCE(SUM(p.quantity*a.current_price),0) AS total FROM users u
            LEFT JOIN portfolio p ON u.id = p.user_id 
            LEFT JOIN assets a ON p.asset_id = a.id GROUP BY u.id")->fetchAll(PDO::FETCH_ASSOC);    // COALESCE                 
            $ok = ["status" => "OK", "data" => $datosUser];
            $response->getBody()->write(json_encode($ok));
            DB::closeConnection($db);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        else{
            $error = ["status" => "Bad Request", "message" => "No tiene permisos"];
            $response->getBody()->write(json_encode($error));
            DB::closeConnection($db);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }
    }
}