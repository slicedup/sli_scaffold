<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\tests\cases\extensions\helper;

use sli_scaffold\extensions\helper\Scaffold as ScaffoldHelper;
use sli_scaffold\core\Scaffold;
use lithium\action\Request;
use lithium\net\http\Router;
use lithium\data\entity\Record;
use lithium\tests\mocks\template\helper\MockFormRenderer;

class ScaffoldBaseTest extends \lithium\test\Unit {

	protected $_model = 'lithium\tests\mocks\template\helper\MockFormPost';

	public $helper = null;

	public $context = null;

	public $base = null;

	protected $_routes = array();

	/**
	 * Initialize test by creating a new object instance with a default context.
	 *
	 */
	public function setUp() {
		$this->_routes = Router::get();
		Router::reset();
		Router::connect('/{:controller}/{:action}/{:id}.{:type}', array('id' => null));
		Router::connect('/{:controller}/{:action}/{:args}');

		$request = new Request();
		$request->params = array('controller' => 'posts', 'action' => 'index');
		$request->persist = array('controller');

		$this->context = new MockFormRenderer(compact('request'));
		$this->helper = new ScaffoldHelper(array('context' => $this->context));

		$base = trim($this->context->request()->env('base'), '/') . '/';
		$this->base = ($base == '/') ? $base : '/' . $base;
	}

	public function tearDown() {
		Router::reset();

		foreach ($this->_routes as $route) {
			Router::connect($route);
		}
	}
}

?>