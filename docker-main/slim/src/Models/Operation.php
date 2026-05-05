<?php

class Operation{
    public static function crearTransaccion ($id, $assetId, $tipo, $quantity, $precioActual, $total, $tiempoActual, $db){
        $db->query("INSERT INTO transactions (user_id,asset_id,transaction_type,quantity,price_per_unit,total_amount,transaction_date)
                                VALUES ($id, $assetId, $tipo, $quantity, $precioActual, $total, '$tiempoActual')");
    }

    //Busco nombre desde assets, precio por unidad y dia de transaccion desde transactions.
    public static function obtenerHistorialPrecio($assetId, $quantity, $db){
        $datos = $db->query("SELECT a.name, t.price_per_unit, t.transaction_date FROM transactions t 
                INNER JOIN assets a ON t.asset_id = a.id WHERE t.asset_id = '$assetId' AND t.quantity = 0
                ORDER BY t.transaction_date DESC LIMIT $quantity")->fetchAll(PDO::FETCH_ASSOC);
        return $datos;
    }
}