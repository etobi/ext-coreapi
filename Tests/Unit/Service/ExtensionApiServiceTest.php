<?php

namespace Etobi\CoreApi\Tests\Unit\Service;

/***************************************************************
 *  Copyright notice
 *
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
 
use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Class ExtensionApiServiceTest
 * 
 * @package Etobi\CoreApi\Tests\Unit\Service
 * @author  Stefano Kowalke <blueduck@gmx.net>
 * @coversDefaultClass \Etobi\CoreAPI\Service\ExtensionApiService
 */
class ExtensionApiServiceTest extends UnitTestCase {

	/**
	 * @var \Etobi\CoreApi\Service\ExtensionApiService|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject
	 */
	protected $subject;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper|\PHPUnit_Framework_MockObject_MockObject $repositoryHelperMock
	 */
	protected $repositoryHelperMock;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Model\Mirrors|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mirrorsMock;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Model\Extension|\PHPUnit_Framework_MockObject_MockObject $extensionMock
	 */
	protected $extensionMock;

	/**
	 * @var |\PHPUnit_Framework_MockObject_MockObject $extensionRepositoryMock
	 */
	protected $extensionRepositoryMock;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\RepositoryRepository|\PHPUnit_Framework_MockObject_MockObject $repositoryRepositoryMock
	 */
	protected $repositoryRepositoryMock;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $extensionManagementService
	 */
	protected $extensionManagementService;

	/**
	 * @var string $installPath
	 */
	protected $installPath = 'root/coreapi/';

	/**
	 * Set the test up
	 */
	public function setup() {
		$this->subject = $this->getAccessibleMock('Etobi\\CoreApi\\Service\\ExtensionApiService', array('dummy'));

		$fileHandlingUtility = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility');
		$this->subject->_set('fileHandlingUtility', $fileHandlingUtility);

		$this->repositoryHelperMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\Repository\\Helper', array(), array(), '', FALSE);
		$this->mirrorsMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Mirrors');
		$this->extensionMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\Extension', array('dummy'));
		$this->repositoryRepositoryMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\RepositoryRepository', array(), array(), '', FALSE);
	}

	/**
	 * @test
	 * @covers ::getExtensionInformation
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage No extension key given!
	 */
	public function getExtensionInformationNoExtensionKeyGivenThrowsException(){
		$this->subject->getExtensionInformation('');
	}

	/**
	 * @test
	 * @covers ::getExtensionInformation
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Extension "bigfoot" not found!
	 */
	public function getExtensionInformationExtensionNotFoundThrowsException(){
		$this->subject->getExtensionInformation('bigfoot');
	}

	/**
	 * @param string $amount
	 *
	 * @return array
	 */
	protected function getFakeInstalledExtensionArray($amount = 'full') {
		$result = array();

		if ($amount === 'none') {
			$result = array();
		} else {
			$result = array(
						'core' => array(
							'type' => 'S',
							'siteRelPath' => 'typo3/sysext/core/',
							'typo3RelPath' => 'sysext/core/',
							'ext_localconf.php' => 'path/to/core/ext_localconf.php',
							'ext_tables.php' => 'path/to/core/ext_tables.php',
							'ext_tables.sql' => 'path/to/core/ext_tables.sql',
							'ext_icon' => 'ext_icon.png'
						),
						'backend' => array(
							'type' => 'S',
							'siteRelPath' => 'typo3/sysext/backend/',
							'typo3RelPath' => 'sysext/backend/',
							'ext_localconf.php' => 'path/to/backend/ext_localconf.php',
							'ext_tables.php' => 'path/to/backend/ext_tables.php',
							'ext_icon' => 'ext_icon.png'
						),
						'cms' => array(
							'type' => 'G',
							'siteRelPath' => 'typo3/sysext/cms/',
							'typo3RelPath' => 'sysext/cms/',
							'ext_localconf.php' => 'path/to/cms/ext_localconf.php',
							'ext_tables.php' => 'path/to/cms/ext_tables.php',
							'ext_icon' => 'ext_icon.png'
						),
						'coreapi' => array(
							'type' => 'L',
							'siteRelPath' => 'typo3conf/ext/coreapi/',
							'typo3RelPath' => '../typo3conf/ext/coreapi/',
							'ext_localconf.php' => 'path/to/coreapi/ext_localconf.php',
							'ext_icon' => 'ext_icon.png'
						),
				);
		}

		return $result;
	}

	/**
	 * @test
	 * @covers ::getExtensionInformation
	 */
	public function getExtensionInformationReturnsInformation() {
		$loadedExtensions = $GLOBALS['TYPO3_LOADED_EXT'];

		$GLOBALS['TYPO3_LOADED_EXT'] = $this->getFakeInstalledExtensionArray();
		$currentExtensionInformation = $this->subject->getExtensionInformation('coreapi');
		$GLOBALS['TYPO3_LOADED_EXT'] = $loadedExtensions;

		$this->assertArrayHasKey('em_conf', $currentExtensionInformation);
		$this->assertArrayHasKey('is_installed', $currentExtensionInformation);
		$this->assertSame($currentExtensionInformation['em_conf']['title'], 'coreapi');
		$this->assertSame($currentExtensionInformation['is_installed'], TRUE);

	}

	/**
	 * @test
	 * @covers ::getInstalledExtensions
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Only "Local", "System", "Global" and "" (all) are supported as type
	 */
	public function getInstalledExtensionWrongTypeGivenThrowsException() {
		$this->subject->getInstalledExtensions('42');
	}

	/**
	 * @test
	 * @covers ::getInstalledExtensions
	 */
	public function getInstalledExtensionReturnsListOfLocalExtensions() {
		$loadedExtensions = $GLOBALS['TYPO3_LOADED_EXT'];

		$GLOBALS['TYPO3_LOADED_EXT'] = $this->getFakeInstalledExtensionArray('none');
		$listLocal = $this->subject->getInstalledExtensions('Local');
		$this->assertTrue(count($listLocal) === 0);
		$GLOBALS['TYPO3_LOADED_EXT'] = $this->getFakeInstalledExtensionArray('full');
		$listLocal = $this->subject->getInstalledExtensions('Local');
		$this->assertTrue(count($listLocal) === 1);

		$GLOBALS['TYPO3_LOADED_EXT'] = $loadedExtensions;
	}

	/**
	 * @test
	 * @covers ::getInstalledExtensions
	 */
	public function getInstalledExtensionReturnsListOfGlobalExtensions() {
		$loadedExtensions = $GLOBALS['TYPO3_LOADED_EXT'];

		$GLOBALS['TYPO3_LOADED_EXT'] = $this->getFakeInstalledExtensionArray('none');
		$listGlobal = $this->subject->getInstalledExtensions('Global');
		$this->assertTrue(count($listGlobal) === 0);
		$GLOBALS['TYPO3_LOADED_EXT'] = $this->getFakeInstalledExtensionArray('full');
		$listGlobal = $this->subject->getInstalledExtensions('Global');
		$this->assertTrue(count($listGlobal) === 1);

		$GLOBALS['TYPO3_LOADED_EXT'] = $loadedExtensions;
	}

	/**
	 * @test
	 * @covers ::getInstalledExtensions
	 */
	public function getInstalledExtensionReturnsListOfSystemExtensions() {
		$loadedExtensions = $GLOBALS['TYPO3_LOADED_EXT'];
		$GLOBALS['TYPO3_LOADED_EXT'] = $this->getFakeInstalledExtensionArray('none');
		$listSystem = $this->subject->getInstalledExtensions('System');
		$this->assertTrue(count($listSystem) === 0);

		$GLOBALS['TYPO3_LOADED_EXT'] = $this->getFakeInstalledExtensionArray('full');
		$listSystem = $this->subject->getInstalledExtensions('System');
		$this->assertTrue(count($listSystem) === 2);

		$GLOBALS['TYPO3_LOADED_EXT'] = $loadedExtensions;
	}

	public function getRepositoryData() {
		$repository = new \TYPO3\CMS\ExtensionManager\Domain\Model\Repository();
		$repository->setTitle('TYPO3.org Main Repository');
		$repository->setDescription('Main repository on typo3.org. This repository has some mirrors configured which are available with the mirror url.');
		$repository->setMirrorListUrl('http://repositories.typo3.org/mirrors.xml.gz');
		$repository->setWsdlUrl('http://typo3.org/wsdl/tx_ter_wsdl.php');
		$repository->setLastUpdate(new \DateTime('now'));
		$repository->setExtensionCount(42);
		$repository->setPid(0);

		return $repository;
	}

	/**
	 * @test
	 * @covers ::updateMirrors
	 */
	public function updateMirrorsReturnsFalse() {
		$this->repositoryRepositoryMock->expects($this->once())->method('findAll')->will($this->returnValue(array($this->getRepositoryData())));
		$this->repositoryHelperMock->expects($this->once())->method('updateExtList')->will($this->returnValue(FALSE));
		$this->subject->injectRepositoryHelper($this->repositoryHelperMock);
		$this->subject->injectRepositoryRepository($this->repositoryRepositoryMock);
		$this->assertFalse($this->subject->updateMirrors());
	}

	/**
	 * @test
	 * @covers ::updateMirrors
	 */
	public function updateMirrorsReturnsTrue() {
		$this->repositoryRepositoryMock->expects($this->once())->method('findAll')->will($this->returnValue(array($this->getRepositoryData())));
		$this->repositoryHelperMock->expects($this->once())->method('updateExtList')->will($this->returnValue(TRUE));
		$this->subject->injectRepositoryHelper($this->repositoryHelperMock);
		$this->subject->injectRepositoryRepository($this->repositoryRepositoryMock);
		$this->assertTrue($this->subject->updateMirrors());
	}

	/**
	 * @test
	 * @covers ::fetchExtension
	 */
	public function optionVersionNotSetDownloadLatestVersion(){
		$this->extensionMock->setExtensionKey('coreapi');
		$this->extensionMock->setVersion('2.03');

		$this->extensionRepositoryMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('findHighestAvailableVersion'), array(), '', FALSE);
		$this->extensionRepositoryMock->expects($this->once())->method('findHighestAvailableVersion')->with('coreapi')->will($this->returnValue($this->extensionMock));
		$this->subject->_set('extensionRepository', $this->extensionRepositoryMock);

		$this->extensionManagementService = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService');
		$this->extensionManagementService->expects($this->once())->method('resolveDependenciesAndInstall')->with($this->extensionMock)->will($this->returnValue(array('installed' => array('coreapi' => ''))));
		$this->subject->_set('extensionManagementService', $this->extensionManagementService);

		$this->repositoryHelperMock->expects($this->once())->method('getMirrors')->will($this->returnValue($this->mirrorsMock));
		$this->subject->_set('repositoryHelper', $this->repositoryHelperMock);

		$this->subject->fetchExtension('coreapi');
	}

	/**
	 * @test
	 * @covers ::fetchExtension
	 */
	public function optionVersionSetDownloadsDemandedVersion() {
		$this->extensionMock->setExtensionKey('coreapi');
		$this->extensionMock->setVersion('1.0');

		$this->extensionRepositoryMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('findOneByExtensionKeyAndVersion'), array(), '', FALSE);
		$this->extensionRepositoryMock->expects($this->once())->method('findOneByExtensionKeyAndVersion')->with('coreapi', '1.0')->will($this->returnValue($this->extensionMock));
		$this->subject->_set('extensionRepository', $this->extensionRepositoryMock);

		$this->extensionManagementService = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Service\\ExtensionManagementService');
		$this->extensionManagementService->expects($this->once())->method('resolveDependenciesAndInstall')->with($this->extensionMock)->will($this->returnValue(array('installed' => array('coreapi' => ''))));
		$this->subject->_set('extensionManagementService', $this->extensionManagementService);

		$this->repositoryHelperMock->expects($this->once())->method('getMirrors')->will($this->returnValue($this->mirrorsMock));
		$this->subject->_set('repositoryHelper', $this->repositoryHelperMock);

		$this->subject->fetchExtension('coreapi', '1.0');
	}

	/**
	 * @test
	 * @covers ::fetchExtension
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage System installation is not allowed!
	 */
	public function optionLocationSystemThrowsException() {
		unset($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowSystemInstall']);
		unset($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowGlobalInstall']);

		$this->extensionRepositoryMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('findOneByExtensionKeyAndVersion'), array(), '', FALSE);
		$this->extensionRepositoryMock->expects($this->once())->method('findOneByExtensionKeyAndVersion')->with('coreapi', '1.0')->will($this->returnValue($this->extensionMock));

		$this->subject->_set('extensionRepository', $this->extensionRepositoryMock);
		$this->subject->fetchExtension('coreapi', '1.0', 'System');
	}

	/**
	 * @test
	 * @covers ::fetchExtension
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Global installation is not allowed!
	 */
	public function optionLocationGlobalThrowsException() {
		unset($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowSystemInstall']);
		unset($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowGlobalInstall']);

		$this->extensionRepositoryMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('findOneByExtensionKeyAndVersion'), array(), '', FALSE);
		$this->extensionRepositoryMock->expects($this->once())->method('findOneByExtensionKeyAndVersion')->with('coreapi', '1.0')->will($this->returnValue($this->extensionMock));

		$this->subject->_set('extensionRepository', $this->extensionRepositoryMock);
		$this->subject->fetchExtension('coreapi', '1.0', 'Global');
	}

	/**
	 * @test
	 * @covers ::fetchExtension
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Local installation is not allowed!
	 */
	public function optionLocationLocalThrowsException() {
		unset($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowLocalInstall']);

		$this->extensionRepositoryMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('findOneByExtensionKeyAndVersion'), array(), '', FALSE);
		$this->extensionRepositoryMock->expects($this->once())->method('findOneByExtensionKeyAndVersion')->with('coreapi', '1.0')->will($this->returnValue($this->extensionMock));

		$this->subject->_set('extensionRepository', $this->extensionRepositoryMock);
		$this->subject->fetchExtension('coreapi', '1.0', 'Local');
	}

	/**
	 * @test
	 * @covers ::fetchExtension
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Unknown location "Location"!
	 */
	public function optionLocationWithWrongDataThrowsException() {
		$this->extensionRepositoryMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('findOneByExtensionKeyAndVersion'), array(), '', FALSE);
		$this->extensionRepositoryMock->expects($this->once())->method('findOneByExtensionKeyAndVersion')->with('coreapi', '1.0')->will($this->returnValue($this->extensionMock));

		$this->subject->_set('extensionRepository', $this->extensionRepositoryMock);
		$this->subject->fetchExtension('coreapi', '1.0', 'location');
	}

	/**
	 * @test
	 * @covers ::fetchExtension
	 * @expectedException InvalidArgumentException
	 */
	public function optionMirrorIsNotANumber() {
		$this->extensionRepositoryMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('findOneByExtensionKeyAndVersion'), array(), '', FALSE);
		$this->extensionRepositoryMock->expects($this->never())->method('findOneByExtensionKeyAndVersion');

		$this->subject->_set('extensionRepository', $this->extensionRepositoryMock);
		$this->subject->fetchExtension('coreapi', '', '', FALSE, 'test');
	}

	/**
	 * @test
	 * @covers ::fetchExtension
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Extension "coreapi" already exists at "vfs://root/coreapi/"!
	 */
	public function optionOverrideNotSetExtensionExistsAlready() {
		vfsStream::setup('root');
		vfsStream::create(array('coreapi' => array()));

		$this->extensionMock->setExtensionKey('coreapi');
		$this->extensionMock->setVersion('1.0');

		$fileHandlingUtility = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\FileHandlingUtility');
		$fileHandlingUtility->expects($this->once())->method('getExtensionDir')->will($this->returnValue(vfsStream::url($this->installPath)));

		$this->extensionRepositoryMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ExtensionRepository', array('findHighestAvailableVersion'), array(), '', FALSE);
		$this->extensionRepositoryMock->expects($this->once())->method('findHighestAvailableVersion')->with('coreapi')->will($this->returnValue($this->extensionMock));
		$this->subject->_set('extensionRepository', $this->extensionRepositoryMock);

		$this->repositoryHelperMock->expects($this->never())->method('getMirrors');

		$this->subject->_set('repositoryHelper', $this->repositoryHelperMock);
		$this->subject->_set('fileHandlingUtility', $fileHandlingUtility);

		$this->subject->fetchExtension('coreapi');
	}
}

