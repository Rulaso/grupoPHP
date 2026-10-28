<?php
require_once __DIR__ . '/../Helpers/ResponseJson.php';

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
        try{
            $db->query("DELETE FROM mensaje WHERE enviado_por = $id");
            $db->query("DELETE FROM chat WHERE creado_por = $id OR usuario_id = $id");
            $db->query("DELETE FROM usuario WHERE id = $id");
            $db->commit();
        }
        catch (PDOException $e){
            $db->rollBack();
            return ResponseJson::dbError($response, $e);
        }
    }

    public static function obtenerContraseña($username, $db){
        $datos = $db->query("SELECT password FROM usuario WHERE username = '$username'")->fetch(PDO::FETCH_ASSOC);
        return $datos;
    }

    public static function asignarToken($username, $token, $tokenExpired, $db){
        $db->query("UPDATE usuario SET token = '$token', token_expired_at = '$tokenExpired' WHERE username = '$username'");
    }

    public static function obtenerTokenExpired($token, $db){
        $datos = $db->query("SELECT id, token_expired_at FROM usuario WHERE token = '$token'")->fetch(PDO::FETCH_ASSOC);
        return $datos;
    }

    public static function actualizarToken($id, $db){
        $tokenExpire = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        $db->query("UPDATE usuario SET token_expired_at = '$tokenExpire' WHERE id = '$id'");
    }

    public static function borrarToken($id, $db){
        $db->query("UPDATE usuario SET token = NULL, token_expired_at = NULL WHERE id = '$id'");
    }

    public static function obtenerUsuarioPorToken($token, $db){
        $datos = $db->query("SELECT id, is_admin FROM usuario WHERE token = '$token'");
        return $datos->fetch(PDO::FETCH_ASSOC);
    }

    /*public static function buscarUsuarios($userID, $esAdmin, $username, $orden, $limite, $offset, $db){
    //consulta BASE
    $consulta = "SELECT u.id, u.nombre, u.es_publico, u.is_admin ";

    if($esAdmin){
        //Trae los datos pedidos en el caso de que el usuario sea admin
        $consulta .= ", (SELECT COUNT(*) FROM chat c WHERE c.creado_por = u.id OR c.usuario_id = u.id) AS cantidad_chats";
    }

    //Sirve para poder armar la consulta dinamica de forma segura
    $consulta .= " FROM usuario u WHERE 1=1 ";

    if($userID){
        //Si el usuario esta logueado lo excluye
        $consulta .= " AND u.id != '$userID' ";
    } else {
        //Si el usuario no esta logueado solo busca los publicos
        $consulta .= " AND u.es_publico = 1 ";
    }

    if($username){
        //Los % en el LIKE son para buscar parcialemnte 
        $consulta .= " AND (u.username LIKE '%$username%' OR u.nombre LIKE '%$username%') ";
    }

    $consulta .= " ORDER BY u.username ";

    //Se pasa a minusculas para normalizarlo y luego se verifica el tipo de orden
    if ($orden && strtolower($orden) == 'desc') {
        $consulta .= " DESC ";
    } else {
        $consulta .= " ASC ";
    }

    $datos = $db->query($consulta)->fetchAll(PDO::FETCH_ASSOC);
    return $datos;
        
    }*/
    public static function buscarUsuarios($userID, $esAdmin, $username, $orden, $limite, $offset, $db){
    // Consulta base
        $consulta = "SELECT u.id, u.nombre, u.es_publico, u.is_admin ";

        if($esAdmin){
            $consulta .= ", (
                SELECT COUNT(*) FROM chat c WHERE c.creado_por = u.id OR c.usuario_id = u.id) AS cantidad_chats";
        }

        $consulta .= " FROM usuario u WHERE 1=1 ";
        // Parametros para la consulta
        $parametros = [];
        if($userID != null){
            // Si esta logueado, excluyo al propio usuario
            $consulta .= " AND u.id != :userID ";
            $parametros['userID'] = $userID;
        }    
        else {
        // Sin usuario autenticado, solo perfiles publicos
            $consulta .= " AND u.es_publico = 1 ";
        }
        if($username !== null && $username !== ''){
            // Busqueda parcial por username o nombre
            $consulta .= " AND (u.username LIKE :search1 OR u.nombre LIKE :search2) ";
            $parametros['search1'] = '%' . $username . '%';
            $parametros['search2'] = '%' . $username . '%';
        }

        // Orden ascendente o descendente por username
        $orden = strtolower($orden) == 'desc' ? 'DESC' : 'ASC';
        $consulta .= " ORDER BY u.username " . $orden;

        // Preparo y ejecuto la consulta de usuarios
        $sentencia = $db->prepare($consulta);

        foreach($parametros as $clave => $valor){
            $sentencia->bindValue(':' . $clave, $valor);
        }

        // Aplico limit y offset si corresponde
        if($limite !== null){
            $consulta .= " LIMIT :limite OFFSET :offset ";
        } 
        elseif((int)$offset > 0){
            // MySQL permite offset sin un limite practico
            $consulta .= " LIMIT 18446744073709551615 OFFSET :offset ";
        }

        // Preparo de nuevo la consulta incluyendo la paginacion
        $sentencia = $db->prepare($consulta);

        foreach($parametros as $clave => $valor){
            $sentencia->bindValue(':' . $clave, $valor);
        }

        if($limite !== null){
            $sentencia->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
            $sentencia->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        } 
        elseif((int)$offset > 0){
            $sentencia->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        }

        $sentencia->execute();
        $datos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        // Consulta para calcular el total sin limit ni offset
        $consultaTotal = "SELECT COUNT(*) FROM usuario u WHERE 1=1 ";

        if($userID != null){
            $consultaTotal .= " AND u.id != :userID ";
        } 
        else {
            $consultaTotal .= " AND u.es_publico = 1 ";
        }

        if($username !== null && $username !== ''){
            $consultaTotal .= " AND (u.username LIKE :search1 OR u.nombre LIKE :search2) ";
        }

        $sentenciaTotal = $db->prepare($consultaTotal);

        foreach($parametros as $clave => $valor){
            $sentenciaTotal->bindValue(':' . $clave, $valor);
        }

        $sentenciaTotal->execute();
        $total = (int)$sentenciaTotal->fetchColumn();

        return [
            'usuarios' => $datos,
            'total' => $total];
    }

    public static function obtenerUsuarioPorID($enviadoID, $db){
        $datos = $db->query("SELECT username FROM usuario WHERE id = $enviadoID")->fetch(PDO::FETCH_ASSOC);
        return $datos;
    }
}