<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\controllers;

use lithium\util\Inflector;
use slicedup_scaffold\core\Scaffold;

class ScaffoldController extends \lithium\action\Controller {

	public $scaffold;

	protected function _init() {
        $this->_render['negotiate'] = true;
        parent::_init();
    }

	public function index() {
		$vars = $this->_scaffoldVars();
		extract($vars);
		$fields = Scaffold::getSummaryFields($model);
		$recordSet = $model::all();
		$data = compact('recordSet', 'fields');
		$this->set($data);
		return $vars + $data;
	}

	public function view() {
		$vars = $this->_scaffoldVars();
		extract($vars);
		$fields = Scaffold::getDetailFields($model);
		$record = $model::first($this->request->id);
		if (!$record) {
			return $this->redirect(array('action' => 'index'));
		}
		$data = compact('record', 'fields');
		$this->set($data);
		return $vars + $data;
	}

	public function add() {
		$vars = $this->_scaffoldVars();
		extract($vars);
		$fields = Scaffold::getAddFormFields($model);
		$record = $model::create($this->request->data);
		if (($this->request->data) && $record->save()) {
			return $this->redirect(array('action' => 'index'));
		}
		$data = compact('record', 'fields');
		$this->set($data);
		return $vars + $data;
	}

	public function edit() {
		$vars = $this->_scaffoldVars();
		extract($vars);
		$fields = Scaffold::getEditFormFields($model);
		$record = $model::find($this->request->id);
		if (!$record) {
			return $this->redirect(array('action' => 'index'));
		}
		if (($this->request->data) && $record->save($this->request->data)) {
			return $this->redirect(array('action' => 'index'));
		}
		$data = compact('record', 'fields');
		$this->set($data);
		return $vars + $data;
	}

	public function delete() {
		$model = Scaffold::model($this->scaffold['name']);
		$record = $model::find($this->request->id);
		if ($record) {
			$record->delete();
		}
		return $this->redirect(array('action' => 'index'));
	}

	public function display() {}

	protected function _scaffoldVars () {
		$name = Inflector::humanize($this->scaffold['name']);
		$vars = array(
			'model' => Scaffold::model($this->scaffold['name']),
			'plural' => Inflector::pluralize($name),
			'singular' => Inflector::singularize($name),
			'actions' => Scaffold::handledAction($name . 'fd')
		);
		$this->set($vars);
		return $vars;
	}
}

?>