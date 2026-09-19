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

    public static function esAdmin($id, $db){
        $datos = $db->query("SELECT is_admin FROM usuario WHERE id = $id") ->fetch(PDO::FETCH_ASSOC);
        return $datos ['is_admin'];
    }

    public static function obteneInfo($userId, $db){
        $datos = $db->query("SELECT username, nombre, es_publico FROM usuario WHERE id = $userId")->fetch(PDO::FETCH_ASSOC);
        return $datos;
    }

    public static function editarNombre($id, $name, $db){
        $db->query("UPDATE usuario SET nombre = '$name' WHERE id = $id");
    }

    public static function editarPassword($id, $pass, $db){
        $db->query("UPDATE usuario SET password = '$pass' WHERE id = $id");
    }

    public static function editarPublico($id, $es_publico, $db){
        $db->query("UPDATE usuario SET es_publico = '$es_publico' WHERE id = $id");
    }

    public static function eliminarUsuario($id, $db, $response){
        $db->beginTransaction();
        // Transaction sirve para tratar todas las operaciones como una sola,puediendo volver todo atras si alguna falla
        try{
            //Elimino mensajes
            $db->query("DELETE FROM mensaje WHERE enviado_por = $id");
            //Elimino chats
            $db->query("DELETE FROM chat WHERE creado_por = $id OR usuario_id = $id");
            //Elimino usuario
            $db->query("DELETE FROM usuario WHERE id = $id");
            
            //OK
            $db->commit();
        }
        catch (PDOException $e){
            //Fallo = deshacer todo
            $db->rollBack();
            return ResponseJson::dbError($response, $e);
        }
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