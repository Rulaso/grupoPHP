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

    //recupero si el usuario es admin o no
    public static function esAdmin($id, $db){
        $datos = $db->query("SELECT is_admin FROM users WHERE id = $id") ->fetch(PDO::FETCH_ASSOC);
        return $datos ['is_admin'];

    }

    //recupero el tiempo de expiracion y el id usando el token
    public static function obtenerTokenExpired($token, $db){
         $datos = $db->query("SELECT id, token_expired_at FROM users WHERE token = '$token'")->fetch(PDO::FETCH_ASSOC);
         return $datos;
    }

    //actualizo la contraseña 
    public static function editarPassword($id, $password, $db){
        $db->query("UPDATE users SET password = '$password' WHERE id = '$id'");
    }
    //actualizo el nombre
    public static function editarName($id, $name, $db){
        $db->query("UPDATE users SET name = '$name' WHERE id = '$id'");
    }
    
    public static function obtenerBalance($id, $db){
        $datos = $db->query("SELECT balance FROM users WHERE id = '$id'")->fetch(PDO::FETCH_ASSOC);
        return $datos;
    }
    
    public static function actualizarBalance($total, $id, $db){
        $db->query("UPDATE users SET balance = balance + $total WHERE id = $id");
    }

    public static function obtenerEmail($email, $db){
        $datos = $db->query("SELECT email FROM users WHERE email = '$email'");
        return $datos;
    }

    public static function crearUser($email, $password, $name, $db){
        $db->query("INSERT INTO users (email, password, name, balance, is_admin) 
            VALUES ('$email', '$password', '$name', 1000, 0)");
    }

    //Calculo el total del portfolio.
    //Agrupo por id para sumar todos los activos que tiene esa id en su portfolio
    public static function obtenerPerfil($userId, $db){
        $datos = $db->query("SELECT u.name, u.balance, COALESCE(SUM(p.quantity*a.current_price), 0) AS total FROM users u 
                LEFT JOIN portfolio p ON u.id = p.user_id 
                LEFT JOIN assets a ON p.asset_id = a.id WHERE u.id = '$userId' GROUP BY u.id")->fetch(PDO::FETCH_ASSOC);
        return $datos;
    }

    public static function obtenerInversores($db){
        $datos = $db->query("SELECT u.name, COALESCE(SUM(p.quantity*a.current_price),0) AS total FROM users u
            LEFT JOIN portfolio p ON u.id = p.user_id 
            LEFT JOIN assets a ON p.asset_id = a.id GROUP BY u.id")->fetchAll(PDO::FETCH_ASSOC);
        return $datos;
    }
}
