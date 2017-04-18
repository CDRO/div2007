<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Franz Holzinger (franz@ttproducts.de)
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
/**
 * Only needed for TYPO3 7
 * It takes care to declare the classes which are necessary for extensions which run under TYPO3 6.2
 * See the TYPO3 core files for the descriptions of these classes
 *
 * @package    TYPO3
 * @subpackage div2007
 * @author	Franz Holzinger <franz@ttproducts.de>
 */


if (!class_exists('t3lib_div')) {
    class t3lib_div extends \TYPO3\CMS\Core\Utility\GeneralUtility {}
}

if (!class_exists('tslib_cObj')) {
    class tslib_cObj extends \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer {}
}

if (!class_exists('tslib_pibase')) {
    class tslib_pibase extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {}
}

if (!class_exists('t3lib_extMgm')) {
    class t3lib_extMgm extends \TYPO3\CMS\Core\Utility\ExtensionManagementUtility {}
}

if (!class_exists('tslib_fe')) {
    class tslib_fe extends \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController {}
}

if (!class_exists('t3lib_parsehtml')) {
    class t3lib_parsehtml extends \TYPO3\CMS\Core\Html\HtmlParser {}
}


if (!class_exists('tx_div2007_compatibility6')) {
    // empty class which does nothing:
    class tx_div2007_compatibility6 {
        public function test () {
            debug ($tmp, 'tx_div2007_compatibility6::test'); // keep this
        }
    }

}

