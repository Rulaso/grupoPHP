<?php

class Portfolio{

    public static function eliminarElementoPorIDs($userID,$assetID, $db){
        $datos = $db->query("DELETE FROM portfolio WHERE asset_id = '$assetID' AND user_id = '$userID'");
        return $datos;
    }
    public static function devolverPortfolioUsuario($userID, $db){      
        //Devuelvo la cantidad de assets comprados, el precio y nombre del asset, y el precio total del valor para ese asset en el portfolio del usuario (POR CADA UNO DE LOS ASSETS)
        $datos = $db->query("SELECT p.quantity, a.current_price, a.name, (p.quantity * a.current_price) AS total FROM portfolio p LEFT JOIN assets a ON a.id = p.asset_id 
                                    WHERE p.user_id = '$userID'")->fetchAll(PDO::FETCH_ASSOC);
        return $datos;
    }

    public static function buscarTransaccion($userID, $tipo, $assetID, $db){
        //Genero la primer consulta sin filtros de busqueda
        $consulta = "SELECT asset_id, transaction_type, quantity, price_per_unit, total_amount, transaction_date FROM transactions WHERE user_id = '$userID'";

        //Si el usuario envio algun tipo lo añado a la busqueda
        if($tipo){
            $consulta .= " AND transaction_type = '$tipo'";
        }
        //Si el usuario envio algun asset ID lo agrego a la busqueda
        if($assetID){
            $consulta .= " AND asset_id = '$assetID'";
        }

        //Agrego a la busqueda que los elementos deben venir ordenados por fecha
        $consulta .= " ORDER BY transaction_date";

        //genero la consulta y retorno el resultado en un array asociativo
        $datos = $db->query($consulta)->fetchAll(PDO::FETCH_ASSOC);
        return $datos;
    }
    public static function obtenerPortfolio($id, $assetId, $db){
        $datos = $db->query("SELECT * FROM portfolio WHERE user_id = $id AND asset_id = $assetId")->fetch(PDO::FETCH_ASSOC);
        return $datos;
    }
    public static function actualizarQuantity($quantity, $id, $assetId, $db){
        $db->query("UPDATE portfolio SET quantity = quantity + $quantity WHERE user_id = $id AND asset_id = $assetId ");
    }
    public static function crearPortfolio($id,$assetId,$quantity,$db){
        $db->query("INSERT INTO portfolio (user_id,asset_id,quantity) 
            VALUES ($id, $assetId, $quantity)");
    }
    public static function obtenerQuantity($id,$assetId,$db){
        $datos = $db->query("SELECT quantity FROM portfolio WHERE user_id = $id AND asset_id = $assetId")->fetch(PDO::FETCH_ASSOC);
        return $datos;
    }
    public static function borrarAssetVacio($id,$assetId,$db){
        $db->query("DELETE FROM portfolio WHERE user_id = $id AND asset_id = $assetId AND quantity = 0");
    }
}