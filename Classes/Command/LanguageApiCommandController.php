<?php
	/***************************************************************
	 *  Copyright notice
	 *  (c) 2014 Marcus Schwemer <marcus@schwemer.de>
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
	class Tx_Coreapi_Command_LanguageApiCommandController extends Tx_Extbase_MVC_Controller_CommandController {

		/**
		 * Update language files from TER
		 *
		 * @param int $verbosity	show progress: 0 - be quiet; 1 - only errors; 2 - show extensions; 3 - show extensions and locales (Default)
		 * @return void
		 */
		public function updateAllTranslationsCommand($verbosity = 3) {
			/** @var $service Tx_Coreapi_Service_LanguageApiService */
			if (empty($verbosity)) {
				$verbosity = 2;
			}
			$this->outputLine('Updating Languages:');
			$this->outputLine('Please be patient. This may take a while.');
			$service = $this->objectManager->get('Tx_Coreapi_Service_LanguageApiService');
			$service->updateAllTranslations($verbosity);
		}
	}

?>