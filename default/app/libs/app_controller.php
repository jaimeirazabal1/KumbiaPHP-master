<?php
/**
 * @see Controller nuevo controller
 */
require_once CORE_PATH . 'kumbia/controller.php';

/**
 * Controlador principal que heredan los controladores
 *
 * Todas las controladores heredan de esta clase en un nivel superior
 * por lo tanto los metodos aqui definidos estan disponibles para
 * cualquier controlador.
 *
 * @category Kumbia
 * @package Controller
 */
class AppController extends Controller
{

    final protected function initialize()
    {
    	$controller = Router::get("controller");
    	$action = Router::get("action");
    	$url = $controller."/".$action;

    	if (Auth::is_valid()) {

    		if ($this->existaModeloReglaYMetodo()) {
    			$reglas = Load::model("regla")->getRutas(Auth::get("id"));
    			if ($this->permisosComunes($url) and !in_array($url, $reglas)) {
    				Flash::warning("Permiso Denegado!");
    				Router::redirect("usuario/perfil");
    			}
    		}else{

    			//Router::redirect("");
    		}
    	}else{
    		//si no esta autenticado y la vista es diferente a la de login y
    		//el controlador y la vista login existen
    		if ($url != 'usuario/login' and $this->exiteVistaYControladorLogin()) {
    			
    			Router::redirect("usuario/login");
    		}
    	}
    }

    final protected function finalize()
    {
        
    }
    /**
     * comprueba si existe el controlador de usuarios y la vista login
     * @return [type] [description]
     */
    public function exiteVistaYControladorLogin(){
    	if(file_exists(APP_PATH."controllers".DIRECTORY_SEPARATOR."usuario_controller.php") and 
    			file_exists(APP_PATH."views".DIRECTORY_SEPARATOR."usuario".DIRECTORY_SEPARATOR."login.phtml")){
    				return true;
    			}else{
    				return false;
    			}
    }
    /**
     * comprueba si existe el modelo regla y el metodo getRutas
     * @return [type] [description]
     */
    public function existaModeloReglaYMetodo(){
    	
    	if (file_exists(APP_PATH."models".DIRECTORY_SEPARATOR."regla.php")) {
    		if(method_exists(Load::model("regla"), "getRutas")){
    			return true;
    		}
    	}
    	return false;
    }
    public function permisosComunes($url){
    	if ($url != 'usuario/perfil' and $url != 'usuario/logout' and $url != 'usuario/login') {
    		return true;
    	}
    	return false;
    }
    
}
