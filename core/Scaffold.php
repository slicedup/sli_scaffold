<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\core;

use lithium\action\Dispatcher;
use lithium\core\Libraries;
use lithium\util\Set;
use lithium\util\Inflector;
use sli_scaffold\extensions\data\Model;
use sli_base\net\http\Media;
use BadMethodCallException;

class Scaffold extends \lithium\core\StaticObject {

	/**
	 * Scaffolding config
	 *
	 * @var array
	 */
	protected static $_config = array(
		'all' => true,
		'paths' => true,
		'prefixes' => array(
			'default' => ''
		),
		'actions' => array(
			'index',
			'view',
			'add',
			'edit',
			'delete',
			'display'
		)
	);

	/**
	 * Scaffolds
	 *
	 * @var array
	 */
	protected static $_scaffold = array();

	/**
	 * Classes
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'controller' => 'sli_scaffold\controllers\ScaffoldController',
		'model' => 'sli_scaffold\models\Scaffolds'
	);

	/**
	 * Provide scaffold field set getters
	 *
	 * @param $method
	 * @param $params
	 */
	public static function __callStatic($method, $params) {
		if (!preg_match('/^get\w+Fields$/', $method)) {
			$message = "Method %s not defined or handled in class %s";
			throw new BadMethodCallException(sprintf($message, $method, get_class()));
		}
		return Model::invokeMethod($method, $params);
	}

	/**
	 * Configure the Scaffold class
	 *
	 * @param array $config
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
	 * @param string $name
	 * @param array $config
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
	 * @param string $name
	 * @param boolean $fullConfig true return config with defaults || false
	 * config options explicitly set only
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
	 * Obtain default scaffold name from dispatch params
	 *
	 * @param $params
	 * @return string
	 */
	public static function name($params) {
		if (is_string($params)) {
			$name = $params;
		} else {
			$name = $params['controller'];
			if (!empty($params['library'])) {
				$name = $params['library'] . '.' . $name;
			}
		}
		return static::_name($name);
	}

	/**
	 * Convert string to a scaffold name
	 *
	 * @param string $name
	 * @return string mixed
	 */
	protected static function _name($name, $full = false) {
		$library = 'app';
		if ($lib = strpos($name, '.')) {
			$library = substr($name, 0, $lib + 1);
			$name = substr($name, $lib + 1);
		}
		$name = Inflector::underscore($name);
		$name = trim(preg_replace('/_?controller(s)?/', '', $name), ' _');
		if ($library != 'app' || $full) {
			$name = "{$library}.{$name}";
		}
		return $name;
	}

	/**
	 * Invokes a scaffolded action on the default scaffold controller based on
	 * the current controller/request using the.
	 */
	public static function invoke($controller, array $params = array(), array $options = array()) {
		if ($callable = static::callable($controller, $params, $options)) {
			if (method_exists($controller, '_scaffold')) {
				$controller->invokeMethod('_scaffold', array($callable, $params, $options));
			}
			return static::call($callable, $params);
		}
	}

	/**
	 * Creates a default scaffold controller instance based on the current
	 * controller/request.
	 */
	public static function callable($controller, array &$params = array(), array $options = array()) {
		if (property_exists($controller, 'scaffold')) {
			$config = $controller->scaffold;
			$params += $controller->request->params;
			$params['controller'] = static::$_classes['controller'];
			$options+= array(
				'scaffold' => $config
			);
			if ((isset($config['paths']) && $config['paths']) || static::$_config['paths']) {
				static::paths($config['name']);
			}
			return Dispatcher::invokeMethod('_callable', array($controller->request, $params, $options));
		}
	}

	/**
	 * Convenience wrapper for invoking scaffold controllers.
	 */
	public static function call($controller, $params) {
		if (property_exists($controller, 'scaffold')) {
			return Dispatcher::invokeMethod('_call', array($controller, $controller->request, $params));
		}
	}

	/**
	 * Prepare Controller environment for scaffold
	 *
	 * @param string $name
	 * @param \lithium\action\Controller $controller
	 * @param array $params
	 */
	public static function prepare($name, &$controller, $params){
		if (!empty($params['options']['scaffold'])) {
			$controller->scaffold = $params['options']['scaffold'];
			return;
		}
		$name = static::_name($name);
		if (!property_exists($controller, 'scaffold') || ($config = static::get($name)) === false) {
			return;
		}

		$config = (array) $controller->scaffold + $config;
		$config['controller'] = get_class($controller);
		$controller->scaffold = compact('name') + $config;
		static::set($name, $config);
		if ((isset($config['paths']) && $config['paths']) || static::$_config['paths']) {
			static::paths($name);
		}
		
		$action = $params['params']['action'];
		if (method_exists($controller, $action) && static::$_classes['controller'] != get_class($controller)) {
			return;
		}
		$prefixes = isset($config['prefixes']) ? $config['prefixes'] : static::$_config['prefixes'];
		$prefix = false;
		if ($prefixes) {
			foreach ($prefixes as $name => $pre) {
				if ($pre && strpos($action, $pre) === 0) {
					$action = str_replace($pre, '', $action);
					$prefix = $name;
					break;
				}
			}
		}
		if ($prefix || (!$prefix && isset($prefixes['default']) && empty($prefixes['default']))) {
			if (static::handledAction($name, $action, $prefix ?: 'default')) {
				$scaffold = get_called_class();
				$controller->applyFilter('__invoke', function($self, $params, $chain) use($scaffold, $action){
					$dispatchParams = $params['dispatchParams'];
					$dispatchParams = compact('action') + ($dispatchParams ?: array());
					$options = $params['options'];
					return $scaffold::invoke($self, $dispatchParams, $options);
				});
			}
		}
	}

	/**
	 * Set media paths to allow universal scaffold template usage
	 *
	 * @param $name string scaffold config name
	 * @return null
	 * @filter
	 */
	public static function paths($name = null) {
		if ($name) {
			$name = static::_name($name, true);
		}
		$scaffold = Libraries::get('sli_scaffold');
		$paths = array(
			'append' => array(
				'template' => array(
					'{:library}/views/scaffold/{:template}.{:type}.php', //lib scaffolds
					LITHIUM_APP_PATH . '/views/scaffold/{:template}.{:type}.php', //app scaffolds
					$scaffold['path'] . '/views/scaffold/{:template}.{:type}.php' //default scaffolds
				),
				'layout' => array(
					LITHIUM_APP_PATH . '/views/layouts/{:layout}.{:type}.php' //app layout
				)
			),
			'prepend' => array()
		);

		if ($name) {
			list($library, $_name) = explode('.', $name);
			$library = Libraries::get($library);
			$paths['prepend']['template'] = array(
				$library['path'] . '/views/'.$_name.'/{:template}.{:type}.php',
				$library['path'] . '/views/{:controller}/{:template}.{:type}.php'
			);
			$paths['prepend']['layout'] = array(
				$library['path'] . '/views/layouts/{:layout}.{:type}.php'
			);
		}

		$params = compact('paths') + array('name' => $name);

		$filter = function($self, $params, $chain){
			$name = $params['name'];
			$paths = $params['paths'];
			$html = Media::type('html');
			if (empty($html['options']['paths'])) {
				Media::type('html', $html['content'], $html['options'] + Media::defaults());
			}
			if (!empty($paths['prepend'])) {
				Media::addPaths('html', $paths['prepend']);
			}
			if (!empty($paths['append'])) {
				Media::addPaths('html', $paths['append'], false);
			}
		};

		static::_filter(__FUNCTION__, $params, $filter);
	}

	/**
	 * Get controller class name for a scaffold
	 *
	 * @param string $name
	 * @param boolean $default true return default controller classname if
	 * class not found or configured
	 */
	public static function controller($name, $default = true) {
		$config = static::get($name, false);
		if ($config === false) {
			return false;
		}
		$controller = null;
		if (isset($config['controller'])) {
			if (!class_exists($config['controller'])) {
				if (strpos($config['controller'], '\\') === false) {
					$controller = Libraries::locate('controllers', $config['controller']);
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
	 * Checks is an action is provided in the Scaffold config
	 *
	 * @todo integrate per config checking
	 *
	 * @param string $name
	 * @param string $action
	 */
	public static function handledAction($name, $action = null, $prefix = null) {
		if (!$config = static::get($name)) {
			return false;
		}
		$actions = isset($config['actions']) ? (array) $config['actions'] : static::$_config['actions'];
		if (!isset($action)) {
			return $actions;
		}
		if ($prefix) {
			if (isset($actions[$action])) {
				return in_array($prefix, $actions[$action]);
			}
		}
		return in_array($action, $actions);
	}

	/**
	 * Get model class name for a scaffold
	 *
	 * @param string $name
	 * @param boolean $default true return default model classname if class not
	 * found or configured
	 */
	public static function model($name, $default = true) {
		$config = static::get($name, false);
		if ($config === false) {
			return false;
		}
		$model = null;
		if (isset($config['model'])) {
			if (!class_exists($config['model'])) {
				if (strpos($config['model'], '\\') === false) {
					$model = Libraries::locate('models', $config['model']);
				}
			} else {
				$model = $config['model'];
			}
		} else {
			$model = Libraries::locate('models', Inflector::pluralize(Inflector::classify($name)));
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
				'name' => Inflector::pluralize(Inflector::classify($name))
			));
		}
		return $model;
	}

	/**
	 * Get a default set of configuration options for a given scaffold based on
	 * current scaffold config and scaffold name
	 *
	 * @param string $name
	 */
	public static function defaults($name) {
		return array(
			'controller' => static::controller($name, false) ?: null,
			'model' => static::model($name, false) ?: null
		);
	}
}

?>