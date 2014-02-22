

<?php 
/*
	controller para las reglas
*/
class ReglaController extends AppController{
	
	public function permisos($idUsuario = null){
		if (Auth::is_valid() and !$idUsuario) {
			$idUsuario = Auth::get("id");
		}
		if ($idUsuario) {
			$config = Config::read("config");
	    	if (!$config["application"]["production"]){
				$o = new ControlDeUsuarios("development");
	    	}else{
	    		$o = new ControlDeUsuarios("production");
	    	}
	        $this->reglas = Load::model("regla")->getRutas($idUsuario);
	        $this->posibles_rutas = $o->getControllersMethods();
		}
		if (Input::haspost("permiso","admin") and $idUsuario) {
			if (Load::model("regla")->updatePermisos($idUsuario,Input::post("permiso"),Input::post("admin"))) {
				Flash::valid("Permisos Establecidos");
				Router::toAction("permisos/$idUsuario");
			}else{
				Flash::error("Error Estableciendo Permisos!");
			}
		}
	}
}
?>