<?php

class Chat{
    public static function buscarChat($userID, $db){
        return $db->query("SELECT id,  usuario_id, esta_bloqueado FROM chat WHERE creado_por = $userID")->fetch(PDO::FETCH_ASSOC);
    }

    //crea un nuevo chat
    public static function crearChat($userID, $creadorID, $db){

    }

}