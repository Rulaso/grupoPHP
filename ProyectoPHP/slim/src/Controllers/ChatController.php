<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ChatController{
    public function listarChats(Request $request, Response $response){
        $db = $request->getAttribute('db');
        $userID = $request->getAttribute('userID');
        try{
            $chats = Chat::obtenerChats($userID,$db);
            return ResponseJson::json($response,200,$chats);
        }
        catch(PDOException $e){
            return ResponseJson::dbError($response,$e);
        }
        finally{
            if($db != null){
                DB::closeConnection($db);
            }
        }
    }
}