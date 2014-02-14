<?php

/**
 * Controller por defecto si no se usa el routes
 * 
 */
class IndexController extends AppController
{

    public function index(){
    	$configurer = new DatabaseConfigurer();
    	$this->db_configuracion = $configurer->getDbConfig();
    	if (Input::haspost("development","production")) {
    		if($configurer->escribir($_POST)){
    			Flash::valid("Cambios Realizados con éxito!");
    			Router::toAction("");
    		}
    	}
    }

}
