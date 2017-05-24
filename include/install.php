<?php
// $Id: install.php 1067 2012-09-19 01:34:58Z i.bitcero $
// --------------------------------------------------------------
// MyWords
// Module for advanced image galleries management
// Author: Eduardo Cortés
// Email: i.bitcero@gmail.com
// License: GPL 2.0
// --------------------------------------------------------------

function xoops_module_pre_install_mywords(&$mod){
    
    xoops_setActiveModules();
    
    $mods = xoops_getActiveModules();
    
    if(!in_array("rmcommon", $mods)){
        $mod->setErrors('MyWords could not be instaled if <a href="http://www.redmexico.com.mx/w/common-utilities/" target="_blank">Common Utilities</a> has not be installed previously!<br />Please install <a href="http://www.redmexico.com.mx/w/common-utilities/" target="_blank">Common Utilities</a>.');
        return false;
    }
    
    return true;
    
}

function xoops_module_update_mywords($mod, $pre){

    global $xoopsDB;

    $xoopsDB->queryF("ALTER TABLE ".$xoopsDB->prefix("mw_posts")." ADD `created` INT(10) NOT NULL DEFAULT '0' AFTER `pubdate`");
    $xoopsDB->queryF("ALTER TABLE ".$xoopsDB->prefix("mw_posts")." ADD `description` TEXT NOT NULL AFTER `image`");
    $xoopsDB->queryF("ALTER TABLE ".$xoopsDB->prefix("mw_posts")." ADD `keywords` TEXT NOT NULL AFTER `description`");
    $xoopsDB->queryF("ALTER TABLE ".$xoopsDB->prefix("mw_posts")." ADD `customtitle` VARCHAR(255) NOT NULL AFTER `keywords`");

    return true;
    
}