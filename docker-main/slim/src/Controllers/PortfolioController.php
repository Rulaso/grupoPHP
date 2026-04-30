<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
class PortfolioController {
    public function portfolioUsuario(Request $request, Response $response, array $args){
        $userID = $request->getAttribute('userID');
        $db = $request->getAttribute('db');

        $datosPortfolio = $db->query("SELECT p.quantity, a.current_price, a.name, (p.quantity * a.current_price) AS total FROM portfolio p LEFT JOIN assets a ON a.id = p.asset_id 
                                    WHERE p.user_id = '$userID'")->fetchAll(PDO::FETCH_ASSOC);

        DB::closeConnection($db);
        if($datosPortfolio){
            $mensaje = ['Status'=> 'OK', 'message'=> 'Se recuperaron los datos requeridos', 'datos'=> $datosPortfolio];
            $response->getBody()->write(json_encode($mensaje));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $mensaje = ['Status'=> 'Not found', 'message'=> 'No se encontraron datos del usuario', 'datos'=> $datosPortfolio];
            $response->getBody()->write(json_encode($mensaje));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }

}