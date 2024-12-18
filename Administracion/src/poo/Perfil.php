<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once "AccesoDatos.php";
require_once "autentificadora.php";


class Perfil{
        public int  $id;
        public string  $descripcion;
        public string  $estado;


        public function altaPerfil(Request $request, Response $response, array $args): Response 
        {
            $arrayDeParametros = $request->getParsedBody();
            $arrayDeParametros = json_decode($arrayDeParametros['perfil']);
    
            $descripcion= $arrayDeParametros->descripcion;
            $estado= $arrayDeParametros->estado;
    
            $perfil = new Perfil();
            $perfil->descripcion = $descripcion;
            $perfil->estado = $estado;
    
            $id = $perfil->agregarPerfilBD();
          
            if($id != false){
                $exito = true;
                $mensaje= "perfil agregado con exito";
                $status= 200;
            }else{
                $exito = false;
                $mensaje= "Ocurrio un error al agregar el perfil";
                $status= 418;
            }

            $response->getBody()->write(json_encode(array("exito" => $exito , "mensaje" => $mensaje, "status" =>$status )));
    
            $response->withStatus($status);

            return $response->withHeader('Content-Type', 'application/json');
        }
    
        public function agregarPerfilBD(){
            
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
            
            $consulta =$objetoAccesoDato->retornarConsulta("INSERT into perfiles (descripcion, estado)values(:descripcion, :estado)");
            
            $consulta->bindValue(':descripcion', $this->descripcion, PDO::PARAM_STR);
            $consulta->bindValue(':estado', $this->estado, PDO::PARAM_INT);
      
           
            if($consulta->execute()){
                return $objetoAccesoDato->retornarUltimoIdInsertado();
            }else{
                return false;
            }	
        }



        public function traerPerfiles(Request $request, Response $response, array $args) : Response {
        
            $datos = Perfil::traerTodos();
            if($datos != false){
                $exito=true;
                $status= 200;
                $mensaje= "Perfiles recuperados correctamente";
                $dato= $datos;
            }else{
                $exito=false;
                $status= 424;
                $mensaje= "Ocurrio un error al recuperar los perfiles";
                $dato= NULL;
    
            }
    
            $response->getBody()->write(json_encode(array("exito" => $exito, "mensaje" => $mensaje, "dato" =>$dato, "status" => $status)));
            $response->withStatus($status);

            return $response->withHeader('Content-Type', 'application/json');
        }
        
        public static function traerTodos(){
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
                
            $consulta = $objetoAccesoDato->retornarConsulta("SELECT * FROM perfiles");
    
            if($consulta->execute()){
                $datos = $consulta->fetchAll(PDO::FETCH_ASSOC);
                return $datos;
    
            }else{
                return false;
            }
        }

        public function eliminarPerfil(Request $request, Response $response, array $args): Response 
        {		 
            $id = $args['id_perfil'];

            $perfil = new Perfil();
            $perfil->id = $id;
                  
            $cantBorrados = $perfil->eliminarPerfilBD();
                 
            
            if($cantBorrados>=1){
                $mensaje = "Perfil eliminado exitosamente";
                $status=200;
                $exito=true;
                
            }else if($cantBorrados==0){
                $mensaje = "Ocurrio un error al eliminar el perfil";
                $status=418;
                $exito=false;
            }
    
            $response->getBody()->write(json_encode(array("exito"=>$exito,"mensaje"=>$mensaje, "status" => $status)));
            $response->withStatus($status);

            return $response->withHeader('Content-Type', 'application/json');
           
    

        }

        public function eliminarPerfilBD()
        {
             $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
            $consulta = $objetoAccesoDato->RetornarConsulta("delete from perfiles WHERE id=:id");	
            $consulta->bindValue(':id',$this->id, PDO::PARAM_INT);		
            if($consulta->execute()){
                return $consulta->rowCount();
            }else{
                return false;
            }
        }


        public function modificarPerfilBD($id)
        {
            $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
            $consulta = $objetoAccesoDato->retornarConsulta("
                    update perfiles
                    set descripcion=:descripcion,
                    estado=:estado
                    WHERE id=:id");
            $consulta->bindValue(':id',$id, PDO::PARAM_INT);
            $consulta->bindValue(':descripcion',$this->descripcion, PDO::PARAM_STR);
            $consulta->bindValue(':estado', $this->estado, PDO::PARAM_INT);

    
    
            if($consulta->execute()){
                return $consulta->rowCount();
            }else{
                return false;
            }
        }

        public function modificarPerfil(Request $request, Response $response, array $args): Response
        {
            $requestBody = $request->getBody()->getContents();
            $arrayDeParametros = json_decode($requestBody);
            $datos = $arrayDeParametros;
            $id = $args['id_perfil'];

            $perfil = new Perfil();
            $perfil->descripcion = $datos->descripcion;
            $perfil->estado = $datos->estado;
        
            $cant = $perfil->modificarperfilBD($id);
            if($cant >=1){
                $exito = true;
                $mensaje= "perfil modificado correctamente";
                $status = 200;
            }else{
                $exito = false;
                $mensaje= "Ocurrio un error al modificar el perfil";
                $status = 418;
            }
               
            $response->getBody()->write(json_encode(array("exito" => $exito , "mensaje" => $mensaje, "status" =>$status )));
            $response->withStatus($status);
            return $response->withHeader('Content-Type', 'application/json');
            

        }




}

