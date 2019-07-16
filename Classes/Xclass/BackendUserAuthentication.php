<?php
namespace ChristianEssl\Fixrootpageaccess\Xclass;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * TYPO3 backend user authentication
 * Contains most of the functions used for checking permissions, authenticating users,
 * setting up the user, and API for user from outside.
 * This class contains the configuration of the database fields used plus some
 * functions for the authentication process of backend users.
 */
class BackendUserAuthentication extends FrontendBackendUserAuthentication
{
    /**
     * Checks if the page id, $id, is found within the webmounts set up for the user.
     * This should ALWAYS be checked for any page id a user works with, whether it's about reading, writing or whatever.
     * The point is that this will add the security that a user can NEVER touch parts outside his mounted
     * pages in the page tree. This is otherwise possible if the raw page permissions allows for it.
     * So this security check just makes it easier to make safe user configurations.
     * If the user is admin OR if this feature is disabled
     * (fx. by setting TYPO3_CONF_VARS['BE']['lockBeUserToDBmounts']=0) then it returns "1" right away
     * Otherwise the function will return the uid of the webmount which was first found in the rootline of the input page $id
     *
     * @param int $id Page ID to check
     * @param string $readPerms Content of "->getPagePermsClause(1)" (read-permissions). If not set, they will be internally calculated (but if you have the correct value right away you can save that database lookup!)
     * @param bool|int $exitOnError If set, then the function will exit with an error message.
     * @throws \RuntimeException
     * @return int|null The page UID of a page in the rootline that matched a mount point
     */
    public function isInWebMount($id, $readPerms = '', $exitOnError = 0)
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts'] || $this->isAdmin()) {
            return 1;
        }
        $id = (int)$id;
        // Check if input id is an offline version page in which case we will map id to the online version:
        $checkRec = BackendUtility::getRecord(
            'pages',
            $id,
            'pid,t3ver_oid,'
            . $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] . ','
            . $GLOBALS['TCA']['pages']['ctrl']['languageField']
        );
        if ($checkRec['pid'] == -1) {
            $id = (int)$checkRec['t3ver_oid'];
        }
        // if current rec is a translation then get uid from l10n_parent instead
        // because web mounts point to pages in default language and rootline returns uids of default languages
        if ((int)$checkRec[$GLOBALS['TCA']['pages']['ctrl']['languageField']] !== 0 && (int)$checkRec[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']] !== 0) {
            $id = (int)$checkRec[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']];
        }

        if ($checkRec['pid'] == -1) {
            $id = (int)$checkRec['t3ver_oid'];
        }
        if (!$readPerms) {
            $readPerms = $this->getPagePermsClause(Permission::PAGE_SHOW);
        }
        if ($id > 0) {
            $wM = $this->returnWebmounts();
            $rL = BackendUtility::BEgetRootLine($id, ' AND ' . $readPerms);
            foreach ($rL as $v) {
                if ($v['uid'] && in_array($v['uid'], $wM)) {
                    return $v['uid'];
                }
            }
        }
        if ($exitOnError) {
            throw new \RuntimeException('Access Error: This page is not within your DB-mounts', 1294586445);
        }
        return null;
    }


}
