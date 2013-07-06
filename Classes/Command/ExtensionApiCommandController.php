<?php

/* * *************************************************************
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
 * ************************************************************* */

/**
 * Extension API Command Controller
 *
 * @package TYPO3
 * @subpackage tx_coreapi
 */
class Tx_Coreapi_Command_ExtensionApiCommandController extends Tx_Extbase_MVC_Controller_CommandController {

	/**
	 * Information about an extension
	 *
	 * @param string $key extension key
	 * @return void
	 */
	public function infoCommand($key) {
		$data = array();
		try {
			/** @var $service Tx_Coreapi_Service_ExtensionApiService */
			$service = $this->objectManager->get('Tx_Coreapi_Service_ExtensionApiService');
			$data = $service->getExtensionInformation($key);
		} catch (Exception $e) {
			$this->outputLine($e->getMessage());
			$this->quit();
		}

		$this->outputLine('');
		$this->outputLine('EXTENSION "%s": %s %s', array(strtoupper($key), $data['em_conf']['version'], $data['em_conf']['state']));
		$this->outputLine(str_repeat('-', self::MAXIMUM_LINE_LENGTH));

		$outputInformation = array();
		$outputInformation['is installed'] = ($data['is_installed'] ? 'yes' : 'no');
		foreach($data['em_conf'] as $emConfKey => $emConfValue) {
				// Skip empty properties
			if (empty($emConfValue)) {
				continue;
			}
				// Skip properties which are already handled
			if ($emConfKey === 'title' || $emConfKey === 'version' || $emConfKey === 'state') {
				continue;
			}
			$outputInformation[$emConfKey] = $emConfValue;
		}

		foreach ($outputInformation as $outputKey => $outputValue) {
			$description = '';
			if (is_array($outputValue)) {
				foreach ($outputValue as $additionalKey => $additionalValue) {
					if (is_array($additionalValue)) {

						if (empty($additionalValue))  {
							continue;
						}
						$description .= LF . str_repeat(' ', 28) . $additionalKey;
						$description .= LF;
						foreach ($additionalValue as $ak => $av) {
							$description .= str_repeat(' ', 30) . $ak . ': ' . $av . LF;
						}
					} else {
						$description .= LF . str_repeat(' ', 28) . $additionalKey . ': '. $additionalValue;
					}
				}
			} else {
				$description = wordwrap($outputValue, self::MAXIMUM_LINE_LENGTH - 28, PHP_EOL . str_repeat(' ', 28), TRUE);
			}
			$this->outputLine('%-2s%-25s %s', array(' ', $outputKey, $description));
		}
	}

	/**
	 * List all installed extensions
	 *
	 * @param string $type Extension type, can either be L for local, S for system or G for global. Leave it empty for all
	 * @return void
	 */
	public function listInstalledCommand($type = '') {
		$type = strtoupper($type);
		if (!empty($type) && $type !== 'L' && $type !== 'G' && $type !== 'S') {
			$this->outputLine('Only "L", "S" and "G" are supported as type (or nothing)');
			$this->quit();
		}

		/** @var $extensions Tx_Coreapi_Service_ExtensionApiService */
		$extensions = $this->objectManager->get('Tx_Coreapi_Service_ExtensionApiService')->getInstalledExtensions($type);

		foreach ($extensions as $key => $details) {
			$title = $key . ' - ' . $details['version'] . '/' . $details['state'];
			$description = $details['title'];
			$description = wordwrap($description, self::MAXIMUM_LINE_LENGTH - 43, PHP_EOL . str_repeat(' ', 43), TRUE);
			$this->outputLine('%-2s%-40s %s', array(' ', $title, $description));
		}

		$this->outputLine('%-2s%-40s', array(' ', str_repeat('-', self::MAXIMUM_LINE_LENGTH - 3)));
		$this->outputLine('  Total: ' . count($extensions) . ' extensions');
	}

	/**
	 * Update list
	 *
	 * @return void
	 */
	public function updateListCommand() {
		/** @var $service Tx_Coreapi_Service_ExtensionApiService */
		$service = $this->objectManager->get('Tx_Coreapi_Service_ExtensionApiService');
		$service->updateMirrors();

		$this->outputLine('Extension list has been updated.');
	}

	/**
	 * createUploadFoldersCommand
	 *
	 * @return void
	 */
	public function createUploadFoldersCommand() {
		/** @var $service Tx_Coreapi_Service_ExtensionApiService */
		$service = $this->objectManager->get('Tx_Coreapi_Service_ExtensionApiService');
		$messages = $service->createUploadFolders();

		if (sizeof($messages)) {
			foreach ($messages as $message) {
				$this->outputLine($message);
			}
		} else {
			$this->outputLine('no uploadFolder created');
		}
	}

}

?>