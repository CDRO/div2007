<?php

namespace JambageCom\Div2007\Utility;

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Kasper Skårhøj (kasperYYYY@typo3.com)
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


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


/**
 * front end functions.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage div2007
 */


class FrontendUtility {
	static public function test () {
		return TRUE;
	}

/**
 * This is the MAIN DOCUMENT of the TypoScript driven standard front-end (from
 * the "cms" extension)
 *
 * Basically call this php script which all requests for TYPO3
 * delivered pages goes to in the frontend (the website) The script configures
 * constants, includes libraries and does a little logic here and there in order
 * to instantiate the right classes to create the webpage.
 *
 * All the real data processing goes on in the "tslib/" classes which this script
 * will include and use as needed.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */

	static public function init () {
		global $TT, $TSFE, $BE_USER;

		/** @var $TSFE \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
		$TSFE = GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController',
			$TYPO3_CONF_VARS,
			GeneralUtility::_GP('id'),
			GeneralUtility::_GP('type'),
			GeneralUtility::_GP('no_cache'),
			GeneralUtility::_GP('cHash'),
			GeneralUtility::_GP('jumpurl'),
			GeneralUtility::_GP('MP'),
			GeneralUtility::_GP('RDCT')
		);

		if (
			$TYPO3_CONF_VARS['FE']['pageUnavailable_force'] &&
			!GeneralUtility::cmpIP(
				GeneralUtility::getIndpEnv('REMOTE_ADDR'),
				$TYPO3_CONF_VARS['SYS']['devIPmask']
			)
		) {
			$TSFE->pageUnavailableAndExit('This page is temporarily unavailable.');
		}

		$TSFE->connectToDB();
		$TSFE->sendRedirect();

		// Output compression
		// Remove any output produced until now
		ob_clean();
		if (
			$TYPO3_CONF_VARS['FE']['compressionLevel'] &&
			extension_loaded('zlib')
		) {
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($TYPO3_CONF_VARS['FE']['compressionLevel'])) {
				// Prevent errors if ini_set() is unavailable (safe mode)
				@ini_set('zlib.output_compression_level', $TYPO3_CONF_VARS['FE']['compressionLevel']);
			}
			ob_start(array(GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Utility\\CompressionUtility'), 'compressionOutputHandler'));
		}

		// FE_USER
		$TT->push('Front End user initialized', '');
		/** @var $TSFE \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
		$TSFE->initFEuser();
		$TT->pull();

		// BE_USER
		/** @var $BE_USER \TYPO3\CMS\Backend\FrontendBackendUserAuthentication */
		$BE_USER = $TSFE->initializeBackendUser();

		// Process the ID, type and other parameters.
		// After this point we have an array, $page in TSFE, which is the page-record
		// of the current page, $id.
		$TT->push('Process ID', '');
		// Initialize admin panel since simulation settings are required here:
		if ($TSFE->isBackendUserLoggedIn()) {
			$BE_USER->initializeAdminPanel();
			\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadExtensionTables(TRUE);
		} else {
			\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadCachedTca();
		}
		$TSFE->checkAlternativeIdMethods();
		$TSFE->clear_preview();
		$TSFE->determineId();

		// Now, if there is a backend user logged in and he has NO access to this page,
		// then re-evaluate the id shown! _GP('ADMCMD_noBeUser') is placed here because
		// \TYPO3\CMS\Version\Hook\PreviewHook might need to know if a backend user is logged in.
		if (
			$TSFE->isBackendUserLoggedIn()
			&& (!$BE_USER->extPageReadAccess($TSFE->page) || GeneralUtility::_GP('ADMCMD_noBeUser'))
		) {
			// Remove user
			unset($BE_USER);
			$TSFE->beUserLogin = FALSE;
			// Re-evaluate the page-id.
			$TSFE->checkAlternativeIdMethods();
			$TSFE->clear_preview();
			$TSFE->determineId();
		}

		$TSFE->makeCacheHash();
		$TT->pull();

		// Starts the template
		$TT->push('Start Template', '');
		$TSFE->initTemplate();
		$TT->pull();
		// Get from cache
		$TT->push('Get Page from cache', '');
		$TSFE->getFromCache();
		$TT->pull();
		// Get config if not already gotten
		// After this, we should have a valid config-array ready
		$TSFE->getConfigArray();
		// Setting language and locale
		$TT->push('Setting language and locale', '');
		$TSFE->settingLanguage();
		$TSFE->settingLocale();
		$TT->pull();

		// Convert POST data to internal "renderCharset" if different from the metaCharset
		$TSFE->convPOSTCharset();

		// Store session data for fe_users
		$TSFE->storeSessionData();
		// Finish timetracking
		$TT->pull();

		// Debugging Output
		if (
			isset($error) &&
			is_object($error) &&
			@is_callable(array($error, 'debugOutput'))
		) {
			$error->debugOutput();
		}

		if (TYPO3_DLOG) {
			GeneralUtility::devLog('END of div2007 FRONTEND session', 'cms', 0, array('_FLUSH' => TRUE));
		}
	}

	/**
	 * Returns a JavaScript <script> section with some function calls to JavaScript functions from "typo3/js/jsfunc.updateform.js" (which is also included by setting a reference in $GLOBALS['TSFE']->additionalHeaderData['JSincludeFormupdate'])
	 * The JavaScript codes simply transfers content into form fields of a form which is probably used for editing information by frontend users. Used by fe_adminLib.inc.
	 *
	 * @param array $dataArray Data array which values to load into the form fields from $formName (only field names found in $fieldList)
	 * @param string $formName The form name
	 * @param string $arrPrefix A prefix for the data array
	 * @param string $fieldList The list of fields which are loaded
	 * @param string $javascriptFilename relative path to the filename of the Javascript which can execute the update form
	 * @return string
	 * @access private
	 * @see tx_agency_display::createScreen()
	 */
	static public function getUpdateJS (
		$dataArray,
		$formName,
		$arrPrefix,
		$fieldList,
		$javascriptFilename = ''
	) {
		$JSPart = '';
		$updateValues = GeneralUtility::trimExplode(',', $fieldList);
		foreach ($updateValues as $fKey) {
			$value = $dataArray[$fKey];
			if (is_array($value)) {
				foreach ($value as $Nvalue) {
					$JSPart .= '
	updateForm(\'' . $formName . '\',\'' . $arrPrefix . '[' . $fKey . '][]\',' . GeneralUtility::quoteJSvalue($Nvalue, TRUE) . ');';
				}
			} else {
				$JSPart .= '
	updateForm(\'' . $formName . '\',\'' . $arrPrefix . '[' . $fKey . ']\',' . GeneralUtility::quoteJSvalue($value, TRUE) . ');';
			}
		}
		$JSPart = '<script type="text/javascript">
	/*<![CDATA[*/ ' . $JSPart . '
	/*]]>*/
</script>
';

		if (empty($javascriptFilename)) {
			$javascriptFilename =
				ExtensionManagementUtility::siteRelPath('div2007') .
				'Resources/Public/JavaScript/jsfunc.updateform.js';
		} else {
			$lookupFile = explode('?', $file);
			$path = GeneralUtility::resolveBackPath(GeneralUtility::dirname(PATH_thisScript) . '/' . $lookupFile[0]);
			if (!file_exists($path)) {
				return FALSE;
			}
		}

		$script =
			'<script type="text/javascript" src="' .
				$GLOBALS['TSFE']->absRefPrefix .
				GeneralUtility::createVersionNumberedFilename($javascriptFilename) .
			'"></script>';
		$GLOBALS['TSFE']->additionalHeaderData['JSincludeFormupdate'] = $script;
		return $JSPart;
	}
}
