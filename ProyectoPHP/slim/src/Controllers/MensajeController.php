<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../Helpers/ResponseJson.php';
require_once __DIR__ . '/../Models/DB.php';
require_once __DIR__ . '/../Models/Mensaje.php';

class MensajeController {

    public function delete(Request $request, Response $response, array $args) {
        $db = $request->getAttribute('db');
        $userID = $request->getAttribute('userID');
        $mensajeID = $args['message_id'];
        try {
            if (!is_numeric($mensajeID) || $mensajeID <= 0) {
                return ResponseJson::json($response, 400, [
                    'Status' => 'Bad request',
                    'message_id' => 'Id de mensaje invalido']);
            }
            $enviadoID = Mensaje::buscarMensaje($mensajeID, $db);
            if ($enviadoID) {
                if (Usuario::esAdmin($userID, $db)) {
                    Mensaje::eliminarMensaje($mensajeID, $db);
                    return ResponseJson::json($response, 200, [
                        'Status' => 'OK',
                        'Message' => 'El mensaje fue eliminado']);
                } else {
                    if ($enviadoID['enviado_por'] == $userID) {
                        Mensaje::eliminarMensaje($mensajeID, $db);
                        return ResponseJson::json($response, 200, [
                            'Status' => 'OK',
                            'Message' => 'El mensaje fue eliminado']);
                    } else {
                        return ResponseJson::json($response, 400, [
                            'Status' => 'Bad request',
                            'message_id' => 'El mensaje no pertenece al usuario que lo quiere eliminar']);
                    }
                }
            } else {
                return ResponseJson::json($response, 404, [
                    'Status' => 'Not Found',
                    'message_id' => 'El mensaje no existe']);
            }
        } catch (PDOException $e) {
            return ResponseJson::dbError($response, $e);
        } finally {
            DB::closeConnection($db);
        }
    }
}


