<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace slicedup_scaffold\tests\cases\core;

use lithium\test;

use slicedup_scaffold\core\Scaffold;
use slicedup_scaffold\tests\mocks\data\MockPost;
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
			'libraries' => array('slicedup_scaffold')
		)
	))
));

class ScaffoldTest extends \lithium\test\Unit {

	public function tearDown() {
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

	public function testName() {
		$expected = 'posts';

		$params = array(
			'controller' => 'app\controllers\PostsController'
		);
		$result = Scaffold::name($params);
		$this->assertEqual($expected, $result);
		$params = array(
			'controller' => 'Posts'
		);
		$result = Scaffold::name($params);
		$this->assertEqual($expected, $result);

		$params = 'app\controllers\PostsController';
		$result = Scaffold::name($params);
		$this->assertEqual($expected, $result);

		$params = array(
			'library' => 'app',
			'controller' => 'Posts'
		);
		$result = Scaffold::name($params);
		$this->assertEqual($expected, $result);

		$params = 'Posts';
		$result = Scaffold::name($params);
		$this->assertEqual($expected, $result);

		$expected = 'lib\posts';
		$params = 'lib\controllers\PostsController';
		$result = Scaffold::name($params);
		$this->assertEqual($expected, $result);

		$params = 'lib\Posts';
		$result = Scaffold::name($params);
		$this->assertEqual($expected, $result);

		$params = array(
			'library' => 'lib',
			'controller' => 'Posts'
		);
		$result = Scaffold::name($params);
		$this->assertEqual($expected, $result);
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
			'controller' => 'slicedup_scaffold\tests\mocks\controllers\MockController'
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
		$expected = 'slicedup_scaffold\controllers\ScaffoldController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => 'slicedup_scaffold\tests\mocks\controllers\NonExistentController'
		);
		Scaffold::set('posts', $config);
		$expected = 'slicedup_scaffold\controllers\ScaffoldController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => 'NonExistentController'
		);
		Scaffold::set('posts', $config);
		$expected = 'slicedup_scaffold\controllers\ScaffoldController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => 'slicedup_scaffold\tests\mocks\controllers\MockController'
		);
		Scaffold::set('posts', $config);
		$expected = $config['controller'];
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => 'MockController'
		);
		Scaffold::set('posts', $config);
		$expected = 'slicedup_scaffold\tests\mocks\controllers\MockController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));
	}

	public function testModelGetter() {
		Scaffold::set('posts');
		$result = Scaffold::get('posts');
		$this->assertNull($result['model']);
		$expected = 'slicedup_scaffold\models\Scaffolds';
		$this->assertIdentical($expected, Scaffold::model('posts'));

		$config = array(
			'model' => 'slicedup_scaffold\tests\mocks\models\NonExistentModel'
		);
		Scaffold::set('posts', $config);
		$expected = 'slicedup_scaffold\models\Scaffolds';
		$this->assertIdentical($expected, Scaffold::model('posts'));

		$config = array(
			'model' => 'NonExistentModel'
		);
		Scaffold::set('posts', $config);
		$expected = 'slicedup_scaffold\models\Scaffolds';
		$this->assertIdentical($expected, Scaffold::model('posts'));

		$config = array(
			'model' => 'slicedup_scaffold\tests\mocks\data\MockPost'
		);
		Scaffold::set('posts', $config);
		$expected = $config['model'];
		$this->assertIdentical($expected, Scaffold::model('posts'));

		$config = array(
			'model' => 'MockPost'
		);
		Scaffold::set('posts', $config);
		$expected = 'slicedup_scaffold\tests\mocks\data\MockPost';
		$this->assertIdentical($expected, Scaffold::model('posts'));
		$meta = $expected::meta();
		$this->assertEqual('MockPost', $meta['name']);
		$this->assertEqual('mock_posts', $meta['source']);
	}

	public function testControllerPrepare() {
		$params = $this->_request();
		$options = array('request' => $params['request']) + $params['options'];
		$controller = Libraries::instance('controllers', Scaffold::controller('posts'), $options);

		$this->assertNull($controller->scaffold);
		Scaffold::prepare('posts', $controller, $params);
		$this->assertTrue(!empty($controller->scaffold));
		$this->assertIdentical('slicedup_scaffold\controllers\ScaffoldController', $controller->scaffold['controller']);
		$this->assertIdentical('posts', $controller->scaffold['name']);
	}

	public function testControllerInvoke() {
		$expected = 'slicedup_scaffold\tests\mocks\controllers\MockController';
		$config = array(
			'controller' => $expected
		);
		Scaffold::set('posts', $config);
		$params = $this->_request();
		$options = array('request' => $params['request']) + $params['options'];
		$controller = Libraries::instance('controllers', Scaffold::controller('posts'), $options);
		Scaffold::prepare('posts', $controller, $params);
		$this->assertTrue($controller instanceOf $expected);

		$params = array();
		$scaffold = Scaffold::callable($controller, $params);

		$expected = 'slicedup_scaffold\controllers\ScaffoldController';
		$this->assertTrue($scaffold instanceOf $expected);

		$expected = Scaffold::invoke($controller);
		$result = Scaffold::call($controller, $params);
		$this->assertEqual($expected, $result);

		$params = $this->_request();
		$result = $controller($params['request'], $params['params']);
		$this->assertEqual($expected, $result);
	}

	public function testModelCalls(){
		$model = Scaffold::model('posts');

		$expected = Scaffold::getAllFields($model);
		$result = Scaffold::getUpdateFields($model);
		$this->assertIdentical($expected, $result);

		$expected = Scaffold::getFormFields($model);
		$result = Scaffold::getUpdateFormFields($model);
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