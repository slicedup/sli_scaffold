<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\util;

use lithium\core\Libraries;
use lithium\net\http\Media;
use lithium\util\Inflector;

class Scaffold extends \lithium\core\StaticObject {

	protected static $_config = array(
		'all' => true,
		'model' => array(),
		'controller' => array()
	);

	protected static $_actions = array(
		'index',
		'view',
		'add',
		'edit',
		'delete'
	);

	protected static $_scaffold = array();

	protected static $_classes = array(
		'controller' => '\slicedup_scaffold\controllers\ScaffoldController',
		'model' => '\slicedup_scaffold\models\ScaffoldModel'
	);

	/**
	 * Configure the Scaffold class
	 *
	 * @param unknown_type $config
	 */
	public static function config($config = array()){
		if (is_bool($config)) {
			$config = array('all' => $config);
		}
		if ($config) {
			if (isset($config['scaffold'])) {
				static::set($config['scaffold']);
			}
			unset($config['scaffold']);
			static::$_config = $config + static::$_config;
		}
		return static::$_config + array('scaffold' => static::$_scaffold);
	}

	/**
	 * Set a Scaffold config
	 *
	 * @param unknown_type $name
	 * @param unknown_type $config
	 */
	public static function set($name, $config = array()) {
		if (is_array($name)) {
			foreach ($name as $_name => $_config) {
				if (is_int($_name) && !is_array($_config)) {
					$_name = $_config;
					$_config = array();
				}
				if (!empty($config)) {
					$_config += $config;
				}
				static::set($_name, $_config);
			}
			return;
		}

		$name = static::_name($name);
		if ($config === false) {
			unset(static::$_scaffold[$name]);
		} else {
			static::$_scaffold[$name] = $config;
		}
	}

	/**
	 * Get a scaffold config
	 *
	 * @param unknown_type $name
	 * @param unknown_type $fullConfig
	 */
	public static function get($name, $fullConfig = true) {
		$name = static::_name($name);
		$config = true;
		if (isset(static::$_scaffold[$name])) {
			$config = static::$_scaffold[$name];
		} elseif (empty(static::$_config['all'])) {
			return false;
		}
		if (!$fullConfig) {
			return $config;
		}
		if (!is_array($config)) {
			$config = array();
		}
		$config += static::defaults($name);
		return $config;
	}

	/**
	 * Takes params passed in Dispatcher::_callable() and loads a
	 * ScaffoldController instance for the current request if Scaffold is
	 * configured to accept requests for that controller
	 *
	 * @param unknown_type $params
	 * @return unknown
	 */
	public static function callable($params){
		$options = array('request' => $params['request']) + $params['options'];
		$name = $params['params']['controller'];
		$controller = static::controller($name, false);
		if ($controller) {
			$config['controller'] = $controller;
			static::set($name, $config);
			$action = $params['params']['action'];
			$controller = new $config['controller']($options);
		} elseif ($controller !== false) {
			$controller = static::_instance('controller', $options);
		}
		return $controller;
	}

	/**
	 * Prepare Controller environment for scaffold
	 *
	 * @param unknown_type $name
	 * @param \lithium\action\Controller $controller
	 * @param unknown_type $params
	 */
	public static function prepareController($name, \lithium\action\Controller &$controller, $params){
		if (!property_exists($controller, 'scaffold')) {
			return;
		}
		if (!is_array($controller->scaffold)) {
			$controller->scaffold = array();
		}
		$name = static::_name($name);
		$controller->scaffold['name'] = $name;
		$config = static::get($name);
		$config = $controller->scaffold + $config;
		$config['controller'] = '\\' . get_class($controller);
		static::set($name, $config);
		$action = $params['params']['action'];
		if (!method_exists($controller, $action) && static::action($name, $action)) {
			$config['controller'] = get_class($controller);
			$options = array('request' => $params['request']) + $params['options'];
			$controller = static::_instance('controller', $options);
		}
		$controller->scaffold = $config;
		static::_setMediaPaths($controller);
	}

	/**
	 *
	 * @param unknown_type $name
	 * @param unknown_type $default
	 */
	public static function controller($name, $default = true) {
		$config = static::get($name, false);
		if ($config === false) {
			return false;
		}
		$controller = null;
		if (isset($config['controller'])) {
			if (!class_exists($config['controller'])) {
				if (strpos($config['controller'],'\\') === false) {
					if ($controller = Libraries::locate('controllers', $config['controller'])) {
						$controller = '\\' . $controller;
					}
				}
			} else {
				$controller = $config['controller'];
			}
		}
		if (!$controller && $default) {
			$controller = static::$_classes['controller'];
		}
		return $controller;
	}

	/**
	 *
	 * @param unknown_type $name
	 * @param unknown_type $default
	 * @return string|NULL
	 */
	public static function model($name, $default = true) {
		$config = static::get($name, false);
		if ($config === false) {
			return false;
		}
		$model = null;
		if (isset($config['model'])) {
			if (!class_exists($config['model'])) {
				if (strpos($config['model'],'\\') === false) {
					if($model = Libraries::locate('models', $config['model'])) {
						$model = '\\' . $model;
					}
				}
			} else {
				$model = $config['model'];
			}
		} else {
			$model = Libraries::locate('models', Inflector::classify($name));
		}
		if (!$model && $default) {
			$model = static::$_classes['model'];
			$connection = 'default';
			if (isset($config['connection'])) {
				$connection = $config['connection'];
			}
			$model::meta(array(
				'connection' => $connection,
				'source' => Inflector::tableize($name),
				'name' => Inflector::classify($name)
			));
		}
		return $model;
	}

	/**
	 * Checks is an action is provided in the Scaffold config
	 *
	 * @todo integrate per config checking
	 *
	 * @param unknown_type $name
	 * @param unknown_type $action
	 */
	public static function action($name, $action) {
		return in_array($action, static::$_actions);
	}

	/**
	 *
	 * @param unknown_type $name
	 */
	public static function defaults($name) {
		return array(
			'controller' => self::controller($name, false) ?: null,
			'model' => static::model($name, false) ?: null
		);
	}

	/**
	 * Set Media paths to allow for default scaffold templates to be used
	 * and to adjust the layout path to default to the app.
	 *
	 * @todo do we need to set paths for scaffolds in other libs?
	 * @todo do we need to set templates to app default also?
	 *
	 * @param \lithium\action\Controller $controller
	 */
	protected static function _setMediaPaths(\lithium\action\Controller &$controller){
		$scaffold = Libraries::get('slicedup_scaffold');
		$name = $controller->scaffold['name'];
		$paths = array(
			'template' => array(
				"{:library}/views/{$name}/{:template}.{:type}.php",
				'{:library}/views/{:controller}/{:template}.{:type}.php',
				'{:library}/views/scaffold/{:template}.{:type}.php',
				$scaffold['path'] . '/views/scaffold/{:template}.{:type}.php'
			),
			'layout' => array(
				'{:library}/views/layouts/{:layout}.{:type}.php',
				LITHIUM_APP_PATH . '/views/layouts/{:layout}.{:type}.php'
			)
		);
		//if (!empty($request->params['library'])) {
		//	if ($library = Libraries::get($request->params['library'])) {}
		//}
		Media::type($controller->request->type(), null, array(
			'view' => '\lithium\template\View',
			'paths' => $paths
		));
	}

	/**
	 * Convert string to a scaffold config key
	 *
	 * @param string $name
	 * @return string mixed
	 */
	protected static function _name($name) {
		$name = Inflector::underscore($name);
		$name = preg_replace('/^app_|_controller(s)?/', '', $name);
		return $name;
	}
}
?>