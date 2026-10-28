<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Helpers/ResponseJson.php';
require_once __DIR__ . '/../Models/DB.php';
require_once __DIR__ . '/../Models/Usuario.php';

class Autenticacion{
    public function login(Request $request, Response $response){
        $datos = $request->getParsedBody();
        $username = trim($datos['username'] ?? '');
        $password = trim($datos['password'] ?? '');

        if(empty($username)){
            return ResponseJson::json($response, 400,['status' => '400', 'username' => 'No se ingreso el nombre de usuario']);
        }
        if(empty($password)){
            return ResponseJson::json($response, 400,['Status' => 'Bad request', 'password' => 'No se ingreso la contraseña']);
        }
        try{
            $db = DB::getConnection();
            if(!Usuario::obtenerUsuario($username, $db)){
                return ResponseJson::json($response, 400,['Status' => 'Bad request', 'username' => 'El nombre de usuario ingresado no corresponde a ningun usuario registrado']);
            } else {
                $datos = Usuario::obtenerContraseña($username, $db);
                $passwordHasehada = $datos['password'];
                if($passwordHasehada && $password == $passwordHasehada){
                    $token = bin2hex(random_bytes(32));
                    $fecha = new DateTime;
                    $fecha->modify('+5 minutes');
                    $tokenExpired = $fecha->format('Y-m-d H:i:s');
                    Usuario::asignarToken($username, $token, $tokenExpired, $db);
                    return ResponseJson::json($response, 200,['Status' => 'OK', 'Message' => 'Sesion iniciada, el token es ' . $token]);
                }else {
                    return ResponseJson::json($response, 400,['Status' => 'Bad request', 'password' => 'La contraseña ingresada no coincide para el usuario ']);
                }
            }
        } catch (\PDOException $e){
            return ResponseJson::dbError($response, $e);
        } finally {
            if($db != null){
                DB::closeConnection($db);
            }
        }
    }

    public function logout(Request $request, Response $response){
        $id = $request->getAttribute('userID');
        $db = $request->getAttribute('db');

        try{
            Usuario::borrarToken($id, $db);
            return ResponseJson::json($response, 200,['Status' => 'OK', 'Message' => 'La sesion se cerro con exito']);
        } catch (\PDOException $e){
            return ResponseJson::dbError($response, $e);
        }
    }
}