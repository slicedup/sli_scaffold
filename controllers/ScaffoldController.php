<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\controllers;

use lithium\util;

use lithium\util\Inflector;
use slicedup_scaffold\util\Scaffold;
use slicedup_scaffold\models\ScaffoldModel;

class ScaffoldController extends \lithium\action\Controller {

	public $scaffold;

	public function index() {
		$vars = $this->_scaffoldVars();
		extract($vars);
		$fields = ScaffoldModel::getSummaryFields($model);
		$recordSet = $model::all();
		$this->set(compact('recordSet', 'fields'));
	}

	public function view() {
		$vars = $this->_scaffoldVars();
		extract($vars);
		$fields = ScaffoldModel::getDetailFields($model);
		$record = $model::first($this->request->id);
		if (!$record) {
			$this->redirect(array('action' => 'index'));
		}
		$this->set(compact('record', 'fields'));
	}

	public function add() {
		$vars = $this->_scaffoldVars();
		extract($vars);
		$fields = ScaffoldModel::getCreateFields($model);
		$record = $model::create();
		if (($this->request->data) && $record->save($this->request->data)) {
			$this->redirect(array('action' => 'index', 'args' => array($record->id)));
		}
		return compact('record', 'fields');
	}

	public function edit() {
		$vars = $this->_scaffoldVars();
		extract($vars);
		$fields = ScaffoldModel::getUpdateFields($model);
		$record = $model::find($this->request->id);
		if (!$record) {
			$this->redirect(array('action' => 'index'));
		}
		if (($this->request->data) && $record->save($this->request->data)) {
			$this->redirect(array('action' => 'view', 'args' => array($record->id)));
		}
		return compact('record', 'fields');
	}

	public function delete() {
		$model = Scaffold::model($this->scaffold['name']);
		$model::delete($this->request->id);
		$this->redirect(array('action' => 'index'));
	}

	public function display() {}

	protected function _scaffoldVars () {
		$name = Inflector::humanize($this->scaffold['name']);
		$vars = array(
			'model' => Scaffold::model($this->scaffold['name']),
			'plural' => Inflector::pluralize($name),
			'singular' => Inflector::singularize($name)
		);
		$this->set($vars);
		return $vars;
	}
}
?>