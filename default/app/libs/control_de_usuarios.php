<?php 
/**
 * clase para crear tablas para control de usuario
 * y archivos php en controladores, modelos y vistas
 */
class ControlDeUsuarios extends Db{
	/**
	 * objeto que se crea con el factory de Db::factory
	 * @var [Db object]
	 */
	private $manager;
	/**
	 * booleano que dice si las tablas estan construidas o no
	 * @var [type]
	 */
	private $tablas_contruidas = false;

	public function __construct($db = "development"){
		$this->setManager($db);
		$this->tablas_contruidas = $this->encontroTablasUsuarioRegla();
	}
	/**
	 * retorna true si las tablas estas construidas, false si no
	 * @return [type]
	 */
	public function tablas_contruidas(){
		return $this->tablas_contruidas;
	}
	/**
	 * crea todo
	 * @param  [type] $db
	 * @return [type]
	 */
	public function crear($db){
		$this->setManager($db);
		return $this->crear_tabla_usuarios();
	}
	/**
	 * setea el objeto manejador de la base de datos
	 * @param [type] $db
	 */
	public function setManager($db){
		$this->manager = $this->factory($db);
	}
	/**
	 * retorna el objeto manejador de la base de datos
	 * @return [type]
	 */
	public function getManager(){
		return $this->manager;
	}
	/**
	 * creacion de tablas, script necesarios e insercion de admin
	 * @return [type]
	 */
	public function crear_tabla_usuarios(){
		if(!$this->manager->create_table("usuario",$this->getDefinicionUsuarios())){
			Flash::error("Error Creando la tabla Usuario");
			Flash::error("Linea: ". __LINE__);
			return false;
		}
		if(!$this->manager->create_table("regla",$this->getDefinicionReglas())){
			Flash::error("Error Creando la tabla Regla");
			Flash::error("Linea: ". __LINE__);
			return false;
		}
		$this->crearAdmin();
		
		$DS = DIRECTORY_SEPARATOR;
		$a = APP_PATH;

		if(!Util::crearArchivoPhp("w+",$a."models".$DS,"usuario",$this->getModeloUsuario())){
			Flash::error("Linea: ". __LINE__);
			return false;
		}
		if(!Util::crearArchivoPhp("w+",$a."models".$DS,"regla",$this->getModeloRegla())){
			Flash::error("Linea: ". __LINE__);
			return false;
		}
		if(!Util::crearArchivoPhp("w+",$a."controllers".$DS,"usuario_controller",$this->getControllerUsuario())){
			Flash::error("Linea: ". __LINE__);
			return false;
		}
		if(!Util::crearArchivoPhp("w+",$a."controllers".$DS,"regla_controller",$this->getControllerRegla())){
			Flash::error("Linea: ". __LINE__);
			return false;
		}
		if(!$this->crearVistas()){
			Flash::error("Linea: ". __LINE__);
			return false;
		}
		if (!$this->insertarReglasAdmin()) {
			Flash::error("Linea: ". __LINE__);
			return false;
		}
		return true;
	}
	/**
	 * define la tabla usuario
	 * @return [type]
	 */
	public function getDefinicionUsuarios(){
		$userDef = array(
				"id"=>array("not_null"=>1,"primary"=>1,"auto"=>1,"type"=>"int"),
				"nombre"=>array("not_null"=>1,"type"=>"varchar","size"=>20),
				"apellido"=>array("not_null"=>1,"type"=>"varchar","size"=>20),
				"cedula"=>array("not_null"=>1,"type"=>"varchar","size"=>9),
				"nombre_usuario"=>array("not_null"=>1,"type"=>"varchar","size"=>20),
				"clave"=>array("not_null"=>1,"type"=>"varchar","size"=>100),
				"admin"=>array("type"=>"int")
			);
		return $userDef;
	}
	/**
	 * refine la tabla regla
	 * @return [type]
	 */
	public function getDefinicionReglas(){
		$reglasDef = array(
				"id"=>array("not_null"=>1,"primary"=>1,"auto"=>1,"type"=>"int"),
				"url"=>array("not_null"=>1,"type"=>"varchar","size"=>100),
				"usuario_id"=>array("not_null"=>1,"type"=>"int"),
				"created_at"=>array("not_null"=>1,"type"=>"timestamp")
			);
		return $reglasDef;
	}
	/**
	 * crea un usuario administrador y lo inserta en la base de datos
	 * con usuario: admin
	 *     clave  : admin
	 * @return [type]
	 */
	public function crearAdmin(){
		$clave = md5("admin");
		$sql = "INSERT INTO usuario (id,nombre,apellido,cedula,nombre_usuario,clave,admin)
				VALUES ('1','admin','admin','1','admin','$clave','1')";
		if ($this->manager->query($sql)) {
			return true;
		}else{
			return false;
		}
	}
	/**
	 * comprueba si las tablas de usuario y regla estan credas
	 * @return [type]
	 */
	public function encontroTablasUsuarioRegla(){
		$no_encontro = 0;
		$tablas = $this->manager->list_tables();
		for ($i=0; $i <count($tablas) ; $i++) { 
			if (gettype(array_search("usuario",$tablas[$i]))!="integer" and 
				gettype(array_search("regla",$tablas[$i]))!="integer") {

				$no_encontro = 1;
			}
		}
		return !$no_encontro;
	}
	/**
	 * retorna el modelo de usuario
	 * @return [type]
	 */
	public function getModeloUsuario(){
		$modelo = '
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
?>';
		return $modelo;
	}
	/**
	 * retorna el modelo de regla
	 * @return [type]
	 */
	public function getModeloRegla(){
		$modelo = '
<?php 
/*
	modelo para las reglas
*/
class Regla extends ActiveRecord{
	public function getReglasDeUsuario($id){
		return $this->find("conditions: usuario_id = \'$id\'");
	}
	public function getRutas($id){
		$rutas = array();
		$permisos = $this->getReglasDeUsuario($id);
		$admin = Load::model("usuario")->find_first("columns: admin","conditions: id=\'$id\'");
		foreach ($permisos as $key => $value) {

			$rutas[]=$value->url;
		}
		
		$rutas["admin"] = $admin->admin;
		return $rutas;
	}
	public function updatePermisos($idUsuario,$permisos,$admin){
		if ($idUsuario and $permisos) {
			if (Load::model("regla")->delete("usuario_id = \'$idUsuario\' ")) {
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
?>';
		return $modelo;
	}
	/**
	 * retorna el controller de regla
	 * @return [type]
	 */
	public function getControllerRegla(){
		$controller = '
<?php 
/*
	controller para las reglas
*/
class ReglaController extends AppController{
	
	public function permisos($idUsuario = null){

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
?>';
		return $controller;
	}
	/**
	 * retorna el el controller de usuario
	 * @return [type]
	 */
	public function getControllerUsuario(){
		$controller = '
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
?>';
		return $controller;
	}

	/**
	 * crea los directorios donde van las vistas de los usuarios
	 * @return [type]
	 */
	public function crearVistas(){
		if (!Util::crearDirectorio(APP_PATH."views".DIRECTORY_SEPARATOR."usuario".DIRECTORY_SEPARATOR)) {
			Flash::error("No se pudo crear el directorio de vistas de usuario");
			return false;
		}
		if (!Util::crearDirectorio(APP_PATH."views".DIRECTORY_SEPARATOR."regla".DIRECTORY_SEPARATOR)) {
			Flash::error("No se pudo crear el directorio de vistas de reglas");
			return false;
		}
		if (!$this->crearVistaUsuario()) {
			return false;
		}
		if (!$this->crearVistaRegla()) {
			return false;
		}
		return true;
	}
	/**
	 * [crearVistaUsuario crear la vistas de usuario]
	 * @return [type]
	 */
	public function crearVistaUsuario(){
		if (!Util::crearArchivoPhp("w+",APP_PATH."views".DIRECTORY_SEPARATOR."usuario".DIRECTORY_SEPARATOR,"login",$this->getLoginString(),"phtml")) {
			Flash::error("No se pudo crear la vista de usuario 'login'");
			return false;
		}
		if (!Util::crearArchivoPhp("w+",APP_PATH."views".DIRECTORY_SEPARATOR."usuario".DIRECTORY_SEPARATOR,"perfil",$this->getPerfilString(),"phtml")) {
			Flash::error("No se pudo crear la vista de usuario 'perfil'");
			return false;
		}
		if (!Util::crearArchivoPhp("w+",APP_PATH."views".DIRECTORY_SEPARATOR."usuario".DIRECTORY_SEPARATOR,"nuevo",$this->getNuevoUsuarioString(),"phtml")) {
			Flash::error("No se pudo crear la vista de usuario 'nuevo'");
			return false;
		}
		if (!Util::crearArchivoPhp("w+",APP_PATH."views".DIRECTORY_SEPARATOR."usuario".DIRECTORY_SEPARATOR,"index",$this->getUsuarioIndex(),"phtml")) {
			Flash::error("No se pudo crear la vista de usuario 'index'");
			return false;
		}
		return true;
	}
	public function crearVistaRegla(){
		if (!Util::crearArchivoPhp("w+",APP_PATH."views".DIRECTORY_SEPARATOR."regla".DIRECTORY_SEPARATOR,"permisos",$this->getPermisosString(),"phtml")) {
			Flash::error("No se pudo crear la vista de reglas 'permisos'");
			return false;
		}
		return true;
	}
	/**
	 * retorna la vista del login
	 * @return [type]
	 */
	public function getLoginString(){
		return '
<?php View::content()?>
<center class="page-header">
	<h2>Ingreso al Sistema</h2>
	<h2><small>Introduzca su nombre de usuario y contraseña</small></h2>
</center>
<?php echo Form::open("","POST","class=\"form-horizontal\" role=\"form\" ")?>

  <div class="form-group">
    <label for="Nombre de Usuario" class="col-sm-2 control-label">Nombre de Usuario</label>
    <div class="col-sm-10">
      <input type="text" maxlength="20" name="nombre_usuario" class="form-control" id="nombre_usuario" placeholder="Nombre de Usuario">
    </div>
  </div>
  <div class="form-group">
    <label for="Contraseña" class="col-sm-2 control-label">Contraseña</label>
    <div class="col-sm-10">
      <input type="password" class="form-control" id="clave" name="clave" placeholder="Contraseña">
    </div>
  </div>
  <div class="form-group">
    <div class="col-sm-offset-2 col-sm-10">
    	<?php echo Form::submit("Entrar","class=\"btn btn-primary\" ")?>
    </div>
  </div>
<?php echo Form::close()?>
<br>
<?php if(Auth::is_valid()):?>
	<?php echo Html::link("usuario/perfil/".Auth::get("id"),"Mi Perfil","class=\"btn btn-primary center-block\" " ) ?>
	<?php if (Auth::get("admin")==1): ?>
		<?php echo Html::link("usuario/nuevo/","Nuevo Usuario","class=\"btn btn-primary center-block\" " ) ?>
	<?php endif;?>
<?php endif; ?>
		';
	}
	/**
	 * retorna la vista del perfil
	 * @return [type]
	 */
	public function getPerfilString(){
		return '
<?php View::content()?>
<?php if(!empty($usuario)):?>
	
	<?php if (!empty($reglas)): ?>
			<div class="btn-group pull-left">
			  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
			    Mis Sitios <span class="caret"></span>
			  </button>
			  <ul class="dropdown-menu" role="menu">
				
			  <?php foreach ($reglas as $key => $value): ?>
			    <?php if ($key!="admin"): ?>
			    	<li><?php echo Html::link($value,$value) ?></li>
			  	<?php endif ?>
			  <?php endforeach ?>
			  </ul>
			</div>
	<?php endif ?>
	<div class="clearfix"></div>

	<center >
		<h3><?php echo ucfirst($usuario->nombre)." ".ucfirst($usuario->apellido) ?></h3>
	</center>
	
	<div class="container">
		<div class="row">
		  <div class="col-md-4"><b>Número de Cédula</b></div>
		  <div class="col-md-4"><?php echo $usuario->cedula ?></div>
		</div>
		<div class="row">
		  <div class="col-md-4"><b>Nombre de Usuario</b></div>
		  <div class="col-md-4"><?php echo $usuario->nombre_usuario ?></div>
		</div>
		<div class="row">
		  <div class="col-md-4"><b>Usuario Administrador</b></div>
		  <?php if($usuario->admin == 1): ?>
		  	<div class="col-md-4"><?php echo "Es Administrador" ?></div>
		  <?php else: ?>
		  	<div class="col-md-4"><?php echo "No posee privilegios de Administrador" ?></div>
		  <?php endif;?>
		</div>
		<br>
		<?php echo Html::link("usuario/logout","Salir del Sistema","class=\"btn btn-warning center-block\" ")?>
		<div class="clearfix"></div>
	</div>
<?php endif;?>
		';
	}
	public function getNuevoUsuarioString(){
		return '
<?php View::content()?>
<center>
		<h3>Nuevo Usuario</h3>
		<h2><small>Introducir datos del nuevo usuario</small></h2>
</center>
<?php echo Form::open("","POST"," class=\"form-horizontal\" role=\"form\" ")?>
  <div class="form-group">
    <label for="nombre" class="col-sm-2 control-label">Nombre</label>
    <div class="col-sm-10">
      <input type="text" maxlength="20" class="form-control" name="usuario[nombre]" id="nombre" placeholder="Nombre">
    </div>
  </div>
  <div class="form-group">
    <label for="apellido" class="col-sm-2 control-label">Apellido</label>
    <div class="col-sm-10">
      <input type="text" maxlength="20" class="form-control" name="usuario[apellido]" id="apellido" placeholder="Apellido">
    </div>
  </div>
  <div class="form-group">
    <label for="cedula" class="col-sm-2 control-label">Cédula</label>
    <div class="col-sm-10">
      <input type="text" maxlength="9" class="form-control" name="usuario[cedula]" id="cedula" placeholder="Cédula">
    </div>
  </div>
  <div class="form-group">
    <label for="nombre_usuario" class="col-sm-2 control-label">Nombre de Usuario</label>
    <div class="col-sm-10">
      <input type="text" maxlength="20" class="form-control" name="usuario[nombre_usuario]" id="nombre_usuario" placeholder="Nombre de Usuario">
    </div>
  </div>
  
    <div class="form-inline">
    <label for="nombre_usuario" class="col-sm-2 control-label">Contraseña</label>
   
      <input type="password" class="form-control col-sm-3" name="usuario[clave]" id="clave" placeholder="Contraseña">
      <input type="password" class="form-control col-sm-3" name="usuario[clave2]" id="clave2" placeholder="Repetir Contraseña">
    </div>
  </div>
  <br>
  <div class="form-group">
    <div class="col-sm-offset-2 col-sm-20 ">
      <button type="submit" class="btn btn-primary pull-rigth">Crear</button>
      <?php echo Html::link("usuario/login","Atras","class=\"btn btn-default\" pull-rigth")?>
    </div>
  </div>
</form>
	';
	}
	public function getUsuarioIndex(){
		return '
<?php View::content() ?>
<center>
	<h2>Lista de Usuarios</h2>
</center>
<br>
<?php if (!empty($usuarios)): ?>
	<?php foreach ($usuarios as $key => $value): ?>
		<div class="container well">
			<div class="row">
			  <div class="col-md-4"><b>Nombre de Usuario</b></div>
			  <div class="col-md-4"><?php echo $value->nombre_usuario ?><br><?php echo Html::link("regla/permisos/".$value->id,"Permisos") ?>&nbsp;|&nbsp;<?php echo Html::link("usuario/perfil/$value->id","Perfil") ?></div>
			</div>
			<div class="row">
			  <div class="col-md-4"><b>Nombre Completo</b></div>
			  <div class="col-md-4"><?php echo $value->nombre." ".$value->apellido ?></div>
			</div>
			<div class="row">
			  <div class="col-md-4"><b>Cédula</b></div>
			  <div class="col-md-4"><?php echo $value->cedula ?></div>
			</div>
		</div>
	<?php endforeach ?>
<?php echo Html::link("usuario/nuevo","Nuevo Usuario","class=\'btn btn-primary center-block\'") ?>
<?php endif ?>
		';
	}
	public function getPermisosString(){
		return '
<?php View::content() ?>
<center>
	<h2>Permisos</h2>
</center>
<br>
<?php // public static function check($field, $checkValue, $attrs = NULL, $checked = NULL) ?>
<div class="list-group">
<?php echo Form::open() ?>
	<div class="checkbox">
		<b>Administrador</b>
		<?php if ($reglas["admin"]): ?>
			<div style="display:inline;padding-top:50px"><?php echo Form::check("admin",1,null,1) ?></div>	
		<?php else: ?>
			<div style="display:inline"><?php echo Form::check("admin",1) ?></div>	
		<?php endif ?>
	</div>
<?php if (!empty($posibles_rutas)): ?>
	<ul class="list-group">
	<?php foreach ($posibles_rutas as $key => $value): ?>
		<?php foreach ($value as $k => $v): ?>
			 <a class="list-group-item">
				<?php $str = substr(strtolower($key), 0, -10)."/".$v ?>
				<span class="badge" style="background:white;">
				<?php if (in_array($str, $reglas)): ?>
					<div class="col"><?php echo Form::check("permiso[".$str."]",1,null,1) ?></div>
				<?php else: ?>
					<div class="col"><?php echo Form::check("permiso[".$str."]",1) ?></div>
				<?php endif; ?>
				</span>
				<b><?php echo $str ?></b><!--ruta -->
			</a>
		<?php endforeach ?>
	<?php endforeach ?>
	</ul>
</div>
<div class="form-group">
	<?php echo Form::submit("Aceptar","class=\"pull-right btn btn-success\" ") ?>
	<?php echo Html::link("usuario/","Lista de Usuarios","class=\"btn btn-default pull-right\" ") ?>

	<div class="clearfix"></div>
</div>
<?php echo Form::close() ?>
<?php endif ?>
		';
	}
	public function validarUsuario(){
		return $this->getControllersMethods();
	}
	public function getControllers(){
		$path = APP_PATH."controllers".DIRECTORY_SEPARATOR;
		return Util::getArchivos($path,array());
	}
	public function getClassControllersNames(){
		$controllers_name = $this->getControllers();
		for ($i=0; $i <count($controllers_name) ; $i++) { 
			if (file_exists(APP_PATH."controllers".DIRECTORY_SEPARATOR.$controllers_name[$i])) {
				include_once(APP_PATH."controllers".DIRECTORY_SEPARATOR.$controllers_name[$i]);
				$name = Util::camelcase($controllers_name[$i]);
				$name = explode(".", $name);
				$controllers_name[$i] = $name[0];
			}
		}

		return $controllers_name;
	}
	public function getControllersMethods(){
		$controllerClass = $this->getClassControllersNames();
		$controllers_methods = array();
		try {
			for ($i=0; $i <count($controllerClass) ; $i++) { 
			$controllers_methods[$controllerClass[$i]] = array_diff(get_class_methods($controllerClass[$i]),
			array("initialize","finalize","__construct","before_filter","after_filter","k_callback"));
			}
		} catch (Exception $e) {
			Flash::error($e->getMessage());
		}
		return $controllers_methods;
	}
	public function getPermisosBasicos(){
		return 	array(
			'index/index',
			'index/control_usuario', 
			'pages/show', 
			'regla/permisos', 
			'usuario/index', 
			'usuario/nuevo', 
			'usuario/login', 
			'usuario/logout', 
			'usuario/perfil'
			);

	}
	public function insertarReglasAdmin(){
		$permisos = $this->getPermisosBasicos();
		for ($i=0; $i <count($permisos) ; $i++) { 
			$regla = Load::model("regla");
			$regla->url = $permisos[$i];
			$regla->usuario_id = 1;
			if(!$regla->save()){
				return false;
			}
		}
		return true;
	}
	
}
?>