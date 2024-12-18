<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once "AccesoDatos.php";
require_once "autentificadora.php";


class Usuario{

        public string $correo;
        public string $clave;
        public string $nombre;
        public string  $apellido;
        public int  $id_perfil;
        public string  $foto;



    public function altaUsuario(Request $request, Response $response, array $args): Response 
	{
        $arrayDeParametros = $request->getParsedBody();
       
        $arrayDeParametros = json_decode($arrayDeParametros['usuario']);

        $correo= $arrayDeParametros->correo;
        $clave= $arrayDeParametros->clave;
        $nombre= $arrayDeParametros->nombre;
        $apellido= $arrayDeParametros->apellido;
        $id_perfil= $arrayDeParametros->id_perfil;

        $usuario = new Usuario();
        $usuario->correo = $correo;
        $usuario->clave = $clave;
        $usuario->nombre = $nombre;
        $usuario->apellido = $apellido;
        $usuario->id_perfil = $id_perfil;


        $id = $usuario->agregarUsuarioBD();
      
        if($id != false){
			$exito = true;
			$mensaje= "Usuario agregado con exito";
			$status= 200;
		}else{
			$exito = false;
			$mensaje= "Ocurrio un error al agregar el usuario";
			$status= 418;
		}

        $archivos = $request->getUploadedFiles();
        $destino = __DIR__ . "/../fotos/";

        $nombreAnterior = $archivos['foto']->getClientFilename();
        $extension = explode(".", $nombreAnterior);

        $extension = array_reverse($extension);

		$archivos['foto']->moveTo($destino .  $id ."_" . $apellido . "." . $extension[0]);

        $usuario->foto = $id . "_" . $apellido . "." . $extension[0];
        $usuario->agregarFotoBD($id);
	
        $response->withStatus($status);
        $response->getBody()->write(json_encode(array("exito" => $exito , "mensaje" => $mensaje, "status" =>$status )));

        return $response->withHeader('Content-Type', 'application/json');

    }

    public function agregarUsuarioBD(){
        $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
        
        $consulta =$objetoAccesoDato->retornarConsulta("INSERT into usuarios (correo, nombre, apellido, clave, id_perfil)values(:correo, :nombre, :apellido, :clave, :id_perfil)");
        
        $consulta->bindValue(':correo', $this->correo, PDO::PARAM_STR);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
        $consulta->bindValue(':clave', $this->clave, PDO::PARAM_STR);
        $consulta->bindValue(':id_perfil', $this->id_perfil, PDO::PARAM_STR);
       
        if($consulta->execute()){
			return $objetoAccesoDato->retornarUltimoIdInsertado();
		}else{
			return false;
		}	
    }
    public function agregarFotoBD(int $id){

        $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
        
        $consulta = $objetoAccesoDato->retornarConsulta("update usuarios set foto=:foto WHERE id=:id");
        $consulta->bindValue(':foto', $this->foto, PDO::PARAM_STR);
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);


        if($consulta->execute()){
			return $consulta->rowCount();
		}else{
			return false;
		}
    }

    public function traerUsuarios(Request $request, Response $response, array $args) : Response {
        
        $datos = Usuario::traerTodos();
        if($datos != false){
            $exito=true;
            $status= 200;
            $mensaje= "Usuarios recuperados correctamente";
            $dato= $datos;
        }else{
            $exito=false;
            $status= 424;
            $mensaje= "Ocurrio un error al recuperar los usuarios";
            $dato= NULL;

        }

        $response->withStatus($status);
        $response->getBody()->write(json_encode(array("exito" => $exito, "mensaje" => $mensaje, "dato" =>$dato, "status" => $status)));
        return $response->withHeader('Content-Type', 'application/json');

    }
    
    public static function traerTodos(){
        $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso();
            
        $consulta = $objetoAccesoDato->retornarConsulta("SELECT id,clave,correo,nombre,apellido,foto, id_perfil FROM usuarios");

        if($consulta->execute()){
            $datos = $consulta->fetchAll(PDO::FETCH_ASSOC);
            return $datos;

        }else{
            return false;
        }
    }


    public function retornarUsuario(Request $request, Response $response, array $args) : Response {
        $arrayDeParametros = $request->getParsedBody();
        $arrayDeParametros = json_decode($arrayDeParametros['usuario_json'],true);

        $correo = $arrayDeParametros['correo'];
        $clave = $arrayDeParametros['clave'];


        $usuarios = Usuario::traerTodos();

        foreach($usuarios as $user){
            if($user['correo'] == $correo && $user['clave'] == $clave){
                $mensaje= $user;
                $exito= true;
                $status = 200;
                $response->withStatus($status);
                $response->getBody()->write(json_encode(array("exito" => $exito, "datos" => $mensaje, "status" => $status)));
                return $response->withHeader('Content-Type', 'application/json');
            }
        }


        $mensaje= null;
                $exito= false;
                $status = 403;
        $response->getBody()->write(json_encode(array("exito" => $exito, "datos" => $mensaje, "status" => $status)));
        $response->withStatus($status);
        return $response->withHeader('Content-Type', 'application/json');

    }


    public function retornarJWT(Request $request, Response $response, array $args) : Response {
        $arrayDeParametros = $request->getParsedBody();
        $arrayDeParametros = json_decode($arrayDeParametros['user'],true);
        
        $correo = $arrayDeParametros['correo'];
        $clave = $arrayDeParametros['clave'];
        $user = new Usuario();
        $flag=false;
        $token=NULL;


        $usuarios = Usuario::traerTodos();

        foreach($usuarios as $u){
            if($u['correo'] == $correo && $u['clave'] == $clave){
                $user->correo = $u['correo'];
                $user->nombre = $u['nombre'];
                $user->apellido = $u['apellido'];
                $user->id_perfil = $u['id_perfil'];
                $user->foto = $u['foto'];
                $flag=true;
                break;
            }
        }
      
        if($flag){
            $token = Autentificadora::crearJWT($user, 45);

        }

        if($token != NULL){
            $exito=true;
            $jwt = $token  ;
            $status= 200;
        }else{
            $exito=false;
            $jwt = null  ;
            $status= 403;
        }

        $response->getBody()->write(json_encode(array("exito" => $exito, "jwt" => $jwt , "status" => $status)));
    
        $response->withStatus($status);

        return $response->withHeader('Content-Type', 'application/json');


    }
    
    public function verificarPorHeader(Request $request, Response $response, array $args) : Response {

        $token = $request->getHeader("token")[0];

        $obj_rta = Autentificadora::verificarJWT($token);

        if($obj_rta->verificado){

            $status = 200;
            $exito = true;
        }else{
            $status = 403;
            $exito = false;
        }

        $response->getBody()->write(json_encode(array("exito" => $exito, "status" => $status)));
        $response->withStatus($status);
        return $response->withHeader('Content-Type', 'application/json');
    }



    public function eliminarUsuario(Request $request, Response $response, array $args): Response 
    {		 

        $requestBody = $request->getBody()->getContents();
        $datos = json_decode($requestBody);
        $id= $datos->id_perfil;

        $usuario = new Usuario();
              
        $cantBorrados = $usuario->eliminarUsuarioBD($id);
             
        
        if($cantBorrados>=1){
            $mensaje = "Usuario eliminado exitosamente";
            $status=200;
            $exito=true;
            
        }else if($cantBorrados==0){
            $mensaje = "Ocurrio un error al eliminar el usuario";
            $status=418;
            $exito=false;
        }

        $response->getBody()->write(json_encode(array("exito"=>$exito,"mensaje"=>$mensaje, "status" => $status)));
        $response->withStatus($status);

        return $response->withHeader('Content-Type', 'application/json');
       


    }

    public function eliminarUsuarioBD(int $id)
    {
        $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
        $consulta = $objetoAccesoDato->RetornarConsulta("delete from usuarios WHERE id=:id");	
        $consulta->bindValue(':id',$id, PDO::PARAM_INT);		
        if($consulta->execute()){
            return $consulta->rowCount();
        }else{
            return false;
        }
    }


    public function modificarUsuarioBD($id)
    {
        $objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
        $consulta = $objetoAccesoDato->retornarConsulta("
                update usuarios
                set correo=:correo,
                clave=:clave,
                apellido=:apellido,
                nombre=:nombre,
                id_perfil=:id_perfil 
                WHERE id=:id");
        $consulta->bindValue(':id',$id, PDO::PARAM_INT);
        $consulta->bindValue(':correo',$this->correo, PDO::PARAM_STR);
        $consulta->bindValue(':clave',$this->clave, PDO::PARAM_STR);
        $consulta->bindValue(':apellido',$this->apellido, PDO::PARAM_STR);
        $consulta->bindValue(':nombre',$this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':id_perfil',$this->id_perfil, PDO::PARAM_STR);

        if($consulta->execute()){
            return $consulta->rowCount();
        }else{
            return false;
        }
    }

    public function modificarUsuario(Request $request, Response $response, array $args): Response
    {
        $arrayDeParametros = $request->getParsedBody();
        $datos = json_decode($arrayDeParametros['usuario']);

        $usuario = new Usuario();
        $usuario->correo = $datos->correo;
        $usuario->clave = $datos->clave;
        $usuario->nombre = $datos->nombre;
        $usuario->apellido = $datos->apellido;
        $usuario->id_perfil = $datos->id_perfil;
        //$usuario->foto = $datos->foto;
        $id = $datos->id;

        $cant = $usuario->modificarUsuarioBD($id);
        if($cant >=1){
            $exito = true;
            $mensaje= "usuario modificado correctamente";
            $status = 200;
        }else{
            $exito = false;
            $mensaje= "Ocurrio un error al modificar el usuario";
            $status = 418;
        }
           


        $archivos = $request->getUploadedFiles();
        $destino = __DIR__ . "/../fotos/";

        $nombreAnterior = $archivos['foto']->getClientFilename();
        $extension = explode(".", $nombreAnterior);

        $extension = array_reverse($extension);

		$archivos['foto']->moveTo($destino .  $id . "_" . "modificacion" . "_" . $usuario->apellido . "." . $extension[0]);

        $usuario->foto = $id . "_" . "modificacion" . "_" . $usuario->apellido . "." . $extension[0];
        $usuario->agregarFotoBD($id);

        $response->getBody()->write(json_encode(array("exito" => $exito , "mensaje" => $mensaje, "status" =>$status )));
        $response->withStatus($status);
        return $response->withHeader('Content-Type', 'application/json');
        

    }

}