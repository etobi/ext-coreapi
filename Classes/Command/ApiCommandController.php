<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Georg Ringer <georg.ringer@cyberhouse.at>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * API Command Controller
 *
 * @package TYPO3
 * @subpackage tx_coreapi
 */
class Tx_Coreapi_Command_ApiCommandController extends Tx_Extbase_MVC_Controller_CommandController {

	/**
	 * Database compare
	 *
	 * Leave the argument 'actions' empty to see the available ones
	 *
	 * @param string $actions List of actions which will be executed
	 */
	public function databaseCompareCommand($actions = '') {
		$availableActions = array_flip($this->objectManager->get('Tx_Extbase_Reflection_ClassReflection', 'Tx_Coreapi_Service_DatabaseApiService')->getConstants());

		if (empty($actions)) {
			$this->outputLine('Available actions are:');
			foreach ($availableActions as $number => $action) {
				if (t3lib_div::isFirstPartOfStr($action, 'ACTION_')) {
					$this->outputLine('  - ' . $action . ' => ' . $number);
				}
			}
			$this->quit();
		}

		/** @var $service Tx_Coreapi_Service_DatabaseApiService */
		$service = $this->objectManager->get('Tx_Coreapi_Service_DatabaseApiService');
		$allowedActions = array();
		$actionSplit = t3lib_div::trimExplode(',', $actions);
		foreach($actionSplit as $split) {
			if (!isset($availableActions[$split])) {
				$this->output('Action "%s" is not available!', array($split));
				$this->quit();
			}
			$allowedActions[$split] = 1;
		}

		$result = $service->databaseCompare($allowedActions);
		if (empty($result)) {
			$this->outputLine('DB has been compared');
		} else {
			$this->outputLine('DB could not be compared, Error(s): %s', array(LF . implode(LF, $result)));
			$this->quit();
		}
	}

}

?>