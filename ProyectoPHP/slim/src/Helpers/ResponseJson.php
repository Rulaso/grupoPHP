<?php
class ResponseJson{
    public static function json($response, int $codigo, array $datos){
        $response->getBody()->write(json_encode($datos));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($codigo);
    }

    public static function dbError($response, PDOException $e)
    {
        $codigoError = $e->getCode();
                switch ($codigoError) {
            case '42000':
                $mensaje = "Error de sintaxis en la base de datos";
                break;
            case '28000':
                $mensaje = "Error al consultar la base de datos se te revocaron los permisos";
                break;
            default:
                $mensaje = "Error con la base de datos";
                break;
        }

        // Reutilizo el método json
        return self::json($response, 500, [
            "status" => "Internal Server Error",
            "message" => $mensaje
        ]);
    }
}