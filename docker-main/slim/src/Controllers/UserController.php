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
}
