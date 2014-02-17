
<?php 
/*
	modelo para los usuarios
*/
class Usuario extends ActiveRecord{
	public function initialize(){
	    $this->validates_uniqueness_of("nombre_usuario", "message: El nombre de usuario que intenta usar ya está usado, intenta con otro!");
	    $this->validates_uniqueness_of("cedula", "message: El número de cédula que intenta usar ya está usado, intenta con otro!");
	    $this->validates_numericality_of("cedula");
	    $this->validates_length_of("nombre",20);
   		$this->validates_length_of("apellido",20);
   		$this->validates_length_of("cedula",9);
   		$this->validates_length_of("clave",100);
   		$this->validates_length_of("nombre_usuario",20);
	}
	public function before_save(){
		$action = Router::get("action");
		if ($action == "nuevo") {
			$usuario = Input::post("usuario");
			if ($usuario["clave"] != $usuario["clave2"] ) {
				Flash::error("Error las claves no coinciden!");
				return "cancel";
			}else{
				$this->clave = md5($usuario["clave"]);
			}
		}
	}
	public function getUsuario($id){
		return $this->find($id);
	}
	public function getUsuarios(){
		return $this->find();
	}
	public function asignarAdmin($idUsuario,$admin){
		$usuario = $this->find_first($idUsuario);
		$usuario->admin = $admin ? $admin : 0;
		if (!$usuario->update()) {
			return false;
		}
		return true;
	}

}
?>