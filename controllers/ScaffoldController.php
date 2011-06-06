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
		$fields = Scaffold::getSummaryFields($vars['model']);
		$params = $vars + compact('fields');
		
		$filter = function($self, $params){
			$model = $params['model'];
			if (empty($params['recordSet'])) {
				$params['recordSet'] = $model::all();
			}
			
			$self->set($params);
			return $params;
		};
		
		return $this->_filter(__METHOD__, $params, $filter);
	}

	public function view() {
		$vars = $this->_scaffoldVars();
		$fields = Scaffold::getDetailFields($vars['model']);
		$params = $vars + compact('fields');
		
		$filter = function($self, $params){
			$model = $params['model'];
			if (empty($params['record'])) {
				$params['record'] = $model::first($self->request->id);
			}
			$record = $params['record'];
			
			if (!$record) {
				return $self->redirect(array('action' => 'index'));
			}
			
			$self->set($params);
			return $params;
		};
		
		return $this->_filter(__METHOD__, $params, $filter);
		
	}

	public function add() {
		$vars = $this->_scaffoldVars();
		$fields = Scaffold::getCreateFormFields($vars['model']);
		$params = $vars + compact('fields');
		
		$filter = function($self, $params){
			$model = $params['model'];
			if (empty($params['record'])) {
				$params['record'] = $model::create();
			}
			$record = $params['record'];
			
			if (($self->request->data) && $record->save($self->request->data)) {
				return $self->redirect(array('action' => 'index'));
			}
			
			$self->set($params);
			return $params;
		};
		
		return $this->_filter(__METHOD__, $params, $filter);
	}

	public function edit() {
		$vars = $this->_scaffoldVars();
		$fields = Scaffold::getUpdateFormFields($vars['model']);
		$params = $vars + compact('fields');
		
		$filter = function($self, $params){
			$model = $params['model'];
			if (empty($params['record'])) {
				$params['record'] = $model::find($self->request->id);
			}
			$record = $params['record'];
			
			if (!$record) {
				return $self->redirect(array('action' => 'index'));
			}
			
			if (($self->request->data) && $record->save($self->request->data)) {
				return $self->redirect(array('action' => 'index'));
			}
			
			$self->set($params);
			return $params;
		};
		
		return $this->_filter(__METHOD__, $params, $filter);
	}

	public function delete() {
		$params = $this->_scaffoldVars();
		
		$filter = function($self, $params){
			$model = $params['model'];
			if (empty($params['record'])) {
				$params['record'] = $model::find($self->request->id);
			}
			$record = $params['record'];
			if ($record) {
				$record->delete();
			}
			return $self->redirect(array('action' => 'index'));
		};
		
		return $this->_filter(__METHOD__, $params, $filter);
	}

	public function display() {
		$vars = $this->_scaffoldVars();
		$path = func_get_args() ?: array('display');
		$params = $vars + compact('path');

		$filter = function($self, $params){
			if (!isset($params['template'])) {
				$params['template'] = join('/', (array) $params['path']);
			}
			return $self->render($params);
		};

		return $this->_filter(__METHOD__, $params, $filter);
	}

	protected function _scaffoldVars () {
		$name = Inflector::humanize($this->scaffold['name']);
		$vars = array(
			'model' => Scaffold::model($this->scaffold['name']),
			'plural' => Inflector::pluralize($name),
			'singular' => Inflector::singularize($name),
			'actions' => Scaffold::handledAction($name)
		);
		$this->set($vars);
		return $vars;
	}
}

?>