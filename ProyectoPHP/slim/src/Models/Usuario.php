<?php

class Usuario{
    public static function obtenerUsuario($usuario, $db){
        $datos = $db->query("SELECT username FROM usuario WHERE username = '$usuario'")->fetch(PDO::FETCH_ASSOC);
        return $datos;
    }

    public static function crearUsuario($usuario, $password, $nombre, $db){
        $db->query("INSERT INTO usuario (username, password, nombre, es_publico) 
            VALUES ('$usuario', '$password', '$nombre', 0)");
    }

    /*public static function obtenerContraseña($username, $db){
        //prepara la consulta, con :nombre_buscado le indico que en ese lugar va a ir el dato a buscar
        $consulta = $db->prepare("SELECT password FROM usuario WHERE username = :nombre_buscado");
        //con execute indicas que lo que vas a enviar como dato si o si va a ser un string y no un comando de SQL
        $consulta->execute(['nombre_buscado' => $username]);
        //recibo el dato como un array asociativo
        $datos = $consulta->fetch(PDO::FETCH_ASSOC);
        //retorno en este caso la contraseña correspondiente a ese nombre de usuario
        return $datos;
    }*/

    //recupero la contraseña de la db
    public static function obtenerContraseña($username, $db){
        $datos = $db->query("SELECT password FROM usuario WHERE username = '$username'")->fetch(PDO::FETCH_ASSOC);
        return $datos;
    }

    //le asigno un token y una fecha de expiracion a un usuario logueado
    public static function asignarToken($username, $token, $tokenExpired, $db){
        $db->query("UPDATE usuario SET token = '$token', token_expired_at = '$tokenExpired' WHERE username = '$username'");
    }

}