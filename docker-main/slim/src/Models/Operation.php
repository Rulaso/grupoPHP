<?php

class Operation{
    //Valido asset_id y quantity
    public static function validarDatos($assetId, $quantity){ 
        //Si da error devuelvo un mensaje
        if (!is_numeric($assetId) || $assetId <= 0) {
            return "asset_id debe ser un entero positivo";
        }
        if (!is_numeric($quantity) || $quantity <= 0) {
            return "La cantidad debe ser un positivo mayor a 0";
        }
        return true;
    }
}