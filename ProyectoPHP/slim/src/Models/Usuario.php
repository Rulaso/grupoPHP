<?php

class Usuario{
    public static function obtenerUsuario($usuario, $db){
        $datos = $db->query("SELECT username FROM usuario WHERE username = '$usuario'");
        return $datos;
    }

    public static function crearUsuario($usuario, $password, $nombre, $db){
        $db->query("INSERT INTO usuario (username, password, nombre, es_publico) 
            VALUES ('$usuario', '$password', '$nombre', 0)");
    }
}