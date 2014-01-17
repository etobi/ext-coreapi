<?php
	/***************************************************************
	 *  Copyright notice
	 *  (c) 2013 Benjamin Serfhos <serfhos@rsm.nl>
	 *  (c) 2014 Marcus Schwemer <marcus@schwemer.de>
	 *
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
	 * Language API service
	 *
	 * @package TYPO3
	 * @subpackage tx_coreapi
	 */
	class Tx_Coreapi_Service_LanguageApiService {

		/**
		 * Status codes for AJAX response
		 */
		const TRANSLATION_NOT_AVAILABLE = 0;
		const TRANSLATION_AVAILABLE = 1;
		const TRANSLATION_FAILED = 2;
		const TRANSLATION_OK = 3;
		const TRANSLATION_INVALID = 4;
		const TRANSLATION_UPDATED = 5;

		/**
		 * Object manager interface for including all repositories and helpers
		 *
		 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
		 */
		protected $objectManager;

		/**
		 * @var \TYPO3\CMS\Lang\Domain\Repository\LanguageRepository
		 */
		protected $languageRepository;

		/**
		 * @var \TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository
		 */
		protected $extensionRepository;

		/**
		 * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
		 */
		protected $repositoryHelper;

		/**
		 * @var \TYPO3\CMS\Lang\Utility\Connection\Ter
		 */
		protected $terConnection;

		/**
		 * @var mirrorUrl URL of TER mirror
		 */
		protected $mirrorUrl;

		/**
		 * Array of translation state messages
		 *
		 * @var array
		 */
		protected $translationStateMessages = array();

		/**
		 * @var array
		 */
		protected $translationStates = array();

		/**
		 * Constructor function
		 */
		public function __construct() {
			$this->objectManager = $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
			$this->languageRepository = $objectManager->get('TYPO3\\CMS\\Lang\\Domain\\Repository\\LanguageRepository');
			$this->extensionRepository = $objectManager->get('TYPO3\\CMS\\Lang\\Domain\\Repository\\ExtensionRepository');
			$this->repositoryHelper = $objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper');
			$this->terConnection = $objectManager->get('TYPO3\\CMS\\Lang\\Utility\\Connection\\Ter');
			$this->mirrorUrl = $this->repositoryHelper->getMirrors()->getMirrorUrl();
			$this->translationStateMessages = array(
				static::TRANSLATION_NOT_AVAILABLE => "not available",
				static::TRANSLATION_AVAILABLE => "available",
				static::TRANSLATION_FAILED => "failed",
				static::TRANSLATION_OK => "up to date",
				static::TRANSLATION_INVALID => "invalid",
				static::TRANSLATION_UPDATED => "updated"
			);
		}

		/**
		 * Update all extension translations
		 *
		 * @param int $verbosity
		 * @return void
		 */
		public function updateAllTranslations($verbosity) {

			$activeLanguages = $this->languageRepository->findSelected();
			$activeLocales = array();
			foreach ($activeLanguages as $language) {
				$activeLocales[] = $language->getLocale();
			}

			$locales = array();
			if (!empty($activeLocales)) {
				$activeExtensions = $this->extensionRepository->findAll();
				foreach ($activeExtensions as $extension) {
					$extensionKey = $extension->getKey();
					if ($verbosity > 1) {
						print "\n" . $extensionKey;
					}
					foreach ($activeLocales as $locale) {
						if ($verbosity > 2) {
							print "\n\t$locale\t ";
						}
						$state = static::TRANSLATION_INVALID;
						try {
							$state = $this->getTranslationStateForExtension($extensionKey, $locale);

							if ($state === static::TRANSLATION_AVAILABLE) {
								$state = $this->updateTranslationForExtension($extensionKey, $locale);
							}
							if ($verbosity > 2) {
								print $this->translationStateMessages[$state];
							}
						} catch (\Exception $exception) {
							$error = $exception->getMessage();
							if ($verbosity === 1) {
								print $extensionKey . "\n\t$locale - ERROR:" . $error;
							} elseif ($verbosity === 2) {
								print "\n\t$locale - ERROR:" . $error;
							} elseif ($verbosity === 3) {
								print "\n\tERROR:" . $error;
							}
						}
					}
				}
			}
		}

		/**
		 * Returns the translation state for an extension
		 *
		 * @param string $extensionKey The extension key
		 * @param string $locale Locale to return
		 * @return integer Translation state
		 */
		protected function getTranslationStateForExtension($extensionKey, $locale) {
			if (empty($extensionKey) || empty($locale)) {
				return static::TRANSLATION_INVALID;
			}

			$identifier = $extensionKey . '-' . $locale;
			if (isset($this->translationStates[$identifier])) {
				return $this->translationStates[$identifier];
			}

			$status = $this->terConnection->fetchTranslationStatus($extensionKey, $this->mirrorUrl);

			$md5 = $this->getTranslationFileMd5($extensionKey, $locale);
			if (empty($status[$locale]) || !is_array($status[$locale])) {
				$this->translationStates[$identifier] = static::TRANSLATION_NOT_AVAILABLE;
			} else {
				if ($md5 !== $status[$locale]['md5']) {
					$this->translationStates[$identifier] = static::TRANSLATION_AVAILABLE;
				} else {
					$this->translationStates[$identifier] = static::TRANSLATION_OK;
				}
			}

			return $this->translationStates[$identifier];
		}

		/**
		 * Returns the md5 of a translation file
		 *
		 * @param string $extensionKey The extension key
		 * @param string $locale The locale
		 * @return string The md5 value
		 */
		protected function getTranslationFileMd5($extensionKey, $locale) {
			if (empty($extensionKey) || empty($locale)) {
				return '';
			}
			$fileName = PATH_site . 'typo3temp' . DIRECTORY_SEPARATOR . $extensionKey . '-l10n-' . $locale . '.zip';
			if (is_file($fileName)) {
				return md5_file($fileName);
			}
			return '';
		}

		/**
		 * Update the translation for an extension
		 *
		 * @param string $extensionKey The extension key
		 * @param string $locale Locale to update
		 * @return integer Translation state
		 */
		protected function updateTranslationForExtension($extensionKey, $locale) {
			if (empty($extensionKey) || empty($locale)) {
				return static::TRANSLATION_INVALID;
			}

			$state = static::TRANSLATION_FAILED;
			$updateResult = $this->terConnection->updateTranslation($extensionKey, $locale, $this->mirrorUrl);
			if ($updateResult === TRUE) {
				$state = static::TRANSLATION_UPDATED;
			}

			return $state;
		}
	}

?>