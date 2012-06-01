<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\core;

use lithium\action\Dispatcher;
use lithium\action\DispatchException;
use lithium\core\Libraries;
use lithium\util\Set;
use lithium\util\Inflector;
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
		),
		'connection' => 'default'
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
		$model = static::$_classes['model'];
		return $model::invokeMethod($method, $params);
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
			if (isset($config['classes'])) {
				static::$_classes = $config['classes'] + static::$_classes;
			}
			unset($config['scaffold'], $config['classes']);
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
		if ($config === false) {
			unset(static::$_scaffold[$name]);
		} else {
			$_library =  null;
			$_name = $name;
			if (strpos($name, '.')) {
				list($_library, $_name) = explode('.', $name);
			}
			$config = compact('name', '_name', '_library') + $config;
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
		if (!isset(static::$_scaffold[$name])) {
			if (empty(static::$_config['all'])) {
				return false;
			}
			static::set($name);
		}
		$config = static::$_scaffold[$name];
		if (!$fullConfig) {
			return $config;
		}
		$config += static::_defaults($name);
		return $config;
	}

	/**
	 * Detect scaffold config from dispatch params
	 * 
	 * @param mixed $params array dispatch params, string controller name variation
	 */
	public static function detect($params) {
		$library = 'app';
		$libset = false;
		if (is_string($params)) {
			$name = $params;
		} else {
			$name = $params['controller'];
			if (!empty($params['library'])) {
				$libset = true;
				$library = $params['library'];
			}
		}
		if (strpos($name, '.')) {
			$libset = true;
			list($library, $name) = explode('.', $name);
		}
		if (strpos($name, '\\') !== false) {
			$path = explode('\\', $name);
			$library = array_shift($path);
			$name = array_pop($path);
		}
		$name = Inflector::underscore($name);
		$name = trim(preg_replace(array('/_?controller(s)?/', '/^.*_/'), '', $name), ' _');
		$lookup = array("{$library}.{$name}");
		$libset ? array_push($lookup, $name) : array_unshift($lookup, $name);
		foreach ($lookup as $key) {
			if (static::get($key) !== false) {
				return $key;
			}
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
		
		if (!property_exists($controller, 'scaffold') || ($config = static::get($name)) === false) {
			return;
		}

		$config = (array) $controller->scaffold + $config;
		$config['controller'] = get_class($controller);
		$controller->scaffold = $config;
		static::set($name, $config);
		if (!isset($config['paths'])) {
			$config['paths'] = static::$_config['paths'];
		}
		if ($config['paths']) {
			static::_paths($config);
		}

		$action = $_action = $params['params']['action'];
		$prefix = false;
		$prefixed = static::_parsePrefix($action, $config);
		extract($prefixed);
		$controller->scaffold['prefix'] = $prefix;
		
		if (method_exists($controller, $_action) && static::$_classes['controller'] != get_class($controller)) {
			if (method_exists($controller, '_scaffold')) {
				$controller->invokeMethod('_scaffold', array($controller, $params['params'], $params['options']));
			}
			return;
		}
		
		if (!$prefix || !static::handledAction($name, $action, $prefix)) {
			throw new DispatchException("Action `{$_action}` not scaffolded.");
		}
		
		$scaffold = get_called_class();
		$controller->applyFilter('__invoke', function($self, $params, $chain) use($scaffold, $action){
			$dispatchParams = $params['dispatchParams'];
			$dispatchParams = compact('action') + ($dispatchParams ?: array());
			$options = $params['options'];
			return $scaffold::invokeMethod('_invoke', array($self, $dispatchParams, $options));
		});	
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
		} else {
			$controller = static::_locate('controllers', $config['_name'], $config['_library']);
		}
		if (!$controller && $default) {
			$controller = static::$_classes['controller'];
		}
		return $controller;
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
			$model = static::_locate('models', $config['_name'], $config['_library']);
		}
		if (!$model && $default) {
			$model = static::$_classes['model'];
			$connection = static::$_config['connection'];
			if (is_array($config) && array_key_exists('connection', $config)) {
				$connection = $config['connection'];
			}
			$model::meta(array(
				'connection' => $connection,
				'name' => Inflector::pluralize(Inflector::classify($config['_name']))
			));
			$lookup = static::_source($config['_name'], $config['_library']);
			$source = $lookup[0];
			if ($connection = $model::connection()) {
				if ($sources = $connection->sources()) {
					foreach ($lookup as $collection) {
						if (in_array($collection, $sources)) {
							$source = $collection;
							break;
						}
					}
				}
			}
			$model::meta(compact('source'));
		}
		return $model;
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
			return array_filter(array_map(function($action, $key) use($prefix){
				if (is_array($action)) {
					if (!isset($prefix) || in_array($prefix, $action)) {
						return $key;	
					}
				} else {
					return $action;	
				}
				return false;
			}, $actions, array_keys($actions)));
		}
		if ($prefix) {
			if (isset($actions[$action])) {
				return in_array($prefix, $actions[$action]);
			}
		}
		return in_array($action, $actions) || array_key_exists($action, $actions);
	}
	
	/**
	 * Get a default set of configuration options for a given scaffold based on
	 * current scaffold config and scaffold name
	 *
	 * @param string $name
	 */
	protected static function _defaults($name) {
		return array(
			'controller' => static::controller($name, false) ?: null,
			'model' => static::model($name, false) ?: null
		);
	}

	/**
	 * Invokes a scaffolded action on the default scaffold controller based on
	 * the current controller/request using the.
	 */
	protected static function _invoke($controller, array $params = array(), array $options = array()) {
		if ($callable = static::_callable($controller, $params, $options)) {
			if (method_exists($controller, '_scaffold')) {
				$controller->invokeMethod('_scaffold', array($callable, $params, $options));
			}
			return Dispatcher::invokeMethod('_call', array($callable, $callable->request, $params));
		}
	}

	/**
	 * Creates a default scaffold controller instance based on the current
	 * controller/request.
	 */
	protected static function _callable($controller, array &$params = array(), array $options = array()) {
		if (property_exists($controller, 'scaffold')) {
			$config = $controller->scaffold;
			$params += $controller->request->params;
			$params['controller'] = static::$_classes['controller'];
			$options+= array(
				'scaffold' => $config
			);
			return Dispatcher::invokeMethod('_callable', array($controller->request, $params, $options));
		}
	}

	/**
	 * Set media paths to allow universal scaffold template usage
	 *
	 * @param $name string scaffold config name
	 * @return null
	 * @filter
	 */
	protected static function _paths($config) {
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

		$name = $config['name'];
		$_name = $config['_name'];
		$_library = $config['_library'];
		
		if ($_library && $_library != 'app' && $library = Libraries::get($_library)) {
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
	 * Parse prefix from current action & configured prefixes
	 * 
	 * @param string $action requested action
	 * @param array $config
	 */
	protected static function _parsePrefix($action, $config) {
		$prefixes = static::$_config['prefixes'];
		if (isset($config['prefixes'])) {
			$prefixes = $config['prefixes'];
		}
		$prefix = false;
		if ($prefixes) {
			foreach ($prefixes as $key => $pre) {
				if ($pre && strpos($action, $pre) === 0) {
					$action = str_replace($pre, '', $action);
					$prefix = $key;
					break;
				}
			}
			if (!$prefix && in_array('', $prefixes)) {
				$prefix = array_search('', $prefixes);
			}
		}
		return compact('action', 'prefix');
	}
	
	/**
	 * Locate a given class type (contoller or model) based on a configured
	 * scaffold name given prefernce to libraries if configured, and also
	 * accounting for incorrect pluralization.
	 * 
	 * @param string $type
	 * @param string $name
	 * @param string $library
	 */
	protected static function _locate($type, $name, $library = null) {
		$singular = Inflector::classify($name);
		$plural = Inflector::pluralize($singular);
		$lookup = array($plural, $singular);
		if ($library) {
			foreach ($lookup as $className) {
				if ($className = Libraries::locate($type, $className, compact('library'))) {
					return $className;
				}
			}
		}
		foreach ($lookup as $className) {
			if ($className = Libraries::locate($type, $className)) {
				return $className;
			}
		}
	}
	
	/**
	 * Generate a list of possible collection/tabel names based on a configured
	 * scaffold name accounting for library prefixes
	 * 
	 * @param string $name
	 * @param string $library
	 */
	protected static function _source($name, $library = null) {
		$singular = Inflector::singularize(Inflector::tableize($name));
		$plural = Inflector::pluralize($singular);
		if (isset($library)) {
			$_singular = Inflector::singularize(Inflector::tableize("{$library}.{$name}"));
			$_plural = Inflector::pluralize($_singular);
			$lookup = array($_plural, $plural, $_singular, $singular);
		} else {
			$lookup = array($plural, $singular);
		}
		return $lookup;
	}
}

?>