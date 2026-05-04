<?php

class Operation{
    public static function crearTransaccion ($id, $assetId, $tipo, $quantity, $precioActual, $total, $tiempoActual, $db){
        $db->query("INSERT INTO transactions (user_id,asset_id,transaction_type,quantity,price_per_unit,total_amount,transaction_date)
                                VALUES ($id, $assetId, $tipo, $quantity, $precioActual, $total, '$tiempoActual')");
    }
}