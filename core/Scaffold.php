<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\core;

use lithium\action\Dispatcher;
use lithium\core\Libraries;
use lithium\net\http\Media;
use lithium\util\Set;
use lithium\util\Inflector;
use slicedup_scaffold\extensions\data\Model;
use BadMethodCallException;

class Scaffold extends \lithium\core\StaticObject {

	/**
	 * Scaffolding config
	 *
	 * @var array
	 */
	protected static $_config = array(
		'all' => true,
		'model' => array(),
		'controller' => array()
	);

	/**
	 * Scaffolded actions
	 *
	 * @var array
	 */
	protected static $_actions = array(
		'index',
		'view',
		'add',
		'edit',
		'delete',
		'display'
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
		'controller' => 'slicedup_scaffold\controllers\ScaffoldController',
		'model' => 'slicedup_scaffold\models\Scaffolds'
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
				$name = $params['library'] . '/' . $name;
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
	protected static function _name($name) {
		$name = Inflector::underscore($name);
		$name = trim(preg_replace('/^app_|_?controller(s)?/', '', $name), ' _');
		return $name;
	}

	/**
	 * Invokes a scaffolded action on the default scaffold controller based on
	 * the current controller/request using the.
	 */
	public static function invoke($controller, array $params = array(), array $options = array()) {
		if ($callable = static::callable($controller, $params, $options)) {
			return static::call($callable, $params);
		}
	}

	/**
	 * Creates a default scaffold controller instance based on the current
	 * controller/request.
	 */
	public static function callable($controller, array &$params = array(), array $options = array()) {
		if (property_exists($controller, 'scaffold')) {
			static::setMediaPaths();
			$params += $controller->request->params;
			$params['controller'] = static::$_classes['controller'];
			$options+= array(
				'scaffold' => $controller->scaffold
			);
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
			//Controllers invoked by Scaffold::invokeScaffoldAction always pass
			//the scaffold config as an option, in those cases all the
			//neccesary config has been done
			$controller->scaffold = $params['options']['scaffold'];
			return;
		}

		if (property_exists($controller, 'scaffold')) {
			static::setMediaPaths();
			//merge Controller::scaffold with config
			$name = static::_name($name);
			$config = static::get($name);
			$config = (array) $controller->scaffold + $config;
			$config['controller'] = get_class($controller);
			//update Controller::scaffold
			$controller->scaffold = compact('name') + $config;
			//update stored config
			static::set($name, $config);

			//Check action for current controller, invoke scaffold controller
			//for undeclared actions when action is envoked
			$action = $params['params']['action'];
			if (!method_exists($controller, $action) && static::handledAction($name, $action)) {
				$scaffold = get_called_class();
				$controller->applyFilter('__invoke', function($self, $params, $chain) use($scaffold){
					$dispatchParams = $params['dispatchParams'];
					$dispatchParams = $dispatchParams ?: array();
					$options = $params['options'];
					return $scaffold::invoke($self, $dispatchParams, $options);
				});
			}
		}
	}

	/**
	 * Set media paths to allow universal scaffold template usage
	 */
	public static function setMediaPaths() {
		static $run;
		if (isset($run)) {
			return;
		}
		$run = true;
		$scaffold = Libraries::get('slicedup_scaffold');
		$htmlOptions = array(
			'view' => '\lithium\template\View',
			'paths' => array(
				'template' => array(
					'{:library}/views/{:controller}/{:template}.html.php',
					'{:library}/views/scaffold/{:template}.html.php',
					$scaffold['path'] . '/views/scaffold/{:template}.html.php'
				),
				'layout' => array(
					'{:library}/views/layouts/{:layout}.html.php',
					LITHIUM_APP_PATH . '/views/layouts/{:layout}.html.php'
				),
				'element' => array(
					'{:library}/views/elements/{:template}.{:type}.php',
				)
			),
			'conditions' => array('ajax' => false)
		);
		$html = Media::type('html');
		Media::type('html', $html['content'], Set::merge($html['options'], $htmlOptions));

		$ajax = Media::type('ajax') ?: array_fill_keys(array('options','content'), array());
		$ajax['options']  = Set::merge($ajax['options'], array(
			'view' => '\lithium\template\View',
			'paths' => array(
				'template' => array(
					'{:library}/views/{:controller}/{:template}.ajax.php',
					'{:library}/views/scaffold/{:template}.ajax.php',
					'{:library}/views/{:controller}/{:template}.html.php',
					'{:library}/views/scaffold/{:template}.html.php',
					$scaffold['path'] . '/views/scaffold/{:template}.html.php'
				),
				'layout' => array(
					'{:library}/views/layouts/{:layout}.ajax.php',
					LITHIUM_APP_PATH . '/views/layouts/{:layout}.ajax.php',
					'{:library}/views/layouts/{:layout}.html.php',
					LITHIUM_APP_PATH . '/views/layouts/{:layout}.html.php'
				),
				'element' => array(
					'{:library}/views/elements/{:template}.{:type}.php',
					'{:library}/views/elements/{:template}.html.php',
				)
			),
			'conditions' => array('ajax' => true)
		));
		$ajax['content'] = array(
			'text/html', 'application/xhtml+xml',//html
			'application/x-www-form-urlencoded',//form
			'application/javascript', 'text/javascript'//js
		);
		Media::type('ajax', $ajax['content'], $ajax['options']);
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
	public static function handledAction($name, $action = null) {
		if (!$config = static::get($name)) {
			return false;
		}
		$actions = isset($config['actions']) ? (array) $config['actions'] : static::$_actions;
		if (!isset($action)) {
			return $actions;
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