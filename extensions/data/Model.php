<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\extensions\data;

use lithium\util\Inflector;
use BadMethodCallException;

/**
 * The `Model` class provides methods for accessing scaffold fieldsets as
 * configured within your own models. Models do not need to extend this model
 * class to provide this functionality excpet where you specificaly require
 * access to the fieldset getters explicitly for that model.
 * Scaffold fieldset properties should be added to your models as needed and
 * querried like so:
 * {{{
 * use slicedup_scaffold\core\Scaffold;
 * Scaffold::getSummaryFields($model);
 * Scaffold::getAddFormFields($model);
 * }}}
 * or directly from this class via
 * {{{
 * use slicedup_scaffold\extensions\data\Model;
 * Model::getFields($model, 'summary');
 * Model::getFormFields($model, 'add');
 * }}}
 */
class Model extends \lithium\data\Model {

	protected static $_formFieldMappings = array(
		'default' => array(
			'__key' => array('method' => 'hidden'),
			'text' => array('type' => 'textarea'),
			'boolean' => array('type' => 'checkbox')
		)
	);

	/**
	 * Scaffold fields
	 *
	 * Array of collumn/field names to be used for general scaffolds and as defaults
	 * when full schema is not required. When other scaffold field sets are
	 * not set, this will be used.
	 *
	 * Items can either be collumn/field name values with integer keys, or
	 * collumn/field names as keys with aliased names as values
	 *
	 * @see slicedup_scaffold\core\Scaffold::getFieldset()
	 * @var array
	 */
	public $scaffoldFields;

	/**
	 * Record summary fields
	 *
	 * Array of collumn/field names to be used for record summaries
	 *
	 * @see slicedup_scaffold\extensions\data\Model::scaffoldFields
	 * @var array
	 */
	public $summaryFields;

	/**
	 * Record detail fields
	 *
	 * Array of collumn/field names to be used for full record details
	 *
	 * @see slicedup_scaffold\extensions\data\Model::scaffoldFields
	 * @var array
	 */
	public $detailFields;

	/**
	 * Scaffold form fields
	 *
	 * Array of collumn/field form mappings for use in scaffold forms. Used for
	 * all forms when other scaffold fieldsets are not set.
	 *
	 * Nested array of fieldset keys and array field mapping values. Fieldsets
	 * keys can be integers or 'named' with string keys. Field mapping values
	 * are arrays of either collumn/field name values with integer keys, or
	 * collumn/field names as keys with field settings arrays as values
	 *
	 * Some examples:
	 * {{{
	 * //Map id, title & content where content is a text area
	 * array(
	 * 	array(
	 * 		'id',
	 * 		'title'
	 * 		'content' => array(
	 * 			'type' => 'textarea'
	 * 		)
	 * 	)
	 * )
	 * //Map named fieldsets
	 * array(
	 * 	'Info' => array(
	 * 		'id',
	 * 		'name'
	 * 		'title' => array(
	 * 			'list' => array('Mr', 'Mrs')
	 * 		)
	 * 	),
	 * 	'Contact' => array(
	 * 		'phone',
	 * 		'postal_address' => array(
	 * 			'type' => 'texarea',
	 * 			'label' => 'Postal'
	 * 		),
	 * 		'preferred' => array(
	 * 			'list' => array('phone', 'postal')
	 * 		)
	 * 	)
	 * )
	 * }}}
	 *
	 * @var array
	 */
	public $scaffoldFormFields;

	/**
	 * Add/create form fields
	 *
	 * Array of collumn/field names & form field mapping for use in record
	 * add/create forms
	 *
	 * @see slicedup_scaffold\extensions\data\Model::scaffoldFormFields
	 * @var array
	 */
	public $createFormFields;

	/**
	 * Edit/update form fields
	 *
	 * Array of collumn/field names & form field mapping for use in record
	 * edit/update forms
	 *
	 * @see slicedup_scaffold\extensions\data\Model::scaffoldFormFields
	 * @var array
	 */
	public $updateFormFields;

	/**
	 * Provide scaffold field set getters
	 *
	 * @param $method
	 * @param $params
	 */
	public static function __callStatic($method, $params) {
		preg_match('/^get(?P<set>\w+)Fields$/', $method, $args);
		if (!$args) {
			return parent::__callStatic($method, $params);
		}

		$model = isset($params[0]) ? $params[0] : get_called_class();
		if ($model == __CLASS__) {
			$message = "Params not specified for method %s in class %s";
			throw new BadMethodCallException(sprintf($message, $method, get_class()));
		}

		extract($args);
		$method = preg_match('/Form$/', $set) ? 'getFormFields' : 'getFields';
		$mapping = isset($params[1]) ? $params[1] : null;
		return static::invokeMethod($method, array($model, $set, $mapping));
	}

	/**
	 * Get a list of fields for use in a given scaffold context
	 *
	 * @param string $model
	 * @param string $fieldset
	 */
	public static function getFields($model, $fieldset = null) {
		if (!$fieldset) {
			$fieldset = 'scaffold';
		}
		$setName = Inflector::camelize($fieldset, false) . 'Fields';
		$getters = array(
			"get" . ucfirst($setName),
			"getScaffoldFields"
		);
		$class = $model::invokeMethod('_object');
		$fields = array();
		switch (true) {
			case method_exists($model, $getters[0]):
				$fields = $model::$getters[0]();
				break;
			case isset($class->{$setName}):
				$fields = $class->{$setName};
				break;
			case $getters[0] != $getters[1] && method_exists($model, $getters[1]):
				$fields = $model::$getters[1]();
				break;
			case isset($class->scaffoldFields):
				$fields = $class->scaffoldFields;
				break;
		}
		
		$filter = function($self, $params) {
			extract($params);
			if (empty($fields)) {
				$schema = $model::schema();
				$keys = array_keys($schema);
				$fieldsNames = array_map('\lithium\util\Inflector::humanize', $keys);
				$fields = array_combine($keys, $fieldsNames);
			} else {
				$_fields = $fields;
				$fields = array();
				foreach ($_fields as $field => $name) {
					if (is_int($field)) {
						$field = $name;
						$name = Inflector::humanize($name);
					}
					$fields[$field] = $name;
				}
			}
			return $fields;
		};
		
		$params = compact('fields', 'fieldset', 'model');
		return static::_filter(__FUNCTION__, $params, $filter);
	}

	/**
	 * Get a list of fields for use in a given scaffold form context with form
	 * meta data to control scaffold form handling
	 *
	 * @param string $model
	 * @param string $fieldset
	 */
	public static function getFormFields($model, $fieldset = null, $mapping = null){
		if (!$fieldset || strtolower($fieldset) == 'form') {
			$fieldset = 'scaffold';
		}
		$setName = Inflector::camelize($fieldset, false);
		if (substr($setName, -4) != 'Form') {
			$setName .= 'Form';
		}
		$setName .= 'Fields';
		$getters = array(
			"get" . ucfirst($setName),
			"getScaffoldFormFields"
		);
		$class = $model::invokeMethod('_object');
		$fields = array();
		switch (true) {
			case method_exists($model, $getters[0]):
				$fields = $model::$getters[0]();
				break;
			case isset($class->{$setName}):
				$fields = $class->{$setName};
				break;
			case $getters[0] != $getters[1] && method_exists($model, $getters[1]):
				$fields = $model::$getters[1]();
				break;
			case isset($class->scaffoldFormFields):
				$fields = $class->scaffoldFormFields;
				break;
		}
		
		$filter = function($self, $params) {
			extract($params);
			if (empty($fields)) {
				$fields[] = array(
					'legend' => null,
					'fields' => $self::invokeMethod('_mapSchemaFields', array($model, $mapping))
				);
			} else {
				$first = reset($fields);
				if (!is_array($first) || !isset($first['fields'])) {
					$fields = array(compact('fields'));
				}
				foreach ($fields as $key => &$_fieldset) {
					if (!isset($_fieldset['fields'])) {
						$_fieldset = array(
							'fields' => $_fieldset
						);
					}
					$map = array($model, $_fieldset['fields'], $mapping);
					$_fieldset['fields'] = $self::invokeMethod('_mapFormFields', $map);
					if (!isset($_fieldset['legend'])) {
						$_fieldset['legend'] = !is_int($key) ? $key : null;
					}
				}	
			}
			return $fields;
		};
		
		$params = compact('fields', 'fieldset', 'mapping', 'model');
		return static::_filter(__FUNCTION__, $params, $filter);
	}

	public static function getFieldMapping($mapping) {
		if (!isset($mapping)) {
			$mapping = key(static::$_formFieldMappings);
		}
		if (isset(static::$_formFieldMappings[$mapping])) {
			return static::$_formFieldMappings[$mapping];
		}
		return array();
	}

	public function setFieldMapping($mapping, $fields = array()) {}

	/**
	 * Apply form field mappings to a model schema
	 *
	 * @param array $schema
	 * @param mixed $mapping
	 * @param array $fieldset
	 */
	protected static function _mapFormFields($model, $fieldset = array(), $mapping = null){
		if (!is_array($mapping)) {
			$mapping = static::getFieldMapping($mapping);
		}
		if (!$fieldset) {
			return static::_mapSchemaFields($model, $mapping);
		}
		$schema = $model::schema();
		$key = $model::meta('key');
		$fields = array();
		foreach ($fieldset as $field => $settings) {
			if (is_int($field)) {
				$field = current((array) $settings);
				$settings = array();
			}
			$fields[$field] = array();
			if ($field == $key && isset($mapping['__key'])) {
				$fields[$field]+= $mapping['__key'];
			}
			$type = isset($schema[$field]) ? $schema[$field]['type'] : null;
			if ($type && isset($mapping[$type])) {
				$fields[$field] = $mapping[$type];
			}
			$fields[$field]+= $settings;
		}
		return $fields;
	}

	protected static function _mapSchemaFields($model, $mapping = null) {
		if (!is_array($mapping)) {
			$mapping = static::getFieldMapping($mapping);
		}
		$schema = $model::schema();
		$key = $model::meta('key');
		$fields = array();
		foreach ($schema as $field => $settings) {
			$fields[$field] = array();
			if ($field == $key && isset($mapping['__key'])) {
				$fields[$field]+= $mapping['__key'];
			}
			if (isset($mapping[$settings['type']])) {
				$fields[$field]+= $mapping[$settings['type']];
			}
		}
		return $fields;
	}

}

?>