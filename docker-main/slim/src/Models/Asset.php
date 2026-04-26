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


    public static function buscarDinamico($nombre, $min, $max, $db){
        //Crea una consulta generica 
        $consulta = "SELECT name, current_price FROM assets WHERE 1=1";

        //Si existe un nombre lo agrega como parte de la consulta
        if($nombre){
            $consulta .= " AND name = '$nombre'";
        }
        //Si existe un minimo lo agrega como parte de la consulta
        if($min){
            $consulta .= " AND current_price >= '$min'";
        }
        //Si existe un maximo lo agrega como parte de la consulta
        if($max){
            $consulta .= " AND current_price <= '$max'";
        }

        //efectua la consulta y retorna los resultados obtenidos en un array asociativo 
        $datos = $db->query($consulta)->fetchAll(PDO::FETCH_ASSOC);
        return $datos;
    }

}