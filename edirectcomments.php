<?php

require_once(dirname(__FILE__).'/classes/EdirectComment.php');

class EdirectComments extends Module
{
	public function __construct()
	{
		$this->name = 'edirectcomments';
		$this->tab = 'front_office_features';
		$this->version = '1.0';
		$this->author = 'Anouar Jlidi';
		$this->bootstrap = true;
		parent::__construct();
		$this->displayName = $this->l('eDirect Comment');
		$this->description = $this->l('Module pour commenter le produit');
	}

	public function install()
	{
		if (!parent::install())
			return false;

		$sql_file = dirname(__FILE__).'/install/install.sql';
		if (!$this->loadSQLFile($sql_file))
			return false;
		// Install admin tab
		if (!$this->installTab('AdminCatalog', 'AdminEdirectComments', 'eDirect Comments'))
			return false;

		// Register hooks
		if (!$this->registerHook('displayProductTabContent') ||
			!$this->registerHook('displayBackOfficeHeader') ||
			!$this->registerHook('displayAdminProductsExtra') ||
			!$this->registerHook('displayAdminCustomers') ||
			!$this->registerHook('ModuleRoutes'))
			return false;


		Configuration::updateValue('EDIRECT_GRADES', '1');
		Configuration::updateValue('EDIRECT_COMMENTS', '1');

	
		return true;
	}

	public function uninstall()
	{

		if (!parent::uninstall())
			return false;

		if (!$this->uninstallTab('AdminEdirectComments'))
			return false;
		Configuration::deleteByName('EDIRECT_GRADES');
		Configuration::deleteByName('EDIRECT_COMMENTS');
		return true;
	}

	public function loadSQLFile($sql_file)
	{
		$sql_content = file_get_contents($sql_file);

		$sql_content = str_replace('PREFIX_', _DB_PREFIX_, $sql_content);
		$sql_requests = preg_split("/;\s*[\r\n]+/", $sql_content);

		
		$result = true;
		foreach($sql_requests as $request)
			if (!empty($request))
				$result &= Db::getInstance()->execute(trim($request));

	
		return $result;
	}

	public function installTab($parent, $class_name, $name)
	{
		$tab = new Tab();
		$tab->id_parent = (int)Tab::getIdFromClassName($parent);
		$tab->name = array();
		foreach (Language::getLanguages(true) as $lang)
			$tab->name[$lang['id_lang']] = $name;
		$tab->class_name = $class_name;
		$tab->module = $this->name;
		$tab->active = 1;
		echo 'rrr';
		return $tab->add();
	}

	public function uninstallTab($class_name)
	{
		$id_tab = (int)Tab::getIdFromClassName($class_name);
		$tab = new Tab((int)$id_tab);
		return $tab->delete();
	}

	public function onClickOption($type, $href = false)
	{
		$confirm_reset = $this->l('si vous cliquer sur cet option comments seront supprimés de la base des données');
		$reset_callback = "return edirectcomments_reset('".addslashes($confirm_reset)."');";
		$matchType = array(
			'reset' => $reset_callback,
			'delete' => "return confirm('Confirm delete?')",
		);
		if (isset($matchType[$type]))
			return $matchType[$type];

		return '';
	}

	public function getHookController($hook_name)
	{
		require_once(dirname(__FILE__).'/controllers/hook/'. $hook_name.'.php');
		$controller_name = $this->name.$hook_name.'Controller';
		$controller = new $controller_name($this, __FILE__, $this->_path);
		return $controller;
	}

	public function hookDisplayProductTabContent($params)
	{
		$controller = $this->getHookController('displayProductTabContent');
		return $controller->run($params);
	}

	public function hookDisplayBackOfficeHeader($params)
	{
		$controller = $this->getHookController('displayBackOfficeHeader');
		return $controller->run($params);
	}

	public function hookDisplayAdminProductsExtra($params)
	{
		$controller = $this->getHookController('displayAdminProductsExtra');
		return $controller->run();
	}

	public function hookDisplayAdminCustomers($params)
	{
		$controller = $this->getHookController('displayAdminCustomers');
		return $controller->run();
	}

	public function hookModuleRoutes()
	{
		$controller = $this->getHookController('modulesRoutes');
		return $controller->run();
	}

	public function getContent()
	{
		$ajax_hook = Tools::getValue('ajax_hook');
		if ($ajax_hook != '')
		{
			$ajax_method = 'hook'.ucfirst($ajax_hook);
			if (method_exists($this, $ajax_method))
				die($this->{$ajax_method}(array()));
		}

		$controller = $this->getHookController('getContent');
		return $controller->run();
	}

	public function smartyGetCacheId($name = null)
	{
		return $this->getCacheId($name);
	}

	public function smartyClearCache($template, $cache_id = null, $compile_id = null)
	{
		return $this->_clearCache($template, $cache_id, $compile_id);
	}
}
