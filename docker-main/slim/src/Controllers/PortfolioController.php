<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
class PortfolioController {
    public function portfolioUsuario(Request $request, Response $response){
        //recupero los datos del middleware
        $userID = $request->getAttribute('userID');
        $db = $request->getAttribute('db');
        
        //genero la consulta a la base de datos
        $datosPortfolio = $db->query("SELECT p.quantity, a.current_price, a.name, (p.quantity * a.current_price) AS total FROM portfolio p LEFT JOIN assets a ON a.id = p.asset_id 
                                    WHERE p.user_id = '$userID'")->fetchAll(PDO::FETCH_ASSOC);

        DB::closeConnection($db);
        //Si la base me trajo datos devuelvo un 200 OK con los datos recuperados
        if($datosPortfolio){
            $mensaje = ['Status'=> 'OK', 'message'=> 'Se recuperaron los datos requeridos', 'datos'=> $datosPortfolio];
            $response->getBody()->write(json_encode($mensaje));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            //Si la base no me trajo datos devuelvo un not found con un array vacio
            $mensaje = ['Status'=> 'OK ','message'=> 'No se encontraron datos del usuario', 'datos'=> $datosPortfolio];
            $response->getBody()->write(json_encode($mensaje));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
    }

    public function eliminarElemento (Request $request, Response $response, array $args){
        //recupero los datos del middleware
        $userID = $request->getAttribute('userID');
        $db = $request->getAttribute('db');
        //recupero los datos de la url
        $assetID = ($args['asset_id'] ?? '');

        //verifico que el assetID enviado este en el rango correcto
        if($assetID > 0 && $assetID < 8){
            //si esta, hago la consulta a la base de datos 
            $datos = $db->query("SELECT quantity FROM portfolio WHERE user_id = '$userID' AND asset_id = '$assetID'")->fetch(PDO::FETCH_ASSOC);
            if($datos) {
                //Si la base de datos me devolvio la cantidad, verifico que esta sea exactamente cero
                if($datos['quantity'] == 0){
                //genero la consulta a la base de datos y retorno un 200 OK
                $db->query("DELETE FROM portfolio WHERE asset_id = '$assetID' AND user_id = '$userID'");
                $message = ["Status"=> "OK", "message"=>"el asset se elimino correctamente del portfolio"];
                $response->getBody()->write(json_encode($message));
                DB::closeConnection($db);
                return $response->withHeader("Content-Type", "application/json")->withStatus(200);
                } else {
                    //devuelvo un 409 Conflict ya que no puedo eliminar esos registros
                    $message = ["Status"=>"Conflict", "message"=>"No puedes quitar un activo de tu portfolio si aún tienes unidades. Debes venderlas primero."];
                    $response->getBody()->write(json_encode($message));
                    DB::closeConnection($db);
                    return $response->withHeader("Content-Type", "application/json")->withStatus(409);
                }
            } else {
                //devuelvo un 404 Not found ya que el rango es correcto pero no encontre un registro asociado
                $message = ["Status"=>"Not found", "message"=> "No se encontro el asset en su portfolio"];
                $response->getBody()->write(json_encode($message));
                DB::closeConnection($db);
                return $response->withHeader("Content-Type", "application/json")->withStatus(404);
            }
        } else {
            //devuelvo un 400 Bad request ya que el rango de busqueda es incorrecto
            $message = ["Status"=>"Bad request", "message"=>"no existe un asset con el codigo enviado"];
            $response->getBody()->write(json_encode($message));
            DB::closeConnection($db);
            return $response->withHeader("Content-Type", "application/json")->withStatus(400);
        }
    }

    public function devolverTransacciones(Request $request, Response $response){
        //recupero los datos del middleware
        $userID = $request->getAttribute('userID');
        $db = $request->getAttribute('db');

        //recupero los datos de busqueda
        $datos = $request->getQueryParams();
        $tipo = ($datos['type'] ?? null);
        $assetID = ($datos['asset_id'] ?? null);

        //ejecuto una busqueda dinamica segun los filtros
        $datos = self::buscarTransaccion($userID, $tipo, $assetID, $db);
        //cierro la base de datos
        DB::closeConnection($db);

        //Si encontre resultados los envio junto a un 200 OK
        if($datos){
            $message = ["Status"=>"OK", "message"=> "Se encotnraron transacciones bajo esos filtros", "datos"=>$datos];
            $response->getBody()->write(json_encode($message));
            return $response->withHeader("Content-Type", "application/json")->withStatus(200);
        } else {
            //Si no encontre resultados envio un array vacio junto a un 404 Not found
            $message = ["Status"=>"Not found", "message"=> "No se encontraron datos bajo esos parametros de busqueda", "datos"=>$datos];
            $response->getBody()->write(json_encode($message));
            return $response->withHeader("Content-Type", "application/json")->withStatus(404);
        }

    }

    //Funcion encargada de generar la consulta dinamica a la base de datos
    private static function buscarTransaccion($userID, $tipo, $assetID, $db){
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
}