<?php

class Mensaje{
    public static function buscarMensaje($mensajeID, $db){
        return $db->query("SELECT enviado_por FROM mensaje WHERE id = $mensajeID")->fetch(PDO::FETCH_ASSOC);
    }

    public static function eliminarMensaje($mensajeID, $db){
        $db->query("DELETE FROM mensaje WHERE id = $mensajeID");
    }
}