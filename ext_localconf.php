<?php /** @noinspection PhpUndefinedVariableInspection */

defined('TYPO3_MODE') || die('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class] = [
    'className' => \ChristianEssl\Fixrootpageaccess\Xclass\BackendUserAuthentication::class
];