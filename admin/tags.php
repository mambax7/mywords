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
$common->location = 'tags';

RMTemplate::getInstance()->add_help(
    'Etiqueta',
    'http://www.xoopsmexico.net/docs/mywords/etiquetas/'
);

/**
 * Show all existing tags
 */
function show_tags()
{
    global $xoopsModule, $xoopsSecurity;

    MWFunctions::include_required_files();

    RMTemplate::getInstance()->assign('xoops_pagetitle', __('Tags Management', 'mywords'));

    // More used tags
    $db = XoopsDatabaseFactory::getDatabaseConnection();
    $sql = 'SELECT * FROM ' . $db->prefix('mod_mywords_tags') . ' ORDER BY posts DESC LIMIT 0,30';
    $result = $db->query($sql);
    $mtags = [];
    $size = 0;
    while (false !== ($row = $db->fetchArray($result))) {
        $mtags[$row['tag']] = $row;
        $size = $row['posts'] > $size ? $row['posts'] : $size;
    }

    ksort($mtags);

    // All tags
    list($num) = $db->fetchRow($db->query('SELECT COUNT(*) FROM ' . $db->prefix('mod_mywords_tags')));
    $page = rmc_server_var($_GET, 'page', 1);
    $limit = isset($limit) && $limit > 0 ? $limit : 15;

    $tpages = ceil($num / $limit);
    $page = $page > $tpages ? $tpages : $page;

    $start = $num <= 0 ? 0 : ($page - 1) * $limit;

    $nav = new RMPageNav($num, $limit, $page, 5);
    $nav->target_url('tags.php?page={PAGE_NUM}');

    $sql = 'SELECT * FROM ' . $db->prefix('mod_mywords_tags') . " ORDER BY id_tag DESC LIMIT $start,$limit";

    $result = $db->query($sql);
    $tags = [];
    while (false !== ($row = $db->fetchArray($result))) {
        $tags[] = $row;
    }

    RMBreadCrumb::get()->add_crumb(__('Tags management', 'mywords'));

    xoops_cp_header();
    RMTemplate::getInstance()->add_script(RMCURL . '/include/js/jquery.checkboxes.js');
    RMTemplate::getInstance()->add_script('..//include/js/scripts.php?file=tags-list.js');
    RMTemplate::getInstance()->add_style('jquery.css', 'rmcommon');
    include RMTemplate::getInstance()->get_template('admin/mywords-tags.php', 'module', 'mywords');

    xoops_cp_footer();
}

/**
 * Save a new tag or update an existing tag
 * @param bool $edit Save or edit
 */
function save_tag($edit = false)
{
    global $xoopsConfig, $xoopsSecurity;

    $page = rmc_server_var($_POST, 'page', 1);

    if (!$xoopsSecurity->check()) {
        redirectMsg('tags.php?page=' . $page, __('Operation not allowed!', 'mywords'), 1);
        die();
    }

    $name = rmc_server_var($_POST, 'name', '');
    $short = rmc_server_var($_POST, 'short', '');

    if ('' == $name) {
        redirectMsg('tags.php?page=' . $page, __('You must provide a name!', 'mywords'), 1);
        die();
    }

    if ($edit) {
        $id = rmc_server_var($_POST, 'id', 0);
        if ($id <= 0) {
            redirectMsg('tags.php?page=' . $page, __('Tag id not provided!', 'mywords'), 1);
            die();
        }

        $tag = new MWTag($id);
        if ($tag->isNew()) {
            redirectMsg('tags.php?page=' . $page, __('Tag does not exists!', 'mywords'), 1);
            die();
        }
    } else {
        $tag = new MWTag();
    }

    if ('' == trim($short)) {
        $short = TextCleaner::sweetstring($name);
    } else {
        $short = TextCleaner::sweetstring($short);
    }

    // Check if tag exists
    $db = XoopsDatabaseFactory::getDatabaseConnection();
    if ($edit) {
        $sql = 'SELECT COUNT(*) FROM ' . $db->prefix('mod_mywords_tags') . " WHERE (tag='$name' OR shortname='$short') AND id_tag<>$id";
    } else {
        $sql = 'SELECT COUNT(*) FROM ' . $db->prefix('mod_mywords_tags') . " WHERE tag='$name' OR shortname='$short'";
    }

    list($num) = $db->fetchRow($db->query($sql));
    if ($num > 0) {
        redirectMsg('tags.php?page=' . $page, __('A tag with same name or same short name already exists!', 'mywords'), 1);
        die();
    }

    $tag->setVar('tag', $name);
    $tag->setVar('shortname', $short);
    if ($tag->save()) {
        redirectMsg('tags.php', __('Database updated successfully!', 'mywords'), 0);
        die();
    }
    redirectMsg('tags.php?page=' . $page, __('A problem occurs while trying to save tag.', 'mywords') . '<br>' . $tag->errors(), 1);
    die();
}

function edit_form()
{
    global $xoopsModule, $xoopsSecurity;

    $id = rmc_server_var($_GET, 'id', 0);
    $page = rmc_server_var($_GET, 'page', 1);

    if ($id <= 0) {
        redirectMsg('tags.php?page=' . $page, __('Tag ID not provided!.', 'mywords'), 1);
        die();
    }
    $tag = new MWTag($id);
    if ($tag->isNew()) {
        redirectMsg('tags.php?page=' . $page, __('Tag does not exists!', 'mywords'), 1);
        die();
    }

    MWFunctions::include_required_files();

    RMBreadCrumb::get()->add_crumb(__('Tags management', 'mywords'), 'tags.php');
    RMBreadCrumb::get()->add_crumb(__('Edit tag', 'mywords'));

    RMTemplate::getInstance()->assign('xoops_pagetitle', __('Editing Tag', 'mywords'));
    xoops_cp_header();
    $show_edit = true;
    include RMTemplate::getInstance()->get_template('admin/mywords-tags.php', 'module', 'mywords');

    xoops_cp_footer();
}

/**
 * Deletes a existing tag or set of tags*
 */
function delete_tag()
{
    global $xoopsModule, $xoopsSecurity;

    $page = rmc_server_var($_POST, 'page', 1);
    $tags = rmc_server_var($_POST, 'tags', []);

    if (!$xoopsSecurity->check()) {
        redirectMsg('tags.php?page=' . $page, __('Sorry, operation not allowed!', 'mywords'), 1);
        die();
    }

    if (!is_array($tags) || empty($tags)) {
        redirectMsg('tags.php?page=' . $page, __('Please, specify a valid tag id!', 'mywords'), 1);
        die();
    }

    // Delete all relations
    $db = XoopsDatabaseFactory::getDatabaseConnection();
    $sql = 'DELETE FROM ' . $db->prefix('mod_mywords_tagspost') . ' WHERE tag IN(' . implode(',', $tags) . ')';
    if (!$db->queryF($sql)) {
        redirectMsg('tags.php?page=' . $page, __('Errors ocurred while trying to delete tags!', 'mywords') . '<br>' . $db->error(), 1);
        die();
    }

    $sql = 'DELETE FROM ' . $db->prefix('mod_mywords_tags') . ' WHERE id_tag IN(' . implode(',', $tags) . ')';
    if (!$db->queryF($sql)) {
        redirectMsg('tags.php?page=' . $page, __('Errors ocurred while trying to delete tags!', 'mywords') . '<br>' . $db->error(), 1);
        die();
    }

    redirectMsg('tags.php?page=' . $page, __('Database updated succesfully!', 'mywords'), 0);
}

function update_tag()
{
    global $xoopsModule, $xoopsSecurity;

    $page = rmc_server_var($_POST, 'page', 1);
    $tags = rmc_server_var($_POST, 'tags', []);

    if (!$xoopsSecurity->check()) {
        redirectMsg('tags.php?page=' . $page, __('Sorry, operation not allowed!', 'mywords'), 1);
        die();
    }

    if (!is_array($tags) || empty($tags)) {
        redirectMsg('tags.php?page=' . $page, __('Please, specify a valid tag id!', 'mywords'), 1);
        die();
    }

    foreach ($tags as $id) {
        $tag = new MWTag($id);
        if ($tag->isNew()) {
            continue;
        }

        $tag->update_posts();
    }

    redirectMsg('tags.php?page=' . $page, __('Tags updated!', 'mywords'), 0);
}

$action = rmc_server_var($_REQUEST, 'action', '');

switch ($action) {
    case 'new':
        save_tag(false);
        break;
    case 'saveedit':
        save_tag(true);
        break;
    case 'edit':
        edit_form();
        break;
    case 'delete':
        delete_tag();
        break;
    case 'update':
        update_tag();
        break;
    default:
        show_tags();
        break;
}
