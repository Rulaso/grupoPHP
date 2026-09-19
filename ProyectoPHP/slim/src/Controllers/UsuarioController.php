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
            "message" => "El username debe empezar con @, permitiendo solo letras y numeros, entre 4-11 caracteres"]);         
        }
        //?=.* significa al menos; \d significa 0-9; \W_ significa caracter especial
        else if(empty($password) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/', $password)){
            return ResponseJson::json($response,400,
            ["status" => "Bad Request",
            "message" => "La contraseña debe contener 1 minuscula, 1 mayuscula, 1 numero y un caracter especial, entre 8-15 caracteres"]);
        }
        // /u es para que acepte tildes y Ñ
        else if(empty($nombre) || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{2,50}$/u', $nombre)){
            return ResponseJson::json($response,400,
            ["status" => "Bad Request",
            "message" => "El nombre solo puede contener letras y espacios, entre 2 y 50 caracteres"]);
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
                    "message" => "El username ya se encuentra en uso"]);
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
        $db = $request->getAttribute('db');
        //Recupero el id que viene por url
        $userId = $args['user_id'];
        //Confirmo que sea un numero
        if(!is_numeric($userId)){
            DB::closeConnection($db);
            return ResponseJson::json($response,400,
            ["status"=> "Bad Request",
            "message"=> "Id invalido"]);
        }
        else{
            $id = $request->getAttribute('userID');
            try{
                if (Usuario::esAdmin($id, $db) || $id == $userId){
                    $userData = Usuario::obteneInfo($userId,$db);
                    return ResponseJson::json($response,200,$userData);
                }
                else{
                    return ResponseJson::json($response,401, 
                    ["status" => "Bad Request", 
                    "message" => "No tiene permisos"]);
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
    public function editarUsuario(Request $request, Response $response, array $args)
    {
        $db = $request->getAttribute('db');
        //Recupero el id que viene por url
        $userId = $args['user_id'];
        //Confirmo que sea un numero
        if(!is_numeric($userId)){
            DB::closeConnection($db);
            return ResponseJson::json($response,400,
            ["status"=> "Bad Request",
            "message"=> "Id invalido"]);
        }
        else{
            $id = $request->getAttribute('userID');
            $datos = $request->getParsedBody();
            try{
                if (Usuario::esAdmin($id, $db) || $id == $userId){
                    //Validar nombre
                    if (isset($datos['nombre'])) {
                        $nombre = trim($datos['nombre']);
                        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{2,50}$/u', $nombre)) {
                            return ResponseJson::json($response,400,
                            ["status" => "Bad Request",
                            "message" => "El nombre solo puede contener letras y espacios, entre 2 y 50 caracteres"]);
                        }
                    }

                    //Validar password
                    if (isset($datos['password'])) {
                        $password = $datos['password'];
                        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/', $password)) {
                            return ResponseJson::json($response,400,
                            ["status" => "Bad Request",
                            "message" => "La contraseña debe contener al menos 1 minúscula, 1 mayúscula, 1 número y 1 carácter especial, entre 8 y 15 caracteres"]);
                        }
                    }

                    //Validar es_publico
                    if (isset($datos['es_publico'])) {
                        $esPublico = $datos['es_publico'];
                        if ($esPublico != 0 && $esPublico != 1) {
                            return ResponseJson::json($response,400,
                            ["status" => "Bad Request",
                            "message" => "es_publico debe ser 0 o 1"]);
                        }
                    }
                    //Actualizo
                    if (isset($datos['nombre'])) {
                        Usuario::editarNombre($userId, $nombre, $db);
                    }
                    if (isset($datos['password'])) {
                        Usuario::editarPassword($userId, $password, $db);
                    }
                    if (isset($datos['es_publico'])) {
                        Usuario::editarPublico($userId, $esPublico, $db);
                    }
                    return ResponseJson::json($response,200,
                    ["status" => "OK",
                    "message" => "Los datos fueron actualizados"]);
                }
                else {
                    return ResponseJson::json($response,401, 
                    ["status" => "Bad Request", 
                    "message" => "No tiene permisos"]);
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
    public function eliminarUsuario(Request $request, Response $response, array $args)
    {
        $db = $request->getAttribute('db');
        //Recupero el id que viene por url
        $userId = $args['user_id'];
        //Confirmo que sea un numero
        if(!is_numeric($userId)){
            DB::closeConnection($db);
            return ResponseJson::json($response,400,
            ["status"=> "Bad Request",
            "message"=> "Id invalido"]);
        }
        else{
            $id = $request->getAttribute('userID');
            try{
                if (Usuario::esAdmin($id, $db)){
                    Usuario::eliminarUsuario($id,$db,$response);
                    return ResponseJson::json($response,200,
                    ["status" => "OK",
                    "message" => "Usuario eliminado"]);
                }
                else{
                    return ResponseJson::json($response,401, 
                    ["status" => "Bad Request", 
                    "message" => "No tiene permisos"]);
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