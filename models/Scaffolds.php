<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\models;

use lithium\util\Inflector;
use BadMethodCallException;

/**
 * The `Scaffolds` model provides methods for accessing scaffold fieldsets as
 * configured within your own models. Models do not need to extend this model
 * class to provide this functionality excpet where you specificaly require
 * access to the fieldset getters explicitly for that model.
 *
 * It is also used as a model stub to access datasources for undefined models
 * in the scaffold.
 *
 * Scaffold fieldset properties should be added to your models as needed and
 * querried like so:
 * {{{
 * use sli_scaffold\core\Scaffold;
 * Scaffold::getSummaryFields($model);
 * Scaffold::getAddFormFields($model);
 * }}}
 * or directly from this class via
 * {{{
 * use sli_scaffold\extensions\data\Model;
 * Model::getFields($model, 'summary');
 * Model::getFormFields($model, 'add');
 * }}}
 */
class Scaffolds extends \lithium\data\Model {


	/**
	 * Form field mappings
	 *
	 * @var array
	 */
	protected static $_formFieldMappings = array(
		'default' => array(
			'__key' => array('type' => 'hidden'),
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
	 * @see sli_scaffold\core\Scaffold::getFieldset()
	 * @var array
	 */
	public static $scaffoldFields;

	/**
	 * Record summary fields
	 *
	 * Array of collumn/field names to be used for record summaries
	 *
	 * @see sli_scaffold\extensions\data\Model::scaffoldFields
	 * @var array
	 */
	public static $summaryFields;

	/**
	 * Record detail fields
	 *
	 * Array of collumn/field names to be used for full record details
	 *
	 * @see sli_scaffold\extensions\data\Model::scaffoldFields
	 * @var array
	 */
	public static $detailFields;

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
	public static $scaffoldFormFields;

	/**
	 * Add/create form fields
	 *
	 * Array of collumn/field names & form field mapping for use in record
	 * add/create forms
	 *
	 * @see sli_scaffold\extensions\data\Model::scaffoldFormFields
	 * @var array
	 */
	public static $createFormFields;

	/**
	 * Edit/update form fields
	 *
	 * Array of collumn/field names & form field mapping for use in record
	 * edit/update forms
	 *
	 * @see sli_scaffold\extensions\data\Model::scaffoldFormFields
	 * @var array
	 */
	public static $updateFormFields;

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
		$binding = null;
		if (is_object($model)) {
			$binding = $model;
			$model = $binding->model();
		}
		$setName = Inflector::camelize($fieldset, false) . 'Fields';
		$getters = array(
			"get" . ucfirst($setName),
			"getScaffoldFields"
		);

		$fields = array();
		switch (true) {
			case method_exists($model, $getters[0]):
				$fields = $model::$getters[0]($binding);
				break;
			case isset($model::$$setName):
				$fields = $model::$$setName;
				break;
			case $getters[0] != $getters[1] && method_exists($model, $getters[1]):
				$fields = $model::$getters[1]($binding);
				break;
			case isset($model::$scaffoldFields):
				$fields = $model::$scaffoldFields;
				break;
		}

		$filter = function($self, $params) {
			extract($params);
			if (empty($fields)) {
				$schema = $model::schema()->fields();
				$keys = array_keys($schema);
				$fieldsNames = array_map('lithium\util\Inflector::humanize', $keys);
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
		$binding = null;
		if (is_object($model)) {
			$binding = $model;
			$model = $binding->model();
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

		$fields = array();
		switch (true) {
			case method_exists($model, $getters[0]):
				$fields = $model::$getters[0]($binding);
				break;
			case isset($model::$$setName):
				$fields = $model::$$setName;
				break;
			case $getters[0] != $getters[1] && method_exists($model, $getters[1]):
				$fields = $model::$getters[1]($binding);
				break;
			case isset($model::$scaffoldFormFields):
				$fields = $model::$scaffoldFormFields;
				break;
		}

		$filter = function($self, $params) {
			extract($params);
			if (empty($fields)) {
				$fields[] = array(
					'legend' => null,
					'fields' => $self::invokeMethod('mapSchemaFields', array($model, $mapping))
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
					$_fieldset['fields'] = $self::mapFormFields($model, $_fieldset['fields'], $mapping);
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

	public static function setFieldMapping($mapping, $fields = array()) {
		static::$_formFieldMappings[$mapping] = $fields;
	}

	/**
	 * Apply form field mappings to a model schema
	 *
	 * @param array $schema
	 * @param mixed $mapping
	 * @param array $fieldset
	 */
	public static function mapFormFields($model, $fieldset = array(), $mapping = null){
		if (!is_array($mapping)) {
			$mapping = static::getFieldMapping($mapping);
		}
		if (!$fieldset) {
			return static::mapSchemaFields($model, $mapping);
		}
		$schema = $model::schema()->fields();
		$key = $model::meta('key');
		$fields = array();
		foreach ($fieldset as $field => $settings) {
			if (is_int($field)) {
				$field = current((array) $settings);
				$settings = array();
			}
			$fields[$field] = $settings;
			if ($field == $key && isset($mapping['__key'])) {
				$fields[$field]+= $mapping['__key'];
			}
			$type = isset($schema[$field]) ? $schema[$field]['type'] : null;
			if ($type && isset($mapping[$type])) {
				$fields[$field]+= $mapping[$type];
			}
			if ($type != $field && isset($mapping[$field])) {
				$fields[$field]+= $mapping[$field];
			}
		}
		return $fields;
	}

	public static function mapSchemaFields($model, $mapping = null, $schema = null) {
		if (!is_array($mapping)) {
			$mapping = static::getFieldMapping($mapping);
		}
		$schema = $schema ?: $model::schema()->fields();
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
			if ($settings['type'] != $field && isset($mapping[$field])) {
				$fields[$field]+= $mapping[$field];
			}
		}
		return $fields;
	}
}

?>