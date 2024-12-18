<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;

require_once "AccesoDatos.php";
require_once "autentificadora.php";



class MW{


    public function verificarTokenHeader(Request $request, RequestHandler $handler) : ResponseMW {

        $flag=false;
        $token = $request->getHeader("token")[0];
    
        $rta = Autentificadora::verificarJWT($token);
    
        if(!$rta->verificado){
            $newResponse = new ResponseMW;    
            $newResponse->withStatus(403);
            $newResponse->getBody()->write(json_encode(array("mensaje"=>$rta->mensaje)));
            return $newResponse;
            
        }else{
    
            $response = $handler->handle($request);
            return $response;
        }
        
    
    }





}