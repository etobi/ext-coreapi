<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Benjamin Serfhos <serfhos@rsm.nl>
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
     * @var array
     */
    protected $translationStates = array();

    /**
     * Constructor function
     */
    public function __construct() {
        if(!class_exists('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')){
            return; //4.5
        }

        $this->objectManager = $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->objectManager = $objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
        $this->languageRepository = $objectManager->get('TYPO3\\CMS\\Lang\\Domain\\Repository\\LanguageRepository');
        $this->extensionRepository = $objectManager->get('TYPO3\\CMS\\Lang\\Domain\\Repository\\ExtensionRepository');
        $this->repositoryHelper = $objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper');
        $this->terConnection = $objectManager->get('TYPO3\\CMS\\Lang\\Utility\\Connection\\Ter');
    }

    /**
     * Update all extension translations for TYPO3 4.5
     *
     * @author Martin Tillmann <mtillmann@gmail.com>
     * @param string $username BE admin username for 4.5 installations
     * @param string $ter_key key of mirror to use (ex. ter.cablan.net)
     *
     * @return array
     */
    public function updateAllTranslations45($username = null, $ter_key = null) {
        /**
         * Get the UC field from a workin backend admin account because
         * _cli_lowlevel is unlikely to have the required fields set...
         */
        $username = preg_replace('/[^\w\d_\-]/','',$username);
        $user = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uc','be_users','username = "'.$username.'" and deleted = 0 and disable = 0 and admin = 1');
        if(!$user){
            throw new \Exception('[translations][4.5] Backend Admin User "'.$username.'" not found!');
        }

        $GLOBALS['BE_USER']->uc = unserialize($user['uc']);
        $modSettings = t3lib_BEfunc::getModuleData(array(), array('function' => 'translations'), 'tools_em');
        $mirrors = unserialize($modSettings['extMirrors']);

        $ter_key = preg_replace('/[^\w\d_\-\.]/i', '', $ter_key);
        if(!$mirror = $mirrors[$ter_key]){
            throw new \Exception('[translations][4.5] Mirror with key "'.$ter_key.'" not found!');
        }
        $mirrorUrl = 'http://'.$mirror['host'].'/'.$mirror['path'];
      
        /**
         * create fake object that resembles a SC_mod_tools_em_index instance
         * with all properties that are required for the translation task
         */
        
        $fauxEm = new stdClass();
        $fauxEm->MOD_SETTINGS = $modSettings;
        $fauxEm->xmlHandler = t3lib_div::makeInstance('tx_em_Tools_XmlHandler');
        $fauxEm->xmlHandler->emObj = $fauxEm;
        $fauxEm->xmlHandler->useObsolete = $this->MOD_SETTINGS['display_obsolete'];

        $fauxEm->terConnection = t3lib_div::makeInstance('tx_em_Connection_Ter', $fauxEm);
        $fauxEm->terConnection->wsdlURL = $TYPO3_CONF_VARS['EXT']['em_wsdlURL'];

        $fauxEm->translation = t3lib_div::makeInstance('tx_em_Translations', $fauxEm);

        $extensionList = t3lib_div::makeInstance('tx_em_Extensions_List');
        $installedExtensions = array();
        foreach( $extensionList->getInstalledExtensions(true) as $ext ){
            $installedExtensions[] = $ext['extkey'];
        }

        $selectedLanguages = unserialize($modSettings['selectedLanguages']);
        $opt = array();

        /**
         * loop through all languages because tx_em_Translations::installTranslationsForExtension
         * will return inside a loop
         */
        foreach($selectedLanguages as $language){
            $opt[$language.' attempted'] = 0;
            $opt[$language.' existing'] = 0;
            $fauxEm->MOD_SETTINGS['selectedLanguages'] = serialize(array($language));
            foreach($installedExtensions as $extkey){
                $fauxEm->translation->installTranslationsForExtension($extkey,$mirrorUrl);
                $opt[$language.' attempted']++;
            }
            $existingTranslations = glob(PATH_typo3conf.'l10n/'.$language.'/*', GLOB_ONLYDIR);
            $opt[$language.' existing'] = count($existingTranslations);
        }

        return $opt;
    }
    
    /**
     * Update all extension translations
     *
     * @return array
     */
    public function updateAllTranslations() {
        $return = array();
        $activeLanguages = $this->languageRepository->findSelected();
        $activeLocales = array();
        foreach ($activeLanguages as $language) {
            $activeLocales[] = $language->getLocale();
        }

        $locales = array();
        if (!empty($activeLocales)) {
            $activeExtensions = $this->extensionRepository->findAll();
            foreach ($activeExtensions as $extension) {
                foreach ($activeLocales as $locale) {
                    $state = static::TRANSLATION_INVALID;
                    try {
                        $state = $this->getTranslationStateForExtension($extension->getKey(), $locale);
                        if ($state === static::TRANSLATION_AVAILABLE) {
                            $state = $this->updateTranslationForExtension($extension->getKey(), $locale);
                        }
                    } catch (\Exception $exception) {
                        $error = $exception->getMessage();
                    }
                    $locales[$locale] = array(
                        'state'  => $state,
                        'error'  => $error,
                    );
                }
            }
        }

        return $locales;
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

        $mirrorUrl = $this->repositoryHelper->getMirrors()->getMirrorUrl();
        $status = $this->terConnection->fetchTranslationStatus($extensionKey, $mirrorUrl);

        $md5 = $this->getTranslationFileMd5($extensionKey, $locale);
        if (empty($status[$locale]) || !is_array($status[$locale])) {
            $this->translationStates[$identifier] = static::TRANSLATION_NOT_AVAILABLE;
        } else if ($md5 !== $status[$locale]['md5']) {
            $this->translationStates[$identifier] = static::TRANSLATION_AVAILABLE;
            echo $extensionKey . "\r\n";
        } else {
            $this->translationStates[$identifier] = static::TRANSLATION_OK;
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
        $mirrorUrl = $this->repositoryHelper->getMirrors()->getMirrorUrl();
        $updateResult = $this->terConnection->updateTranslation($extensionKey, $locale, $mirrorUrl);
        if ($updateResult === TRUE) {
            $state = static::TRANSLATION_UPDATED;
        }

        return $state;
    }

}
?>