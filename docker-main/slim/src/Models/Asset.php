<?php

class Asset{
    public static function obtenerAsset($db){
        //obtengo todos los id, current_price y last_update de la tabla
        $datos = $db->query("SELECT id, current_price, last_update FROM assets")->fetchAll(PDO::FETCH_ASSOC);
        return $datos;
    }

    public static function actualizarAsset($id, $precio, $lastUpdate, $db){
        //actualizo los valores de current_price y last_update de un asset en especifico
        $db->query("UPDATE assets SET current_price = '$precio', last_update = '$lastUpdate' WHERE id = '$id'");
    }

}