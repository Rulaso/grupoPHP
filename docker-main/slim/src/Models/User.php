<?php

// DB imported on index.php
// require_once __DIR__ . '/DB.php';

class User {
    // Get all users from the database
    public static function getAll()
    {
        $db = DB::getConnection();
        $stmt = $db->query("SELECT * FROM usuario");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //recupero el email, password e id del usuario
    public static function getLoginData($email, $password ,$db){
        $datos = $db->query("SELECT email, password, id FROM users WHERE email = '$email' and password = '$password'");
        return $datos->fetch(PDO::FETCH_ASSOC);
    }

    //creo y guardo el token
    public static function crearToken($id, $db){
        //genero el token
        $token = bin2hex(random_bytes(32));
        $tokenExpire = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        //guardo el token y su tiempo de expiracion en la base de datos utilizando el id como referencia
        $db->query("UPDATE users SET token = '$token', token_expired_at = '$tokenExpire' WHERE id = '$id'"); 
        return $token;
    }

    //Reestablesco la duracion del token en 5 minutos
    public static function updateToken($id, $db){
        $tokenExpire = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $db->query("UPDATE users SET token_expired_at = '$tokenExpire' WHERE id = '$id'");
    }

    //Borro el token y su tiempo de expiracion de la base de datos
        public static function deleteToken($id, $db){
        $db->query("UPDATE users SET token = NULL, token_expired_at = NULL WHERE id = '$id'");
    }
    //recibo un token y devuelvo el id del usuario y si el mismo es admin
    public static function obtenerUsuarioPorToken($token, $db){
        $datos = $db->query("SELECT id, is_admin FROM users WHERE token = '$token'");
        return $datos->fetch(PDO::FETCH_ASSOC);
    }


    //FUNCION DE PRUEBA PARA EL NUEVO MIDDLEWARE DE AUTHTOKEN
    //recupero si el usuario es admin o no
    public static function obtenerDatosDelUsuarioPorID($id, $db){
        $datos = $db->query("SELECT is_admin FROM users WHERE id = $id");
        return $datos->fetch(PDO::FETCH_ASSOC);
    }
}
