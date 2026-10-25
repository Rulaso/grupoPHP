<?php
require_once __DIR__ . '/../Helpers/ResponseJson.php';


class Chat{
    public static function obtenerChats($userID, $db){
        $datos = $db->query("SELECT c.id, c.nombre, c.descripción, c.esta_bloqueado, 
        c.creado_por, c.usuario_id,    
        CASE WHEN c.creado_por = '$userID' THEN u2.nombre ELSE u1.nombre
        END AS nombre_otro_usuario 
        FROM chat c
        INNER JOIN usuario u1 ON u1.id = c.creado_por
        INNER JOIN usuario u2 ON u2.id = c.usuario_id
        LEFT JOIN mensaje m ON m.id = 
        (SELECT m2.id FROM mensaje m2 WHERE m2.chat_id = c.id 
        ORDER BY m2.created_at DESC, m2.id DESC LIMIT 1)
        WHERE c.creado_por = '$userID' OR c.usuario_id = '$userID'
        ORDER BY m.created_at IS NULL ASC, m.created_at DESC")->fetchAll(PDO::FETCH_ASSOC); 
        //IS NULL me da 1 si es nulo, por eso ordeno asc, asi tengo los no nulos arribo, y cuando tengo
        //los no nulos, los acomodo por cuando se crearon.
        return $datos;
    }

    public static function obtenerIdChat($myID, $user_id, $db){
        $id = $db->query("SELECT id FROM chat
        WHERE (creado_por = $myID AND usuario_id = $user_id) OR
        (creado_por = $user_id AND usuario_id = $myID)")->fetch(PDO::FETCH_ASSOC);
        
        if($id){
            return $id['id'];
        }
        else{
            return false;
        }
    }

    public static function editarNombre($chatID,$db,$nuevo_nombre){
        $db->query("UPDATE chat SET nombre = '$nuevo_nombre' WHERE id = $chatID");
    }

    public static function editarDescripcion($chatID,$db,$descripcion){
        $db->query("UPDATE chat SET descripcion = '$descripcion' WHERE id = $chatID");
    }

    public static function editarColor($chatID,$db,$color){
        $db->query("UPDATE chat SET color = '$color' WHERE id = $chatID");
    }

    public static function editarBloqueo($chatID,$db,$esta_bloqueado){
        if($esta_bloqueado){
            $db->query("UPDATE chat SET esta_bloqueado = $esta_bloqueado WHERE id = $chatID");
            return true;
        }
        else{
            $bloqueo = $db->query("SELECT esta_bloqueado FROM chat WHERE id = $chatID");
            if($bloqueo){
                return false; // Solo devuelvo false como un error para decir que no se puede desbloquear
            }
            return true;
        }
    }

}