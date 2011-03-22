<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\models;

class ScaffoldModel extends \lithium\data\Model {

	public static function getSummaryFields($model) {
		return static::getAllFields($model);
	}

	public static function getDetailFields($model) {
		return static::getAllFields($model);
	}

	public static function getCreateFields($model) {
		return static::getAllFields($model);
	}

	public static function getUpdateFields($model) {
		return static::getAllFields($model);
	}

	public static function getScaffoldFields($model) {
		return static::getAllFields($model);
	}

	public static function getScaffoldFieldSet($model, $fieldset) {}

	public static function getAllFields($model) {
		$schema = $model::schema();
		$keys = array_keys($schema);
		$fields = array_map('\lithium\util\Inflector::humanize', $keys);
		return array_combine($keys, $fields);
	}
}
?>