
<?php 
/*
	controller los usuarios
*/
class UsuarioController extends AppController{
	public function index(){
		$this->usuarios = Load::model("usuario")->getUsuarios();
	}
	public function nuevo(){
		if (Input::haspost("usuario")) {
			$usuario = Load::model("usuario",Input::post("usuario"));
			if ($usuario->save()) {
				Flash::valid("Usuario Creado!");
				Router::redirect("usuario/login");
			}else{
				Flash::error("Usuario no creado!");
				$this->usuario = $usuario;
			}
		}
	}
	public function login(){
		
        if (Input::hasPost("nombre_usuario","clave")){
            $pwd = md5(Input::post("clave"));
            $usuario=Input::post("nombre_usuario");

            $auth = new Auth("model", "class: usuario", "nombre_usuario: $usuario", "clave: $pwd");
            if ($auth->authenticate()) {
                Router::redirect("usuario/perfil");
            } else {
                Flash::error("Nombre de Usuario o Contraseña incorrectos");
            }
        }
    }
    public function logout(){
    	Auth::destroy_identity();
    	Router::redirect("usuario/login");
    }
    public function perfil($id = null){
		if ($id) {
			$this->usuario = Load::model("usuario")->getUsuario($id);
		}else{
			if (Auth::is_valid()) {
				$this->usuario = Load::model("usuario")->getUsuario(Auth::get("id"));
				$this->reglas = Load::model("regla")->getRutas(Auth::get("id"));
			}else{
				Flash::info("No posee una autenticación en el sistema");
				Router::redirect("usuario/login");
			}
		}
    }				
}
?>