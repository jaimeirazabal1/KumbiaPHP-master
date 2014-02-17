
<?php 
/*
	modelo para las reglas
*/
class Regla extends ActiveRecord{
	public function getReglasDeUsuario($id){
		return $this->find("conditions: usuario_id = '$id'");
	}
	public function getRutas($id){
		$rutas = array();
		$permisos = $this->getReglasDeUsuario($id);
		$admin = Load::model("usuario")->find_first("columns: admin","conditions: id='$id'");
		foreach ($permisos as $key => $value) {

			$rutas[]=$value->url;
		}
		
		$rutas["admin"] = $admin->admin;
		return $rutas;
	}
	public function updatePermisos($idUsuario,$permisos,$admin){
		if ($idUsuario and $permisos) {
			if (Load::model("regla")->delete("usuario_id = '$idUsuario' ")) {
				if (!Load::model("usuario")->asignarAdmin($idUsuario,$admin)) {
					Flash::error("Falla al asignar el permiso de administrador!");
					return false;
				}
				return $this->crearPermisos($idUsuario,$permisos); 
			}else{
				Flash::error("Error Borrando los permisos anteriores del usuario $idUsuario");
			}

		}else{
			Flash::error("No se recibieron todos los parametros para establecer los permisos");
		}
	}
	public function crearPermisos($idUsuario,$permisos){
		
		foreach ($permisos as $key => $value) {
			$rule = new Regla();
		
			$rule->url = $key;
			$rule->usuario_id = $idUsuario;
			if (!$rule->save()) {
				Flash::error("Error guardando la regla $permisos[$i] del usuario numero $idUsuario");
				return false;
			}
		}
		
		return true;
	}		
}
?>