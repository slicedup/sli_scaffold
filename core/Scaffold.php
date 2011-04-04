<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\core;

use lithium\util;

use lithium\core\Libraries;
use lithium\net\http\Media;
use lithium\util\Inflector;
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
		'delete'
	);

	/**
	 * Scaffolds
	 *
	 * @var array
	 */
	protected static $_scaffold = array();

	protected static $_formFieldMappings = array(
		'default' => array(
			'text' => array('type' => 'textarea'),
			'boolean' => array('type' => 'checkbox')
		)
	);

	protected static $_formFieldsMapped = array();

	protected static $_fieldsMapped = array();

	/**
	 * Classes
	 *
	 * @var array
	 */
	protected static $_classes = array(
		'controller' => '\slicedup_scaffold\controllers\ScaffoldController',
		'model' => '\slicedup_scaffold\models\Scaffolds'
	);

	/**
	 * Provide scaffold field set getters
	 *
	 * @param $method
	 * @param $params
	 */
	public static function __callStatic($method, $params) {
		preg_match('/^get(?P<fieldSet>\w+)Fields$/', $method, $args);
		if (!$args) {
			$message = "Method %s not defined or handled in class %s";
			throw new BadMethodCallException(sprintf($message, $method, get_class()));
		}
		if (!isset($params[0])) {
			$message = "Params not specified for method %s in class %s";
			throw new BadMethodCallException(sprintf($message, $method, get_class()));
		}
		if (preg_match('/Form$/', $args['fieldSet'])) {
			$method = 'getFormFieldSet';
		} else {
			$method = 'getFieldSet';
		}
		$args = array($params[0], $args['fieldSet']);
		return static::invokeMethod($method, $args);
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
	 * Takes params passed in Dispatcher::_callable() and loads a
	 * ScaffoldController instance for the current request if Scaffold is
	 * configured to accept requests for that controller
	 *
	 * @param array $params
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
	 * @param string $name
	 * @param \lithium\action\Controller $controller
	 * @param array $params
	 */
	public static function prepareController($name, \lithium\action\Controller &$controller, $params){
		if (!property_exists($controller, 'scaffold')) {
			return;
		}

		$name = static::_name($name);
		$config = static::get($name);
		$config = (array) $controller->scaffold + $config;
		$config['controller'] = '\\' . get_class($controller);

		$action = $params['params']['action'];
		if (!method_exists($controller, $action) && static::handledAction($name, $action)) {
			$options = array('request' => $params['request']) + $params['options'];
			$controller = static::_instance('controller', $options);
		}

		$controller->scaffold = compact('name') + $config;
		static::set($name, $config);
		static::_setMediaPaths($controller);
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
	 * Get a list of fields for use in a given scaffold context
	 *
	 * @param string $model
	 * @param string $fieldset
	 */
	public static function getFieldSet($model, $fieldset = null) {
		if (!$fieldset) {
			$fieldset = 'scaffold';
		}
		$setName = Inflector::camelize($fieldset, false) . 'Fields';
		if (isset(static::$_fieldsMapped[$model][$setName])) {
			return static::$_fieldsMapped[$model][$setName];
		}

		$_model = $model::invokeMethod('_object');
		if (isset($_model->{$setName})) {
			$_fields = $_model->{$setName};
		} elseif (isset($_model->scaffoldFields)) {
			$_fields = $_model->scaffoldFields;
		}

		if (isset($_fields)) {
			$fields = array();
			foreach ($_fields as $field => $name) {
				if (is_int($field)) {
					$field = $name;
					$name = Inflector::humanize($name);
				}
				$fields[$field] = $name;
			}
		} else {
			$schema = $model::schema();
			$keys = array_keys($schema);
			$fieldsNames = array_map('\lithium\util\Inflector::humanize', $keys);
			$fields = array_combine($keys, $fieldsNames);
		}

		static::$_fieldsMapped[$model][$setName] = $fields;
		return $fields;
	}

	/**
	 * Get a list of fields for use in a given scaffold form context with form
	 * meta data to control scaffold form handling
	 *
	 * @param string $model
	 * @param string $fieldset
	 */
	public static function getFormFieldSet($model, $fieldset = null, $mapping = 'default'){
		if (!$fieldset || strtolower($fieldset) == 'form') {
			$fieldset = 'scaffoldForm';
		}
		$setName = Inflector::camelize($fieldset, false) . 'Fields';
		if (isset(static::$_formFieldsMapped[$model][$setName])) {
			return static::$_formFieldsMapped[$model][$setName];
		}

		$_model = $model::invokeMethod('_object');
		if (isset($_model->{$setName})) {
			$fields = $_model->{$setName};
		} elseif (isset($_model->scaffoldFormFields)) {
			$fields = $_model->scaffoldFormFields;
		}

		$schema = $model::schema();
		if (isset($fields)) {
			foreach ($fields as &$fieldset) {
				$fieldset = static::mapFormFields($schema, $mapping, $fieldset);
			}
		} else {
			$fields = array(static::mapFormFields($schema, $mapping));
		}

		static::$_formFieldsMapped[$model][$setName] = $fields;
		return $fields;
	}

	/**
	 * Apply form field mappings to a model schema
	 *
	 * @param array $schema
	 * @param mixed $mapping
	 * @param array $fieldset
	 */
	public static function mapFormFields($schema, $mapping = 'default', $fieldset = array()){
		$fields = array();
		if (!is_array($mapping)) {
			if (isset(static::$_formFieldMappings[$mapping])) {
				$mapping = static::$_formFieldMappings[$mapping];
			} else {
				$mapping = static::$_formFieldMappings['default'];
			}
		}
		foreach ($schema as $field => $settings) {
			if (!empty($fieldset)) {
				if (!isset($fieldset[$field]) && !in_array($field, $fieldset)) {
					continue;
				}
			}
			if (isset($mapping[$settings['type']])) {
				$fields[$field] = array();
				if (isset($fieldset[$field])) {
					$fields[$field] = $fieldset[$field];
				}
				$fields[$field]+= $mapping[$settings['type']];
			} else {
				$fields[] = $field;
			}
		}
		return $fields;
	}

	/**
	 * Get a default set of configuration options for a given scaffold based on
	 * current scaffold config and scaffold name
	 *
	 * @param string $name
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
		$type = $controller->request->type();
		if ($type == 'form') {
			$type = 'html';
		}
		Media::type($type, null, array(
			'view' => '\lithium\template\View',
			'paths' => $paths
		));
	}

	/**
	 * Convert string to a scaffold name
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