<?php
namespace Etobi\CoreAPI\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Georg Ringer <georg.ringer@cyberhouse.at>
 *  (c) 2014 Stefano Kowalke <blueduck@gmx.net>
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
use InvalidArgumentException;
use RuntimeException;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * Extension API service
 *
 * @author Georg Ringer <georg.ringer@cyberhouse.at>
 * @author Stefano Kowalke <blueduck@gmx.net>
 * @package Etobi\CoreAPI\Service\SiteApiService
 */
class ExtensionApiService {

	/*
	 * some ExtensionManager Objects require public access to these objects
	 */
	/** @var tx_em_Tools_XmlHandler */
	public $xmlHandler;

	/** @var tx_em_Connection_Ter */
	public $terConnection;

	/** @var tx_em_Extensions_Details */
	public $extensionDetails;

	/**
	 * @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/** @var $extensionList \TYPO3\CMS\Extensionmanager\Utility\ListUtility */
	public $listUtility;

	/** @var $installUtility \TYPO3\CMS\Extensionmanager\Utility\InstallUtility */
	protected $installUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository $repositoryRepository
	 */
	protected $repositoryRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper
	 */
	protected $repositoryHelper;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $extensionManagementService
	 */
	protected $extensionManagementService;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility
	 * @inject
	 */
	protected $fileHandlingUtility;

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository $repositoryRepository
	 */
	public function injectRepositoryRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository $repositoryRepository) {
		$this->repositoryRepository = $repositoryRepository;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper
	 */
	public function injectRepositoryHelper(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper) {
		$this->repositoryHelper = $repositoryHelper;

	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
	 */
	public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository){
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $extensionManagementService
	 */
	public function injectExtensionManagementService(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $extensionManagementService) {
		$this->extensionManagementService = $extensionManagementService;

	}

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	public function __construct() {
		$this->configurationManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
	}

	/**
	 * Get information about an extension.
	 *
	 * @param string $extensionKey extension key
	 *
	 * @throws InvalidArgumentException
	 * @return array
	 */
	public function getExtensionInformation($extensionKey) {
		global $EM_CONF;

		if (strlen($extensionKey) === 0) {
			throw new InvalidArgumentException('No extension key given!');
		}
		if (!$GLOBALS['TYPO3_LOADED_EXT'][$extensionKey]) {
			throw new InvalidArgumentException(sprintf('Extension "%s" not found!', $extensionKey));
		}

		include_once(ExtensionManagementUtility::extPath($extensionKey) . 'ext_emconf.php');
		$information = array(
			'em_conf' => $EM_CONF[''],
			'is_installed' => ExtensionManagementUtility::isLoaded($extensionKey)
		);

		return $information;
	}

	/**
	 * Get array of installed extensions.
	 *
	 * @param string $type Local, System, Global or empty (for all)
	 *
	 * @throws InvalidArgumentException
	 * @return array
	 */
	public function getInstalledExtensions($type = '') {
		global $EM_CONF;

		$type = ucfirst(strtolower($type));
		if (!empty($type) && $type !== 'Local' && $type !== 'Global' && $type !== 'System') {
			throw new InvalidArgumentException('Only "Local", "System", "Global" and "" (all) are supported as type');
		}

		$extensions = $GLOBALS['TYPO3_LOADED_EXT'];

		$list = array();
		foreach ($extensions as $key => $extension) {
			if (!empty($type) && $type{0} !== $extension['type']) {
				continue;
			}

			include_once(ExtensionManagementUtility::extPath($key) . 'ext_emconf.php');
			$list[$key] = $EM_CONF[''];
		}

		ksort($list);
		return $list;
	}

	/**
	 * Update the mirrors, using the scheduler task of EXT:em.
	 *
	 * @throws RuntimeException
	 * @return boolean
	 */
	public function updateMirrors() {
		$result = FALSE;
		$repositories = $this->repositoryRepository->findAll();

		// update all repositories
		foreach ($repositories as $repository) {
			$this->repositoryHelper->setRepository($repository);
			$result = $this->repositoryHelper->updateExtList();
			unset($objRepository, $this->repositoryHelper);
		}

		return $result;
	}

	/**
	 * Creates the upload folders of an extension.
	 *
	 * @return array
	 */
	public function createUploadFolders() {
		$extensions = $this->getInstalledExtensions();

		$result = array();
		if (class_exists('\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility')) {
			$fileHandlingUtility = GeneralUtility::makeInstance('TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility');
			foreach ($extensions AS $key => $extension) {
				$extension['key'] = $key;
				$fileHandlingUtility->ensureConfiguredDirectoriesExist($extension);
			}
			$result[] = 'done with \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility->ensureConfiguredDirectoriesExist';
		}

		return $result;
	}


	/**
	 * Install (load) an extension.
	 *
	 * @param string $extensionKey extension key
	 *
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 * @return void
	 */
	public function installExtension($extensionKey) {
//		throw new RuntimeException('This feature is blocked by http://forge.typo3.org/issues/58288');

		// checks if extension exists
		if (!$this->extensionExists($extensionKey)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" does not exist!', $extensionKey));
		}

		$this->installUtility->install($extensionKey);
	}

	/**
	 * Uninstall (unload) an extension.
	 *
	 * @param string $extensionKey extension key
	 *
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 * @return void
	 */
	public function uninstallExtension($extensionKey) {
		if ($extensionKey === 'coreapi') {
			throw new InvalidArgumentException(sprintf('Extension "%s" cannot be uninstalled!', $extensionKey));
		}

		// checks if extension exists
		if (!$this->extensionExists($extensionKey)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" does not exist!', $extensionKey));
		}

		// check if extension is loaded
		if (!ExtensionManagementUtility::isLoaded($extensionKey)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" is not installed!', $extensionKey));
		}

		$this->installUtility->uninstall($extensionKey);
	}

	/**
	 * Configure an extension.
	 *
	 * @param string $extensionKey           The extension key
	 * @param array  $extensionConfiguration
	 *
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 * @return void
	 */
	public function configureExtension($extensionKey, $extensionConfiguration = array()) {
		/** @var $configurationUtility \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility */
		$configurationUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility');

		// check if extension exists
		if (!$this->extensionExists($extensionKey)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" does not exist!', $extensionKey));
		}

		// check if extension is loaded
		if (!ExtensionManagementUtility::isLoaded($extensionKey)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" is not installed!', $extensionKey));
		}

		// check if extension can be configured
		$extAbsPath = ExtensionManagementUtility::extPath($extensionKey);

		$extConfTemplateFile = $extAbsPath . 'ext_conf_template.txt';
		if (!file_exists($extConfTemplateFile)) {
			throw new InvalidArgumentException(sprintf('Extension "%s" has no configuration options!', $extensionKey));
		}

		// checks if conf array is empty
		if (empty($extensionConfiguration)) {
			throw new InvalidArgumentException(sprintf('No configuration for extension "%s"!', $extensionKey));
		}

		$constants = $configurationUtility->getDefaultConfigurationFromExtConfTemplateAsValuedArray($extensionKey);

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

		// write configuration to typo3conf/localconf.php
		$configurationUtility->writeConfiguration($extensionConfiguration, $extensionKey);
	}

	/**
	 * Fetch an extension from TER.
	 *
	 * @param string     $extensionKey
	 * @param string     $version
	 * @param string     $location
	 * @param bool       $overwrite
	 * @param int        $mirror
	 *
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function fetchExtension($extensionKey, $version = '', $location = 'Local', $overwrite = FALSE, $mirror = -1) {
		if (!is_numeric($mirror)) {
			throw new InvalidArgumentException('Option --mirror must be a number. Run the command extensionapi:listmirrors to get the list of all available repositories');
		}

		if ($version === '') {
			$extension = $this->extensionRepository->findHighestAvailableVersion($extensionKey);
			if ($extension === NULL) {
				throw new InvalidArgumentException(sprintf('Extension "%s" was not found on TER', $extensionKey));
			}
		} else {
			$extension = $this->extensionRepository->findOneByExtensionKeyAndVersion($extensionKey, $version);
			if ($extension === NULL) {
				throw new InvalidArgumentException(sprintf('Version %s of extension "%s" does not exist', $version, $extensionKey));
			}
		}

		if (!$overwrite) {
			$comingExtPath = $this->fileHandlingUtility->getExtensionDir($extensionKey, $location);
			if (@is_dir($comingExtPath)) {
				throw new InvalidArgumentException(sprintf('Extension "%s" already exists at "%s"!', $extensionKey, $comingExtPath));
			}
		}

		$allowedInstallTypes = $extension->returnAllowedInstallTypes();
		$location = ucfirst(strtolower($location));
		if (!in_array($location, $allowedInstallTypes)) {
			if ($location === 'Global') {
				throw new InvalidArgumentException('Global installation is not allowed!');
			}
			if ($location === 'Local') {
				throw new InvalidArgumentException('Local installation is not allowed!');
			}
			if ($location === 'System') {
				throw new InvalidArgumentException('System installation is not allowed!');
			}
			throw new InvalidArgumentException(sprintf('Unknown location "%s"!', $location));
		}

		$mirrors = $this->repositoryHelper->getMirrors();

		if ($mirrors === NULL) {
			throw new RuntimeException('No mirrors found!');
		}

		if ($mirror === -1) {
			$mirrors->setSelect();
		} elseif ($mirror > 0 && $mirror <= count($mirrors->getMirrors())) {
			$mirrors->setSelect($mirror);
		} else {
			throw new InvalidArgumentException(sprintf('Mirror "%s" does not exist', $mirror));
		}

		$data = $this->extensionManagementService->resolveDependenciesAndInstall($extension);

		if (array_key_exists($extensionKey, $data['installed'])) {
			$return['extKey'] = $extension->getExtensionKey();
			$return['version'] = $extension->getVersion();
		} else {
			throw new RuntimeException('Extension "'. $extensionKey .'" version ' . $extension->getVersion() . ' could not installed!');
		}

		return $return;
	}

	/**
	 * Lists the possible mirrors
	 *
	 * @return array
	 */
	public function listMirrors() {
		/** @var $repositoryHelper \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper */
		$repositoryHelper = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper');
		$mirrors = $repositoryHelper->getMirrors();

		return $mirrors->getMirrors();
	}

	/**
	 * Imports extension from file.
	 *
	 * @param string $file      path to t3x file
	 * @param string $location  where to import the extension. System = typo3/sysext, Global = typo3/ext, Local = typo3conf/ext
	 * @param bool   $overwrite overwrite the extension if it already exists
	 *
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function importExtension($file, $location = 'Local', $overwrite = FALSE) {
//		if (version_compare(TYPO3_version, '4.7.0', '>')) {
//			throw new RuntimeException('This feature is not available in TYPO3 versions > 4.7 (yet)!');
//		}

		$allowedPaths = Extension::returnAllowedInstallPaths();

		$location = ucfirst(strtolower($location));

		$return = array();
		if (!is_file($file)) {
			throw new InvalidArgumentException(sprintf('File "%s" does not exist!', $file));
		}

		if (!array_key_exists($location, $allowedPaths)) {
			if ($location === 'Global') {
				throw new InvalidArgumentException('Global installation is not allowed!');
			}
			if ($location === 'Local') {
				throw new InvalidArgumentException('Local installation is not allowed!');
			}
			if ($location === 'System') {
				throw new InvalidArgumentException('System installation is not allowed!');
			}
			throw new InvalidArgumentException(sprintf('Unknown location "%s"!', $location));
		}

		$fileContent = GeneralUtility::getUrl($file);
		if (!$fileContent) {
			throw new InvalidArgumentException(sprintf('File "%s" is empty!', $file));
		}

		$fetchData = $this->terConnection->decodeExchangeData($fileContent);
		if (!is_array($fetchData)) {
			throw new InvalidArgumentException(sprintf('File "%s" is of a wrong format!', $file));
		}

		$extensionKey = $fetchData[0]['extKey'];
		if (!$extensionKey) {
			throw new InvalidArgumentException(sprintf('File "%s" is of a wrong format!', $file));
		}

		$return['extKey'] = $extensionKey;
		$return['version'] = $fetchData[0]['EM_CONF']['version'];

		if (!$overwrite) {
			$location = ($location === 'G' || $location === 'S') ? $location : 'L';
			$destinationPath = tx_em_Tools::typePath($location) . $extensionKey . '/';
			if (@is_dir($destinationPath)) {
				throw new InvalidArgumentException(sprintf('Extension "%s" already exists at "%s"!', $extensionKey, $destinationPath));
			}
		}

		$install = $this->getEmInstall();
		$content = $install->installExtension($fetchData, $location, null, $file, !$overwrite);

		return $return;
	}


	/**
	 * Check if an extension exists.
	 *
	 * @param string $extensionKey extension key
	 *
	 * @return boolean
	 */
	protected function extensionExists($extensionKey) {
		$this->initializeExtensionManagerObjects();
		$list = $this->listUtility->getAvailableExtensions();
		$extensionExists = FALSE;
		foreach ($list as $values) {
			if ($values['key'] === $extensionKey) {
				$extensionExists = TRUE;
				break;
			}
		}
		return $extensionExists;
	}

	/**
	 * Initialize ExtensionManager Objects.
	 *
	 * @return void
	 */
	protected function initializeExtensionManagerObjects() {
//		$this->xmlHandler = GeneralUtility::makeInstance('tx_em_Tools_XmlHandler');
		$this->listUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility');
		$this->installUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility');
//		$this->terConnection = GeneralUtility::makeInstance('tx_em_Connection_Ter', $this);
//		$this->extensionDetails = GeneralUtility::makeInstance('tx_em_Extensions_Details', $this);
	}

	/**
	 * @return tx_em_Install
	 */
	protected function getEmInstall() {
		$install = GeneralUtility::makeInstance('tx_em_Install', $this);
		$install->setSilentMode(TRUE);
		return $install;
	}

	/**
	 * Clear the caches.
	 *
	 * @return void
	 */
	protected function clearCaches() {
		$cacheApiService = GeneralUtility::makeInstance('Etobi\\CoreAPI\\Service\\CacheApiService');
		$cacheApiService->initializeObject();
		$cacheApiService->clearAllCaches();
	}
}