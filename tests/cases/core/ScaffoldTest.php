<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\tests\cases\core;

use lithium\action\Request;
use lithium\net\http\Router;
use lithium\action\Dispatcher;
use lithium\core\Libraries;
use lithium\util\Set;
use lithium\data\Connections;
use lithium\tests\mocks\data\MockPost;
use sli_scaffold\core\Scaffold;


Libraries::paths(array(
	'controllers' => Set::merge(Libraries::paths('controllers'), array(
		'{:library}\tests\mocks\controllers\{:name}' => array(
			'libraries' => array('sli_scaffold')
		)
	)),
	'models' => Set::merge((array) Libraries::paths('models'), array(
		'{:library}\tests\mocks\data\{:name}' => array(
			'libraries' => array('sli_scaffold')
		)
	))
));

class ScaffoldTest extends \lithium\test\Unit {


	protected $_configs = array();

	protected $_scaffold = array();

	public function _init() {
		Connections::add('mock-source', array('type' => '\sli_base\tests\mocks\data\MockSource'));
	}

	public function setUp() {
		MockPost::config(array('connection' => 'mock-source'));
		$this->_scaffold = Scaffold::config();
		if (!empty($this->_scaffold['scaffold'])) {
			foreach ($this->_scaffold['scaffold'] as $name => $scaffold) {
				Scaffold::set($name, false);
			}
		}
		Scaffold::config(array(
			'all' => true,
			'paths' => true,
			'prefixes' => array(
				'default' => ''
			),
			'actions' => array(
				'index',
				'view',
				'add',
				'edit',
				'delete',
				'display'
			),
			'connection' => 'mock-source'
		));
	}

	public function tearDown() {
		$config = Scaffold::config();
		Scaffold::config(array('all' => true));
		if (!empty($config['scaffold'])) {
			foreach ($config['scaffold'] as $name => $scaffold) {
				Scaffold::set($name, false);
			}
		}
		Scaffold::config($this->_scaffold);
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

	public function testDetect() {
		$expected = 'posts';

		$params = array(
			'controller' => 'app\controllers\PostsController'
		);
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);

		$params = 'app\controllers\PostsController';
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);

		$params = array(
			'controller' => 'Posts'
		);
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);

		$params = 'Posts';
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);
	}

	public function testDetectLibs() {
		$expected = 'app.posts';

		$params = array(
			'library' => 'app',
			'controller' => 'app\controllers\PostsController'
		);
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);

		$params = array(
			'controller' => 'app.app\controllers\PostsController'
		);
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);

		$params = array(
			'library' => 'app',
			'controller' => 'PostsController'
		);
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);

		$params = array(
			'controller' => 'app.Posts'
		);
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);

		$params = array(
			'controller' => 'app.PostsController'
		);
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);

		$params = array(
			'library' => 'app',
			'controller' => 'Posts'
		);
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);

		$params = 'app.app\controllers\PostsController';
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);

		$params = 'app.PostsController';
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);

		$params = 'app.Posts';
		$result = Scaffold::detect($params);
		$this->assertEqual($expected, $result);
	}

	public function testScaffoldConfig() {
		$config = Scaffold::config();
		$newConfig = array(
			'scaffold' => array(
				'posts',
				'pages',
				'app.comments' => array(
					'this' => 'that'
				)
			)
		);
		$expected = array_merge($config, array(
			'scaffold' => array(
				'posts' => array(
					'name' => 'posts',
					'_name' => 'posts',
					'_library' => null
				),
				'pages' => array(
					'name' => 'pages',
					'_name' => 'pages',
					'_library' => null
				),
				'app.comments' => array(
					'name' => 'app.comments',
					'_name' => 'comments',
					'_library' => 'app',
					'this' => 'that'
				)
			)
		));
		$result = Scaffold::config($newConfig);
		$this->assertIdentical($expected, $result);
	}

	public function testScaffoldSetGet() {
		$config = array(
			'controller' => 'sli_scaffold\tests\mocks\controllers\MockController'
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
		$expected = 'sli_scaffold\controllers\ScaffoldsController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => 'sli_scaffold\tests\mocks\controllers\NonExistentController'
		);
		Scaffold::set('posts', $config);
		$expected = 'sli_scaffold\controllers\ScaffoldsController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => 'NonExistentController'
		);
		Scaffold::set('posts', $config);
		$expected = 'sli_scaffold\controllers\ScaffoldsController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => 'sli_scaffold\tests\mocks\controllers\MockController'
		);
		Scaffold::set('posts', $config);
		$expected = $config['controller'];
		$this->assertIdentical($expected, Scaffold::controller('posts'));

		$config = array(
			'controller' => 'MockController'
		);
		Scaffold::set('posts', $config);
		$expected = 'sli_scaffold\tests\mocks\controllers\MockController';
		$this->assertIdentical($expected, Scaffold::controller('posts'));
	}

	public function testModelGetter() {
		Scaffold::set('mock_posts', array());
		$expected = 'sli_scaffold\tests\mocks\data\MockPost';
		$result = Scaffold::model('mock_posts');
		$this->assertIdentical($expected, $result);
		$result = Scaffold::get('mock_posts');
		$this->assertIdentical($expected, $result['model']);

		$config = array(
			'model' => 'sli_scaffold\tests\mocks\models\NonExistentModel'
		);
		Scaffold::set('mock_posts', $config);
		$expected = 'sli_scaffold\models\Scaffolds';
		$this->assertIdentical($expected, Scaffold::model('mock_posts'));

		$config = array(
			'model' => 'NonExistentModel'
		);
		Scaffold::set('mock_posts', $config);
		$expected = 'sli_scaffold\models\Scaffolds';
		$this->assertIdentical($expected, Scaffold::model('mock_posts'));

		$config = array(
			'model' => 'sli_scaffold\tests\mocks\data\MockPost'
		);
		Scaffold::set('mock_posts', $config);
		$expected = $config['model'];
		$this->assertIdentical($expected, Scaffold::model('mock_posts'));

		$config = array(
			'model' => 'MockPost'
		);
		Scaffold::set('mock_posts', $config);
		$expected = 'sli_scaffold\tests\mocks\data\MockPost';
		$this->assertIdentical($expected, Scaffold::model('mock_posts'));
		$meta = $expected::meta();
		$this->assertEqual('MockPost', $meta['name']);
		$this->assertEqual('mock_posts', $meta['source']);
	}

	public function testHandledActions() {
		Scaffold::config(array(
			'scaffold' => array(
				'posts',
				'pages' => array(
					'actions' => array('display')
				)
			)
		));

		$result = Scaffold::handledAction('posts', 'index');
		$this->assertTrue($result);

		$result = Scaffold::handledAction('posts', 'view');
		$this->assertTrue($result);

		$result = Scaffold::handledAction('posts', 'add');
		$this->assertTrue($result);

		$result = Scaffold::handledAction('posts', 'edit');
		$this->assertTrue($result);

		$result = Scaffold::handledAction('posts', 'delete');
		$this->assertTrue($result);

		$result = Scaffold::handledAction('posts', 'display');
		$this->assertTrue($result);

		$result = Scaffold::handledAction('pages', 'index');
		$this->assertFalse($result);

		$result = Scaffold::handledAction('pages', 'view');
		$this->assertFalse($result);

		$result = Scaffold::handledAction('pages', 'add');
		$this->assertFalse($result);

		$result = Scaffold::handledAction('pages', 'edit');
		$this->assertFalse($result);

		$result = Scaffold::handledAction('pages', 'delete');
		$this->assertFalse($result);

		$result = Scaffold::handledAction('pages', 'display');
		$this->assertTrue($result);
	}

	public function testPrefixes() {
		Scaffold::config(array(
			'prefixes' => array(
				'default' => '',
				'admin' => 'admin_'
			),
			'scaffold' => array(
				'posts',
				'pages' => array(
					'prefixes' => array('admin')
				),
				'users' => array(
					'prefixes' => array('default' => '')
				)
			)
		));

		$expected = array(
			'default' => '',
			'admin' => 'admin_'
		);
		$result = Scaffold::invokeMethod('_prefixes');
		$this->assertEqual($expected, $result);

		$expected = array('default', 'admin');
		$result = Scaffold::invokeMethod('_prefixes', array(array(), true));
		$this->assertEqual($expected, $result);

		$expected = array(
			'default' => '',
			'admin' => 'admin_'
		);
		$result = Scaffold::invokeMethod('_prefixes', array(Scaffold::get('posts')));
		$this->assertEqual($expected, $result);

		$expected = array('default', 'admin');
		$result = Scaffold::invokeMethod('_prefixes', array(Scaffold::get('posts'), true));
		$this->assertEqual($expected, $result);

		$expected = array(
			'admin' => 'admin_'
		);
		$result = Scaffold::invokeMethod('_prefixes', array(Scaffold::get('pages')));
		$this->assertEqual($expected, $result);

		$expected = array('admin');
		$result = Scaffold::invokeMethod('_prefixes', array(Scaffold::get('pages'), true));
		$this->assertEqual($expected, $result);

		$expected = array(
			'default' => ''
		);
		$result = Scaffold::invokeMethod('_prefixes', array(Scaffold::get('users')));
		$this->assertEqual($expected, $result);

		$expected = array('default');
		$result = Scaffold::invokeMethod('_prefixes', array(Scaffold::get('users'), true));
		$this->assertEqual($expected, $result);
	}

	public function testHandledActionPrefixes() {
		Scaffold::config(array(
			'prefixes' => array(
				'default' => '',
				'admin' => 'admin_'
			),
			'scaffold' => array(
				'posts',
				'pages' => array(
					'prefixes' => array('admin'),
					'actions' => array(
						'index' => array('default'),
						'add'
					)
				),
				'users' => array(
					'actions' => array(
						'view' => array('default'),
						'add' => array('admin')
					)
				)
			)
		));

		$result = Scaffold::handledAction('posts', 'index', 'default');
		$this->assertTrue($result);

		$result = Scaffold::handledAction('posts', 'index', 'admin');
		$this->assertTrue($result);

		$result = Scaffold::handledAction('pages', 'index', 'default');
		$this->assertTrue($result);

		$result = Scaffold::handledAction('pages', 'index', 'admin');
		$this->assertFalse($result);

		$result = Scaffold::handledAction('pages', 'add', 'default');
		$this->assertFalse($result);

		$result = Scaffold::handledAction('pages', 'add', 'admin');
		$this->assertTrue($result);

		$result = Scaffold::handledAction('users', 'view', 'default');
		$this->assertTrue($result);

		$result = Scaffold::handledAction('users', 'view', 'admin');
		$this->assertFalse($result);

		$result = Scaffold::handledAction('users', 'add', 'default');
		$this->assertFalse($result);

		$result = Scaffold::handledAction('users', 'add', 'admin');
		$this->assertTrue($result);
	}

	public function testControllerPrepare() {
		$params = $this->_request();
		$options = array('request' => $params['request']) + $params['options'];
		$controller = Libraries::instance('controllers', Scaffold::controller('posts'), $options);
		$this->assertNull($controller->scaffold);

		Scaffold::prepare('posts', $controller, $params);
		$result = empty($controller->scaffold);
		$this->assertFalse($result);

		$expected = array('name' => 'posts', 'prefix' => 'default') + Scaffold::get('posts');
		$result = $controller->scaffold;
		$this->assertEqual($expected, $result);

		$expected = 'sli_scaffold\controllers\ScaffoldsController';
		$result = $controller->scaffold['controller'];
		$this->assertEqual($expected, $result);
	}

	public function testControllerInvoke() {
		$expected = 'sli_scaffold\tests\mocks\controllers\MockController';
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
		$scaffold = Scaffold::invokeMethod('_callable', array($controller, $params));

		$expected = 'sli_scaffold\controllers\ScaffoldsController';
		$this->assertTrue($scaffold instanceOf $expected);

		$expected = Scaffold::invokeMethod('_invoke', array($controller));
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
		Router::connect('/{:controller}/{:action}/{:args}');
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