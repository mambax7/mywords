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
set_time_limit(0);
/**
 * This function show an error message
 * @param mixed $msg
 * @param mixed $token
 * @param mixed $redirect
 */
function return_error($msg, $token = true, $redirect = '')
{
    global $xoopsSecurity;

    $ret['error'] = $msg;
    if ($token) {
        $ret['token'] = $xoopsSecurity->createToken();
    }
    if ('' != $redirect) {
        $ret['redirect'] = $redirect;
    }

    echo json_encode($ret);
    die();
}

$no_includes = true;

require  dirname(dirname(dirname(__DIR__))) . '/mainfile.php';
require  dirname(__DIR__) . '/header.php';

include XOOPS_ROOT_PATH . '/modules/mywords/class/mwtrackback.php';

global $xoopsLogger;
$xoopsLogger->renderingEnabled = false;
error_reporting(0);
$xoopsLogger->activated = false;

$frontend = 0;
extract($_POST);

/*if(!$xoopsSecurity->check() || !$xoopsSecurity->checkReferer()){
    $ret = array(
        'error'=>__('You are not allowed to do this operation!','mywords')
    );
    echo json_encode($ret);
    die();
}*/
$mc = RMSettings::module_settings('mywords');

if (!isset($xoopsUser) || (!$xoopsUser->isAdmin() && !$mc->submit)) {
    return_error(__('You are not allowed to do this action!', 'mywords'), false, MW_URL);
}

$editor = new MWEditor();
$editor->from_user($author);

if ('saveedit' === $op) {
    if (!isset($id) || $id <= 0) {
        return_error(__('You must provide a valid post ID', 'mywords'), 0, 'posts.php');
        die();
    }

    $post = new MWPost($id);
    if ($post->isNew()) {
        return_error(__('You must provide an existing post ID', 'mywords'), 0, 'posts.php');
        die();
    }

    if (!$xoopsUser->isAdmin() && !$editor->id() == $post->getVar('author')) {
        return_error(__('You are not allowed to do this action!', 'mywords'), false, MW_URL);
    }

    $query = 'op=edit&id=' . $id;
    $edit = true;
} else {
    $query = 'op=new';
    $post = new MWPost();
    $edit = false;
}

/**
 * @todo Insert code to verify token
 */

// Verify title
if ('' == $title) {
    return_error(__('You must provide a title for this post', 'mywords'), true);
    die();
}

if (!isset($shortname) || '' == $shortname) {
    $shortname = TextCleaner::getInstance()->sweetstring($title);
} else {
    $shortname = TextCleaner::getInstance()->sweetstring($shortname);
}

// Check content
if ('' == $content && 'image' !== $format) {
    return_error(__('Content for this post has not been provided!', 'mywords'), true);
    die();
}

// Categories
if (!isset($categories) || empty($categories)) {
    $categories = [MWFunctions::get()->default_category_id()];
}

// Check publish options
if ('password' === $visibility && '' == $vis_password) {
    return_error(__('You must provide a password for this post or select another visibility option', 'mywords'), true);
    die();
}

$time = explode('-', $schedule);
$schedule = mktime($time[3], $time[4], 0, $time[1], $time[0], $time[2]);
if ($schedule <= time()) {
    $schedule = 0;
}

$editor = new MWEditor($xoopsUser->uid(), 'user');
if ($editor->isNew()) {
    $editor->setVar('uid', $xoopsUser->uid());
    $editor->setVar('shortname', $xoopsUser->getVar('uname'));
    $editor->setVar('name', $xoopsUser->getVar('name'));
    $editor->setVar('bio', $xoopsUser->getVar('bio'));
    $editor->setVar('active', 0);
    $editor->save();
}

// Add Data
$post->setVar('title', $title);
$post->setVar('shortname', $shortname);
$post->setVar('content', $content);

if ($editor->isNew() && !$xoopsUser->isAdmin()) {
    $status = 'pending';
} else {
    if ($xoopsUser->isAdmin()) {
        $status = $status;
    } elseif ($mc->approve && $editor->active) {
        $status = $status;
    } else {
        $status = 'pending';
    }
}

$post->setVar('status', $status);
$post->setVar('visibility', $visibility);
$post->setVar('schedule', $schedule);
$post->setVar('password', $vis_password);
$post->setVar('author', $editor->id());
$post->setVar('comstatus', isset($comstatus) ? $comstatus : 0);
$post->setVar('pingstatus', isset($pingstatus) ? $pingstatus : 0);
$post->setVar('authorname', '' != $editor->name ? $editor->name : $editor->shortname);
$post->setVar('image', $image);
$post->setVar('format', $format);

// SEO
$post->setVar('description', $description);
$post->setVar('keywords', $keywords);
$post->setVar('customtitle', $seotitle);

if ($edit) {
    $post->setVar('modified', time());
}

if ($post->isNew()) {
    $post->setVar('created', time());
}

if ('draft' !== $status) {
    if (!$edit && $schedule <= time()) {
        $post->setVar('pubdate', time());
    } elseif ($edit && $schedule <= time()) {
        $post->setVar('pubdate', 0 == $post->getVar('pubdate') ? time() : $post->getVar('pubdate'));
    } else {
        $post->setVar('pubdate', 0);
    }
}

if (MWFunctions::post_exists($post)) {
    return_error(__('There is already another post with same title for same date', 'mywords'), $xoopsSecurity->createToken());
    die();
}

// Add categories
$post->add_categories($categories, true);

// Add tags
$post->add_tags($tags);

$post->clear_metas();

foreach ($meta as $data) {
    $post->add_meta($data['key'], $data['value']);
}

// before to save post
RMEvents::get()->run_event('mywords.saving.post', $post);

// Add trackbacks uris
$toping = [];
$pinged = $edit ? $post->getVar('pinged') : [];
if ('' != $trackbacks && $post->getVar('pingstatus')) {
    $trackbacks = explode(' ', $trackbacks);
} elseif ('' == $trackbacks && $post->getVar('pingstatus')) {
    $tb = new MWTrackback('', '');
    $trackbacks = $tb->auto_discovery($content);
}

if (!empty($trackbacks)) {
    foreach ($trackbacks as $t) {
        if (!empty($pinged) && in_array($t, $pinged, true)) {
            continue;
        }
        $toping[] = $t;
    }
}

$post->setVar('toping', !empty($toping) ? $toping : '');

$return = $edit ? $post->update() : $post->save();

if ($return) {
    if (!$edit) {
        $xoopsUser->incrementPost();
    }

    showMessage($edit ? __('Post updated successfully', 'mywords') : __('Post saved successfully', 'mywords'), 0);

    $url = MWFunctions::get_url();
    if ($mc->permalinks > 1) {
        $url .= $frontend ? 'edit/' . $post->id() : 'posts.php?op=edit&id=' . $post->id();
    } else {
        $url .= $frontend ? '?edit=' . $post->id() : 'posts.php?op=edit&id=' . $post->id();
    }

    $rtn = [
        'message' => $edit ? __('Post updated successfully', 'mywords') : __('Post saved successfully', 'mywords'),
        'token' => $xoopsSecurity->createToken(),
        'link' => '<strong>' . __('Permalink:', 'mywords') . '</strong> ' . $post->permalink(),
        'post' => $post->id(),
        'url' => $url,
    ];
    echo json_encode($rtn);
    die();
}
    return_error(__('Errors ocurred while trying to save this post.', 'mywords') . '<br>' . $post->errors(), true);
    die();
