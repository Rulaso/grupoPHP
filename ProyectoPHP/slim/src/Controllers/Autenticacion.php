<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Helpers/ResponseJson.php';
require_once __DIR__ . '/../Models/DB.php';
require_once __DIR__ . '/../Models/Usuario.php';

class Autenticacion{
    //MODELO EL INICIO DE SESION
    public static function login(Request $request, Response $response){
        //recupero los datos ingresados
        $datos = $request->getParsedBody();

        $username = trim($datos['username'] ?? '');
        $password = trim($datos['password'] ?? '');

        //verifico que el usuario haya ingresado un nombre
        if(empty($username)){
            return ResponseJson::json($response, 400,['status' => '400', 'username' => 'No se ingreso el nombre de usuario']);
        }
        //verifico que el usuario haya ingresado una contraseña
        if(empty($password)){
            return ResponseJson::json($response, 400,['Status' => 'Bad request', 'password' => 'No se ingreso la contraseña']);
        }
        try{
            $db = DB::getConnection();
            //verifico que el nombre de usuario exista
            if(!Usuario::obtenerUsuario($username, $db)){
                return ResponseJson::json($response, 400,['Status' => 'Bad request', 'username' => 'El nombre de usuario ingresado no corresponde a ningun usuario registrado']);
            } else {
                //recibo la contraseña hasehada de la base de datos
                $datos = Usuario::obtenerContraseña($username, $db);
                $passwordHasehada = $datos['password'];
                //password_verify es una funcion de PHP que verifica una contraseña hasehada con una no hasheada, devuelve true si son iguales
                //if(password_verify($password,$passwordHasehada)){
                if($passwordHasehada && $password == $passwordHasehada){
                    //si entro a este if significa que el usuario ingreso los datos correctos y se loguea 
                    $token = bin2hex(random_bytes(32));
                    $fecha = new DateTime;
                    $fecha->modify('+5 minutes');
                    $tokenExpired = $fecha->format('Y-m-d H:i:s');
                    Usuario::asignarToken($username, $token, $tokenExpired, $db);
                    return ResponseJson::json($response, 200,['Status' => 'OK', 'Message' => 'Sesion iniciada, el token es ' . $token]);
                }else {
                    //devuelvo el error por contraseña incorrecta
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

    public static function logout(Request $request, Response $response){
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