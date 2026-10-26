<?php

class Mensaje{
    public static function buscarMensaje($mensajeID, $db){
        return $db->query("SELECT enviado_por FROM mensaje WHERE id = $mensajeID")->fetch(PDO::FETCH_ASSOC);
    }

    public static function eliminarMensaje($mensajeID, $db){
        $db->query("DELETE FROM mensaje WHERE id = $mensajeID");
    }

    public static function contarMensajes($chatID, $db){
        return $db->query("SELECT COUNT(*) FROM mensaje WHERE chat_id = $chatID")->fetchColumn();
    }

    public static function subirMensaje($mensaje, $creadoID, $chatID, $db){
        $db->query("INSERT INTO mensaje(chat_id, enviado_por, texto) VALUES ($chatID, $creadoID, '$mensaje')");
    }

    //devuelve el id del emisario del primer mensaje donde el chatID ingresado sea igual al chatID cargado en la db
    //devuelve el creador id del crador del chat
    public static function buscarCreador($userID, $chat_id, $db){

    }
}