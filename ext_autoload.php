<?php

$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('coreapi');
$extensionClassesPath = $extensionPath . 'Classes/';

return array(
	'tx_coreapi_command_databaseapicommandcontroller' => $extensionClassesPath . 'Command/DatabaseApiCommandController.php',
	'tx_coreapi_command_siteapicommandcontroller' => $extensionClassesPath . 'Command/SiteApiCommandController.php',
	'tx_coreapi_command_cacheapicommandcontroller' => $extensionClassesPath . 'Command/CacheApiCommandController.php',
	'tx_coreapi_service_cacheapiservice' => $extensionClassesPath . 'Service/CacheApiService.php',
	'tx_coreapi_service_siteapiservice' => $extensionClassesPath . 'Service/SiteApiService.php',
	'tx_coreapi_service_databaseapiservice' => $extensionClassesPath . 'Service/DatabaseApiService.php',
	'tx_coreapi_service_extensionapiservice' => $extensionClassesPath . 'Service/ExtensionApiService.php',
);