<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\tests\cases\util;

use slicedup_scaffold\util\Scaffold;
use lithium\tests\mocks\data\MockPost;
use lithium\action\Request;
use lithium\net\http\Router;
use lithium\action\Dispatcher;
use lithium\core\Libraries;
use lithium\util\Set;
use lithium\data\Connections;

Libraries::paths(array(
	'controllers' => Set::merge(Libraries::paths('controllers'), array(
		'{:library}\tests\mocks\controllers\{:name}' => array(
			'libraries' => array('slicedup_scaffold')
		)
	)),
	'models' => Set::merge((array) Libraries::paths('models'), array(
		'{:library}\tests\mocks\data\{:name}' => array(
			'libraries' => array('lithium')
		)
	))
));

class ScaffoldTest extends \lithium\test\Unit {

	public function setUp() {
		$this->_configs = Connections::config();
		Connections::config($this->_configs + array('mock-source' => array(
			'type' => 'lithium\tests\mocks\data\MockSource'
		)));
		MockPost::config();
	}

	public function tearDown() {
		Connections::config(array('mock-source' => false));
		Connections::config($this->_configs);
		$config = Scaffold::config();
		Scaffold::config(array('all' => true));
		if (!empty($config['scaffold'])) {
			foreach ($config['scaffold'] as $name => $scaffold) {
				Scaffold::set($name, false);
			}
		}
	}

	public function testConfig() {
		$config = Scaffold::config();
		$newConfig = array('all' => false, 'cows' => 'moo');
		$expected = $newConfig + $config;
		$result = Scaffold::config($newConfig);
		$this->assertIdentical($expected, $result);
		$this->assertIdentical(false, $result['all']);

		$newConfig = array('all' => true, 'cows' => 'baa');
		$expected = $newConfig + $result;
		$result = Scaffold::config($newConfig);
		$this->assertIdentical($expected, $result);
		$this->assertIdentical(true, $result['all']);

		$config = Scaffold::config(false);
		$this->assertIdentical(false, $config['all']);

		$config = Scaffold::config(true);
		$this->assertIdentical(true, $config['all']);

		Scaffold::config(false);
		$result = Scaffold::get('posts');
		$this->assertIdentical(false, $result);

		Scaffold::set('posts');
		$result = Scaffold::get('posts');
		$this->assertTrue(!empty($result));

		Scaffold::set('posts', false);
		$result = Scaffold::get('posts');
		$this->assertIdentical(false, $result);

		Scaffold::config(true);
		$result = Scaffold::get('posts');
		$this->assertTrue(!empty($result));
	}

	public function testScaffoldConfig() {
		$config = Scaffold::config();
		$newConfig = array(
			'scaffold' => array(
				'posts',
				'pages',
				'comments' => array(
					'this' => 'that'
				)
			)
		);
		$expected = array_merge($config, array(
			'scaffold' => array(
				'posts' => array(),
				'pages' => array(),
				'comments' => array(
					'this' => 'that'
				)
			)
		));
		$result = Scaffold::config($newConfig);
		$this->assertIdentical($expected, $result);
	}

	public function testScaffoldSetGet() {
		$config = array(
			'controller' => '\slicedup_scaffold\tests\mocks\controllers\MockController'
		);
		Scaffold::set('posts', $config);
		$expected = $config + array('model' => null);
		$result = Scaffold::get('posts');
		$this->assertIdentical($expected['controller'], $result['controller']);
		$this->assertIdentical($expected['model'], $result['model']);
	}

	public function testControllerGetter() {
		Scaffold::set('posts');
		$result = Scaffold::get('posts');
		$this->assertNull($result['controller']);
		$expected = '\slicedup_scaffold\controllers\ScaffoldController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => '\slicedup_scaffold\tests\mocks\controllers\NonExistentController'
		);
		Scaffold::set('posts', $config);
		$expected = '\slicedup_scaffold\controllers\ScaffoldController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => 'NonExistentController'
		);
		Scaffold::set('posts', $config);
		$expected = '\slicedup_scaffold\controllers\ScaffoldController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => '\slicedup_scaffold\tests\mocks\controllers\MockController'
		);
		Scaffold::set('posts', $config);
		$expected = $config['controller'];
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => 'MockController'
		);
		Scaffold::set('posts', $config);
		$expected = '\slicedup_scaffold\tests\mocks\controllers\MockController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));
	}

	public function testModelGetter() {
		Scaffold::set('posts');
		$result = Scaffold::get('posts');
		$this->assertNull($result['model']);
		$expected = '\slicedup_scaffold\models\Scaffolds';
		$this->assertIdentical($expected, Scaffold::model('posts'));

		$config = array(
			'model' => '\slicedup_scaffold\tests\mocks\models\NonExistentModel'
		);
		Scaffold::set('posts', $config);
		$expected = '\slicedup_scaffold\models\Scaffolds';
		$this->assertIdentical($expected, Scaffold::model('posts'));

		$config = array(
			'model' => 'NonExistentModel'
		);
		Scaffold::set('posts', $config);
		$expected = '\slicedup_scaffold\models\Scaffolds';
		$this->assertIdentical($expected, Scaffold::model('posts'));

		$config = array(
			'model' => '\lithium\tests\mocks\data\MockPost'
		);
		Scaffold::set('posts', $config);
		$expected = $config['model'];
		$this->assertIdentical($expected, Scaffold::model('posts'));

		$config = array(
			'model' => 'MockPost'
		);
		Scaffold::set('posts', $config);
		$expected = '\lithium\tests\mocks\data\MockPost';
		$this->assertIdentical($expected, Scaffold::model('posts'));
		$meta = $expected::meta();
		$this->assertEqual('MockPost', $meta['name']);
		$this->assertEqual('mock_posts', $meta['source']);
	}

	public function testCallable() {
		$params = $this->_request();
		$result = Scaffold::callable($params);
		$this->assertTrue($result instanceof \slicedup_scaffold\controllers\ScaffoldController);

		Scaffold::config(false);
		$params = $this->_request();
		$result = Scaffold::callable($params);
		$this->assertFalse($result);

		Scaffold::set('posts');
		$params = $this->_request();
		$result = Scaffold::callable($params);
		$this->assertTrue($result instanceof \slicedup_scaffold\controllers\ScaffoldController);

		$config = array(
			'controller' => '\slicedup_scaffold\tests\mocks\controllers\NonExistentController'
		);
		Scaffold::set('posts', $config);
		$params = $this->_request();
		$result = Scaffold::callable($params);
		$this->assertTrue($result instanceof \slicedup_scaffold\controllers\ScaffoldController);

		$config = array(
			'controller' => '\slicedup_scaffold\tests\mocks\controllers\MockController'
		);
		Scaffold::set('posts', $config);
		$params = $this->_request();
		$result = Scaffold::callable($params);
		$this->assertTrue($result instanceof \slicedup_scaffold\tests\mocks\controllers\MockController);
	}

	public function testControllerPrepare() {
		$params = $this->_request();
		$controller = Scaffold::callable($params);
		$this->assertNull($controller->scaffold);
		Scaffold::prepareController('posts', $controller, $params);
		$this->assertTrue(!empty($controller->scaffold));
		$this->assertIdentical('\slicedup_scaffold\controllers\ScaffoldController', $controller->scaffold['controller']);
		$this->assertIdentical('posts', $controller->scaffold['name']);
	}

	public function testReassignController() {
		$config = array(
			'controller' => '\slicedup_scaffold\tests\mocks\controllers\MockController'
		);
		Scaffold::set('posts', $config);
		$params = $this->_request();
		$result = Scaffold::callable($params);
		$this->assertTrue($result instanceof \slicedup_scaffold\tests\mocks\controllers\MockController);

		$config = array(
			'controller' => '\slicedup_scaffold\tests\mocks\controllers\MockController'
		);
		Scaffold::set('posts', $config);
		$params = $this->_request('/posts/view/1');
		$result = Scaffold::callable($params);
		$this->assertTrue($result instanceof \slicedup_scaffold\tests\mocks\controllers\MockController);

		Scaffold::prepareController('posts', $result, $params);
		$this->assertTrue($result instanceof \slicedup_scaffold\controllers\ScaffoldController);
		$this->assertEqual($result->scaffold['controller'], '\slicedup_scaffold\tests\mocks\controllers\MockController');
		$this->assertEqual(Scaffold::controller('posts'), '\slicedup_scaffold\tests\mocks\controllers\MockController');
	}

	public function testModelCalls(){
		$expected = Scaffold::getAllFields('posts');
		$result = Scaffold::getUpdateFields('posts');
		$this->assertIdentical($expected, $result);

		$expected = Scaffold::getFormFields('posts');
		$result = Scaffold::getUpdateFormFields('posts');
		$this->assertIdentical($expected, $result);
	}

	/**
	 * Create basic request params as passed to `Dispatcher::_callable()`
	 */
	protected function _request($url = '/posts') {
		$request = Router::process(new Request(compact('url')));
		$params = array(
			'request' => $request,
			'params' => Dispatcher::applyRules($request->params),
			'options' => array()
		);
		return $params;
	}

}
?>