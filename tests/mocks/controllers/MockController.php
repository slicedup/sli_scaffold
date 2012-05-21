<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_scaffold\tests\mocks\controllers;

class MockController extends \lithium\action\Controller {

	public $scaffold;

	public function _scaffold($controller) {
		$controller->applyFilter('index', function($self, $params, $chain){
			$params = $chain->next($self, $params, $chain);
			$params['recordSet'] = array();
			return $params;
		});
	}

}

?>