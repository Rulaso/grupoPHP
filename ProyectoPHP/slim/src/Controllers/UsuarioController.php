<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Helpers/ResponseJson.php';
require_once __DIR__ . '/../Models/DB.php';
require_once __DIR__ . '/../Models/Usuario.php';

class usuarioController
{
    public function create(Request $request, Response $response)
    {
        $datos = $request->getParsedBody();
        $username = trim($datos['username'] ?? '');
        $password = $datos['password'] ?? '';
        $nombre = trim($datos['nombre'] ?? '');

        if(empty($username) || !preg_match('/^@[a-zA-Z0-9]{4,11}$/', $username) ){
            return ResponseJson::json($response, 400, 
            ["status" => "Bad Request", 
            "username" => "El username debe empezar con @, permitiendo solo letras y numeros, entre 5-12 caracteres"]);         
        }
        else if(empty($password) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/', $password)){
            return ResponseJson::json($response,400,
            ["status" => "Bad Request",
            "password" => "La contraseña debe contener 1 minuscula, 1 mayuscula, 1 numero y un caracter especial, entre 8-15 caracteres"]);
        }
        else if(empty($nombre) || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{2,50}$/u', $nombre)){
            return ResponseJson::json($response,400,
            ["status" => "Bad Request",
            "nombre" => "El nombre solo puede contener letras y espacios, entre 2 y 50 caracteres"]);
        }
        else{
            try {
                $db = DB::getConnection();
                $resultado = Usuario::obtenerUsuario($username, $db);
                if($resultado){
                    return ResponseJson::json($response,400,
                    ["status" => "Bad request", 
                    "username" => "El username ya se encuentra en uso"]);
                }
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

    //busca usuarios segun parametros dados
    /*public function listarUsuarios(Request $request, Response $response){
        //recupero toda la informacion
        $userID = $request->getAttribute('useriD');
        $db = $request->getAttribute('db');
        $queryParams = $request->getQueryParams();
        $username = $queryParams['search'] ?? null;
        $orden = $queryParams['order'] ?? null;
        $limite = $queryParams['limit'] ?? null;
        $offset = $queryParams['offset'] ?? null;
        $esAdmin = null;
        //verifico que el usuario sea admin
        if($userID !=null){
            $esAdmin = Usuario::esAdmin($userID, $db);
        } 
        //hago la consulta a la base de datos y retorno los resultados
        $datos = Usuario::buscarUsuarios($userID, $esAdmin, $username, $orden, $limite, $offset, $db);
        return ResponseJson::json($response, 200,['Status'=>'OK','Message'=> $datos]);
    }*/
    public function listarUsuarios(Request $request, Response $response)
    {
        $userID = $request->getAttribute('userID');
        $db = $request->getAttribute('db');
        $queryParams = $request->getQueryParams();
        $username = $queryParams['search'] ?? null;
        $orden = $queryParams['order'] ?? 'asc';
        $limite = $queryParams['limit'] ?? null;
        $offset = $queryParams['offset'] ?? 0;
        $esAdmin = false;
        
        try{
            if($userID != null){
                $esAdmin = Usuario::esAdmin($userID, $db);
            }

            if(strtolower($orden) != 'asc' && strtolower($orden) != 'desc'){
                return ResponseJson::json($response, 400, [
                "status" => "Bad Request",
                "order" => "El orden debe ser asc o desc"]);
            }

            if(($limite !== null && (!ctype_digit((string)$limite) || (int)$limite < 1))
                || !ctype_digit((string)$offset)){
                return ResponseJson::json($response, 400, [
                "status" => "Bad Request",
                "message" => "Limit debe ser positivo y offset no puede ser negativo"]);
            }

            $datos = Usuario::buscarUsuarios($userID,$esAdmin,$username,$orden,$limite,$offset,$db);
            return ResponseJson::json($response, 200, [
                "status" => "OK",
                "total" => $datos['total'],
                "usuarios" => $datos['usuarios']]);
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


    public function getUsuarios(Request $request, Response $response, array $args)
    {
        $db = $request->getAttribute('db');
        $userId = $args['user_id'];
        if(!is_numeric($userId)){
            DB::closeConnection($db);
            return ResponseJson::json($response,400,
            ["status"=> "Bad Request",
            "user_id"=> "Id invalido"]);
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
                    "user_id" => "No tiene permisos"]);
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
    public function editarUsuario(Request $request, Response $response, array $args){
        $db = $request->getAttribute('db');
        $userId = $args['user_id'];

        if(!is_numeric($userId)){
            DB::closeConnection($db);
            return ResponseJson::json($response,400,
            ["status"=> "Bad Request",
            "user_id"=> "Id invalido"]);
        }
        else{
            $id = $request->getAttribute('userID');
            $datos = $request->getParsedBody();
            try{
                if (Usuario::esAdmin($id, $db) || $id == $userId){

                    if (isset($datos['nombre'])) {
                        $nombre = trim($datos['nombre']);
                        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{2,50}$/u', $nombre)) {
                            return ResponseJson::json($response,400,
                            ["status" => "Bad Request",
                            "nombre" => "El nombre solo puede contener letras y espacios, entre 2 y 50 caracteres"]);
                        }
                    }

                    if (isset($datos['password'])) {
                        $password = $datos['password'];
                        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,15}$/', $password)) {
                            return ResponseJson::json($response,400,
                            ["status" => "Bad Request",
                            "password" => "La contraseña debe contener al menos 1 minúscula, 1 mayúscula, 1 número y 1 carácter especial, entre 8 y 15 caracteres"]);
                        }
                    }

                    if (isset($datos['es_publico'])) {
                        $esPublico = $datos['es_publico'];
                        if ($esPublico != 0 && $esPublico != 1) {
                            return ResponseJson::json($response,400,
                            ["status" => "Bad Request",
                            "es_publico" => "es_publico debe ser 0 o 1"]);
                        }
                    }
                    
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
                    "user_id" => "No tiene permisos"]);
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
        $userId = $args['user_id'];
        if(!is_numeric($userId)){
            DB::closeConnection($db);
            return ResponseJson::json($response,400,
            ["status"=> "Bad Request",
            "user_id"=> "Id invalido"]);
        }
        else{
            $id = $request->getAttribute('userID');
            try{
                if (Usuario::esAdmin($id, $db)){
                    Usuario::eliminarUsuario($userId,$db,$response);
                    return ResponseJson::json($response,200,
                    ["status" => "OK",
                    "message" => "Usuario eliminado"]);
                }
                else{
                    return ResponseJson::json($response,401, 
                    ["status" => "Bad Request", 
                    "user_id" => "No tiene permisos"]);
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