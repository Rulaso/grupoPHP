<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Helpers/ResponseJson.php';
require_once __DIR__ . '/../Models/DB.php';

class usuarioController
{
    public function create(Request $request, Response $response)
    {
        //Recibo los datos del body en $datos
        $datos = $request->getParsedBody();

        $username = trim($datos['username'] ?? '');
        $password = $datos['password'] ?? '';
        $nombre = trim($datos['nombre'] ?? '');

        //Validaciones
        //^ significa primero; {} significa minimo y maximo 
        if(empty($username) || !preg_match('/^@[a-zA-Z0-9]{5,12}$/', $username) ){
            return ResponseJson::json($response, 400, 
            ["status" => "Bad Request", 
            "username" => "El username debe empezar con @, permitiendo solo letras y numeros, entre 4-11 caracteres"]);         
        }
        //?=.* significa al menos; \d significa 0-9; \W_ significa caracter especial
        else if(empty($password) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/', $password)){
            return ResponseJson::json($response,400,
            ["status" => "Bad Request",
            "password" => "La contraseña debe contener 1 minuscula, 1 mayuscula, 1 numero y un caracter especial, entre 8-15 caracteres"]);
        }
        // /u es para que acepte tildes y Ñ
        else if(empty($nombre) || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{2,50}$/u', $nombre)){
            return ResponseJson::json($response,400,
            ["status" => "Bad Request",
            "name" => "El nombre solo puede contener letras y espacios, entre 2 y 50 caracteres"]);
        }
        //Valido username no usado
        else{
            try {
                $db = DB::getConnection();
                $resultado = Usuario::obtenerUsuario($username, $db);
                $dato = $resultado->fetchAll(PDO::FETCH_ASSOC);
                if($dato){
                    return ResponseJson::json($response,400,
                    ["status" => "Bad request", 
                    "username" => "El username ya se encuentra en uso"]);
                }

                // Creo usuario
                Usuario::crearUsuario($username, $password, $nombre, $db);
                return ResponseJson::json($response,200,
                ["status" => "OK", 
                "message" => "Usuario creado"]);
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
    public function getUsuarios(Request $request, Response $response, array $args)
    {

    }
}