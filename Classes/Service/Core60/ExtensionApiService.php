<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Alexander Opitz <opitz@pluspol.info>
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
 * Extension API service
 *
 * @package TYPO3
 * @subpackage tx_coreapi
 */
class Tx_Coreapi_Service_Core60_ExtensionApiService implements Tx_Coreapi_Service_ExtensionApiServiceInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Get information about an extension
	 *
	 * @param string $extensionKey extension key
	 * @return array
	 * @author Christoph Lehmann <post@christophlehmann.eu>
	 */
	public function getExtensionInformation($extensionKey) {
		$oldService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("Tx_Coreapi_Service_Core45_ExtensionApiService");
		return $oldService->getExtensionInformation($extensionKey);
	}

	/**
	 * Get array of installed extensions
	 *
	 * @param string $type L, S, G or empty (for all)
	 * @return array
	 * @throws InvalidArgumentException
	 */
	public function getInstalledExtensions($type = '') {
		$type = strtoupper($type);

		if (!empty($type)) {
			switch ($type) {
				case 'L':
					$type = 'Local';
					break;
				case 'S':
					$type = 'System';
					break;
				case 'G':
					$type = 'Global';
					break;
				default:
					throw new InvalidArgumentException('Only "L", "S", "G" and "" (all) are supported as type');
			}
		}

		$list = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility');
		$extensions = $list->getAvailableAndInstalledExtensionsWithAdditionalInformation();

		if (!empty($type)) {
			foreach ($extensions as $key => $extension) {
				if ($type !== $extension['type']) {
					unset($extensions[$key]);
				}
			}
		}

		ksort($extensions);
		return $extensions;
	}

	/**
	 * Update the mirrors
	 *
	 * @return void
	 * @author Christoph Lehmann <post@christophlehmann.eu>
	 * @throws RuntimeException
	 */
	public function updateMirrors() {
		/** @var $repositoryHelper \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper */
		$repositoryHelper = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper');
		$repositoryHelper->updateExtList();
	}

	/**
	 * createUploadFolders
	 *
	 * @return array
	 */
	public function createUploadFolders() {
		$extensions = $this->getInstalledExtensions();

		$fileHandlingUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility');
		foreach ($extensions as $key => $extension) {
			$extension['key'] = $key;
			$fileHandlingUtility->ensureConfiguredDirectoriesExist($extension);
		}
		return array(
			'done with \\TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility->ensureConfiguredDirectoriesExist'
		);
	}


	/**
	 * Install (load) an extension
	 *
	 * @param string $extensionKey extension key
	 * @return void
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public function installExtension($extensionKey) {
		// checks if extension exists
		if (!$this->extensionExists($extensionKey)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" does not exist!', $extensionKey));
		}

		// check if extension is already loaded
		if (t3lib_extMgm::isLoaded($extensionKey)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" already installed!', $extensionKey));
		}

		// check if localconf.php is writable
		if (!t3lib_extMgm::isLocalconfWritable()) {
			throw new RuntimeException('localconf.php is not writeable!');
		}

		$installUtility = $this->getInstallUtility();
		$installUtility->install($extensionKey);
	}

	/**
	 * Uninstall (unload) an extension
	 *
	 * @param string $extensionKey extension key
	 * @return void
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public function uninstallExtension($extensionKey) {
		// check if extension is this extension (coreapi)
		if ($extensionKey == 'coreapi') {
			throw new InvalidArgumentException(sprintf('Extension "%s" cannot be uninstalled!', $extensionKey));
		}

		// checks if extension exists
		if (!$this->extensionExists($extensionKey)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" does not exist!', $extensionKey));
		}

		// check if extension is loaded
		if (!t3lib_extMgm::isLoaded($extensionKey)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" is not installed!', $extensionKey));
		}

		// check if localconf.php is writable
		if (!t3lib_extMgm::isLocalconfWritable()) {
			throw new RuntimeException('localconf.php is not writeable!');
		}

		$installUtility = $this->getInstallUtility();
		$installUtility->uninstall($extensionKey);
	}

	/**
	 * Configure an extension
	 *
	 * @param string $extensionKey extension key
	 * @param array $extensionConfiguration
	 * @return void
	 * @author Christoph Lehmann <post@christophlehmann.eu>
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public function configureExtension($extensionKey, $extensionConfiguration = array()) {
		// check if extension is loaded
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" is not installed!', $extensionKey));
		}

		$extAbsPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey);
		$extConfTemplateFile = $extAbsPath . 'ext_conf_template.txt';
		if (!file_exists($extConfTemplateFile)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" has no configuration options!', $extensionKey));
		}

		$rawConfigurationString = file_get_contents($extConfTemplateFile);

		$tsStyleConfig = $this->objectManager->get('TYPO3\\CMS\\Core\\TypoScript\\ConfigurationForm');
		$tsStyleConfig->doNotSortCategoriesBeforeMakingForm = TRUE;

		$constants = array();
		$extRelPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($extensionKey);($extensionKey);
		$constants = $tsStyleConfig->ext_initTSstyleConfig(
			$rawConfigurationString,
			$extRelPath,
			$extAbsPath,
			$GLOBALS['BACK_PATH']
		);

		// check for unknown configuration settings
		foreach ($extensionConfiguration as $key => $value) {
			if (!isset($constants[$key])) {
				throw new InvalidArgumentException(sprintf('No configuration setting with name "%s" for extension "%s"!', $key, $extensionKey));
			}
		}

		// get existing configuration
		$configurationArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey]);
		$configurationArray = is_array($configurationArray) ? $configurationArray : array();

		// fill with missing values
		foreach (array_keys($constants) as $key) {
			if (!isset($extensionConfiguration[$key])) {
				if (isset($configurationArray[$key])) {
					$extensionConfiguration[$key] = $configurationArray[$key];
				} else {
					if (!empty($constants[$key]['value'])) {
						$extensionConfiguration[$key] = $constants[$key]['value'];
					} else {
						$extensionConfiguration[$key] = $constants[$key]['default_value'];
					}
				}
			}
		}

		// process incoming configuration
		// values are checked against types in $constants
		$tsStyleConfig->ext_procesInput(array('data' => $extensionConfiguration), array(), $constants, array());

		// current configuration is merged with incoming configuration
		$configurationArray = $tsStyleConfig->ext_mergeIncomingWithExisting($configurationArray);

		// Write configuration and clear caches
		$configurationUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility');
		$configurationUtility->writeConfiguration($configurationArray, $extensionKey);
	}

	/**
	 * Fetch an extension from TER
	 *
	 * @param $extensionKey
	 * @param string $version
	 * @param string $location
	 * @param bool $overwrite
	 * @param string $mirror
	 * @return array
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public function fetchExtension($extensionKey, $version = '', $location = 'L', $overwrite = FALSE, $mirror = '') {
		throw new RuntimeException('This feature is not available in this TYPO3 version (yet)!');
	}

	/**
	 * Imports extension from file
	 *
	 * @param string $file path to t3x file
	 * @param string $location where to import the extension. S = typo3/sysext, G = typo3/ext, L = typo3conf/ext
	 * @param bool $overwrite overwrite the extension if it already exists
	 * @throws RuntimeException
	 * @return void
	 */
	public function importExtension($file, $location = 'L', $overwrite = FALSE) {
		// TODO
		throw new RuntimeException('This feature is not available in this TYPO3 version (yet)!');
	}

	/**
	 * Check if an extension exists
	 *
	 * @param string $extensionKey extension key
	 * @return void
	 */
	protected function extensionExists($extensionKey) {
		return $this->getInstallUtility()->isAvailable($extensionKey);
	}

	/**
	 * @return \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
	 */
	protected function getInstallUtility() {
		return $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility');
	}
}

?>