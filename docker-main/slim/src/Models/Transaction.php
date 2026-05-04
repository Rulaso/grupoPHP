<?php
class Transaction{
    public static function insertarVariacion($id, $assetID, $nuevoPrecio, $tiempoActual, $db){
        $db->query("INSERT INTO transactions (user_id, asset_id, transaction_type, quantity, price_per_unit, total_amount, transaction_date)
                            VALUES ($id, $assetID, 'buy', 0, $nuevoPrecio, 0, '$tiempoActual')");
    }
}