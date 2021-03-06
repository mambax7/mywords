<?php
/**
 * MyWords for XOOPS
 *
 * Copyright © 2017 Eduardo Cortés http://www.eduardocortes.mx
 * -------------------------------------------------------------
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * -------------------------------------------------------------
 * @copyright    Eduardo Cortés (http://www.eduardocortes.mx)
 * @license      GNU GPL 2
 * @package      mywords
 * @author       Eduardo Cortés (AKA bitcero)    <i.bitcero@gmail.com>
 * @url          http://www.eduardocortes.mx
 */
require __DIR__ . '/header.php';
$common->location = 'editors';

/**
 * Mostramos la lista de editores junto con
 * el formulario para crear nuevos editores
 */
function show_editors()
{
    global $tpl, $xoopsUser, $xoopsSecurity, $xoopsModule;

    MWFunctions::include_required_files();

    RMTemplate::getInstance()->assign('xoops_pagetitle', __('Editors Management', 'mywords'));
    require_once RMCPATH . '/class/form.class.php';

    foreach ($_REQUEST as $k => $v) {
        $$k = $v;
    }

    $db = XoopsDatabaseFactory::getDatabaseConnection();
    list($num) = $db->fetchRow($db->query('SELECT COUNT(*) FROM ' . $db->prefix('mod_mywords_editors')));
    $page = rmc_server_var($_GET, 'page', 1);
    $limit = isset($limit) && $limit > 0 ? $limit : 15;

    $tpages = ceil($num / $limit);
    $page = $page > $tpages ? $tpages : $page;

    $start = $num <= 0 ? 0 : ($page - 1) * $limit;

    $nav = new RMPageNav($num, $limit, $page, 5);
    $nav->target_url('editors.php?page={PAGE_NUM}');
    $result = $db->query('SELECT * FROM ' . $db->prefix('mod_mywords_editors') . " ORDER BY name LIMIT $start,$limit");
    $editors = [];

    while (false !== ($row = $db->fetchArray($result))) {
        $ed = new MWEditor();
        $ed->assignVars($row);
        $editors[] = $ed;
    }

    $tpl->assign('editors', $editors);

    RMBreadCrumb::get()->add_crumb(__('Editors Management', 'mywords'));

    xoops_cp_header();
    RMTemplate::getInstance()->add_script(RMCURL . '/include/js/jquery.checkboxes.js');
    RMTemplate::getInstance()->add_script('../include/js/scripts.php?file=editors.js');
    include RMTemplate::getInstance()->get_template('admin/mywords-editors.php', 'module', 'mywords');

    xoops_cp_footer();
}

function edit_editor()
{
    global $xoopsModule, $xoopsSecurity;

    $id = rmc_server_var($_GET, 'id', 0);
    $page = rmc_server_var($_GET, 'page', 1);

    if ($id <= 0) {
        redirectMsg('editors.php?page=' . $page, __('Editor ID not provided!.', 'mywords'), 1);
        die();
    }

    $editor = new MWEditor($id);
    if ($editor->isNew()) {
        redirectMsg('editors.php?page=' . $page, __('Editor does not exists!', 'mywords'), 1);
        die();
    }

    require_once RMCPATH . '/class/form.class.php';

    MWFunctions::include_required_files();

    RMTemplate::getInstance()->assign('xoops_pagetitle', __('Editing Editor', 'mywords'));

    RMBreadCrumb::get()->add_crumb(__('Editors Management', 'mywords'), 'editors.php');
    RMBreadCrumb::get()->add_crumb(__('Edit Editor', 'mywords'));

    xoops_cp_header();
    $show_edit = true;
    include RMTemplate::getInstance()->get_template('admin/mywords-editors.php', 'module', 'mywords');
    xoops_cp_footer();
}

/**
 * Agregamos nuevos editores a la base de datos
 * @param mixed $edit
 */
function save_editor($edit = false)
{
    global $xoopsConfig, $xoopsSecurity;

    $page = rmc_server_var($_POST, 'page', 1);

    if (!$xoopsSecurity->check()) {
        redirectMsg('editors.php?page=' . $page, __('Operation not allowed!', 'mywords'), 1);
        die();
    }

    if ($edit) {
        $id = rmc_server_var($_POST, 'id', 0);
        if ($id <= 0) {
            redirectMsg('editors.php?page=' . $page, __('Editor ID has not been provided!', 'mywords'), 1);
            die();
        }

        $editor = new MWEditor($id);
        if ($editor->isNew()) {
            redirectMsg('editors.php?page=' . $page, __('Editor has not been found!', 'mywords'), 1);
            die();
        }
    } else {
        $editor = new MWEditor();
    }

    $name = rmc_server_var($_POST, 'name', '');
    $bio = rmc_server_var($_POST, 'bio', '');
    $uid = rmc_server_var($_POST, 'new_user', 0);
    $perms = rmc_server_var($_POST, 'perms', []);
    $short = rmc_server_var($_POST, 'short', '');

    if ('' == trim($name)) {
        redirectMsg('editors.php?page=' . $page, __('You must provide a display name for this editor!', 'mywords'), 1);
        die();
    }

    if ($uid <= 0) {
        redirectMsg('editors.php?page=' . $page, __('You must specify a registered user ID for this editor!', 'mywords'), 1);
        die();
    }

    // Check if XoopsUser is already register
    $db = XoopsDatabaseFactory::getDatabaseConnection();
    $sql = 'SELECT COUNT(*) FROM ' . $db->prefix('mod_mywords_editors') . " WHERE uid=$uid";
    if ($edit) {
        $sql .= ' AND id_editor<>' . $editor->id();
    }
    list($num) = $db->fetchRow($db->query($sql));

    if ($num > 0) {
        redirectMsg('editors.php?page=' . $page, __('This user has been registered as editor before.', 'mywords'), 1);
        die();
    }

    $editor->setVar('name', $name);
    $editor->setVar('shortname', TextCleaner::sweetstring('' != $short ? $short : $name));
    $editor->setVar('bio', $bio);
    $editor->setVar('uid', $uid);
    $editor->setVar('privileges', $perms);

    if (!$editor->save()) {
        redirectMsg('editors.php?page=' . $page, __('Errors occurs while trying to save editor data', 'mywords') . '<br>' . $editor->errors(), 1);
        die();
    }
    redirectMsg('editors.php?page=' . $page, __('Database updated succesfully!', 'mywords'), 0);
    die();
}

function activate_editors($a)
{
    global $xoopsSecurity;

    $page = rmc_server_var($_POST, 'page', 1);
    $editors = rmc_server_var($_POST, 'editors', []);

    if (!$xoopsSecurity->check()) {
        redirectMsg('editors.php?page=' . $page, __('Sorry, operation not allowed!', 'mywords'), 1);
        die();
    }

    if (!is_array($editors) || empty($editors)) {
        redirectMsg('editors.php?page=' . $page, __('Please, specify a valid editor ID!', 'mywords'), 1);
        die();
    }

    // Delete all relations
    $db = XoopsDatabaseFactory::getDatabaseConnection();
    $sql = 'UPDATE ' . $db->prefix('mod_mywords_editors') . " SET active='" . ($a ? '1' : '0') . "' WHERE id_editor IN(" . implode(',', $editors) . ')';
    if (!$db->queryF($sql)) {
        redirectMsg('editors.php?page=' . $page, __('Errors ocurred while trying to update database!', 'mywords') . "\n" . $db->error(), 1);
        die();
    }

    redirectMsg('editors.php?page=' . $page, __('Database updated successfully!', 'mywords'), 0);
}

function delete_editors()
{
    global $xoopsSecurity;

    $page = rmc_server_var($_POST, 'page', 1);
    $editors = rmc_server_var($_POST, 'editors', []);

    if (!$xoopsSecurity->check()) {
        redirectMsg('editors.php?page=' . $page, __('Sorry, operation not allowed!', 'mywords'), 1);
        die();
    }

    if (!is_array($editors) || empty($editors)) {
        redirectMsg('editors.php?page=' . $page, __('Please, specify a valid editor ID!', 'mywords'), 1);
        die();
    }

    // Delete all relations
    $db = XoopsDatabaseFactory::getDatabaseConnection();
    $sql = 'UPDATE ' . $db->prefix('mod_mywords_posts') . " SET author='0' WHERE author IN(" . implode(',', $editors) . ')';
    if (!$db->queryF($sql)) {
        redirectMsg('editors.php?page=' . $page, __('Errors ocurred while trying to delete editors!', 'mywords') . '<br>' . $db->error(), 1);
        die();
    }

    $sql = 'DELETE FROM ' . $db->prefix('mod_mywords_editors') . ' WHERE id_editor IN(' . implode(',', $editors) . ')';
    if (!$db->queryF($sql)) {
        redirectMsg('editors.php?page=' . $page, __('Errors ocurred while trying to delete editors!', 'mywords') . '<br>' . $db->error(), 1);
        die();
    }

    redirectMsg('editors.php?page=' . $page, __('Database updated succesfully!', 'mywords'), 0);
}

$action = rmc_server_var($_REQUEST, 'action', '');

switch ($action) {
    case 'new':
        save_editor(false);
        break;
    case 'saveedit':
        save_editor(true);
        break;
    case 'edit':
        edit_editor();
        break;
    case 'delete':
        delete_editors();
        break;
    case 'deactivate':
        activate_editors(0);
        break;
    case 'activate':
        activate_editors(1);
        break;
    default:
        show_editors();
        break;
}
