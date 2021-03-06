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
class MywordsRmcommonPreload
{
    public static function eventRmcommonLoadRightWidgets($widgets)
    {
        global $xoopsModule;

        if (!isset($xoopsModule) || ('system' !== $xoopsModule->getVar('dirname') && 'mywords' !== $xoopsModule->getVar('dirname'))) {
            return $widgets;
        }

        // Check edition
        $id = RMHttpRequest::request('id', 'integer', 0);
        $op = RMHttpRequest::request('op', 'string', '');
        $edit = 'edit' === $op ? 1 : 0;
        $post = null;

        if ($edit) {
            //Verificamos que el software sea válido
            if ($id <= 0) {
                $params = '';
            }

            $post = new MWPost($id);
        }

        if (defined('RMCSUBLOCATION') && RMCSUBLOCATION === 'new_post') {
            require_once dirname(__DIR__) . '/widgets/widget-publish.php';
            $widgets[] = mywords_widget_publish($post);

            require_once dirname(__DIR__) . '/widgets/widget-post-type.php';
            $widgets[] = mywords_widget_post_type($post);

            require_once dirname(__DIR__) . '/widgets/widget-image.php';
            $widgets[] = mywords_widget_image($post);

            require_once dirname(__DIR__) . '/widgets/widget-categories.php';
            $widgets[] = mywords_widget_categories($post);

            require_once dirname(__DIR__) . '/widgets/widget-tags.php';
            $widgets[] = mywords_widget_addtags($post);
        }

        return $widgets;
    }

    public static function eventRmcommonGetSystemTools($tools)
    {
        load_mod_locale('mywords');

        $rtn[] = (object) [
            'link' => '../mywords/admin/posts.php',
            'icon' => 'svg-rmcommon-comment',
            'caption' => __('Articles', 'admin_mywords'),
            'color' => 'amber',
        ];

        $tools = array_merge($rtn, $tools);

        return $tools;
    }

    public static function eventRmcommonImageInsertLinks($links, $image, $url)
    {
        if (false === mb_strpos($url, 'modules/mywords/admin/posts.php')) {
            return $links;
        }

        parse_str($url, $output);
        if (!isset($id) || $id <= 0) {
            return $links;
        }

        xoops_load('mwpost.class', 'mywords');
        xoops_load('mwfunctions', 'mywords');

        $post = new MWPost($id);
        if ($post->isNew()) {
            return $links;
        }

        $links['post'] = ['caption' => __('Link to post', 'mywords'), 'value' => $post->permalink()];

        return $links;
    }

    /**
     * Return the feed options to show in RSS Center
     * @param mixed $feeds
     * @return array|mixed
     */
    public static function eventRmcommonGetFeedsList($feeds)
    {
        global $cuSettings;

        require_once XOOPS_ROOT_PATH . '/modules/mywords/class/mwfunctions.php';
        load_mod_locale('mywords');

        $module = RMModules::load_module('mywords');
        $config = RMSettings::module_settings('mywords');

        $data = [
                'title' => $module->name(),
                'url' => XOOPS_URL . $config->basepath,
                'module' => 'mywords',
        ];

        $options[] = [
            'title' => __('All Recent Posts', 'mywords'),
            'params' => 'show=all',
            'description' => __('Show all recent posts', 'mywords'),
        ];

        $categories = [];
        MWFunctions::categos_list($categories);

        $table = '<table cellpadding="2" cellspacing="2" width="100%"><tr class="even">';
        $count = 0;
        $base_link = $cuSettings->permalinks ? XOOPS_URL . '/rss/' : XOOPS_URL . '/backend.php';
        foreach ($categories as $cat) {
            if ($count >= 3) {
                $count = 0;
                $table .= '</tr><tr class="' . tpl_cycle('odd,even') . '">';
            }
            $table .= '<td width="33%"><a href="' . $base_link . '?action=showfeed&amp;mod=mywords&amp;show=cat&amp;cat=' . $cat['id_cat'] . '">' . $cat['name'] . '</a></td>';
            $count++;
        }
        $table .= '</tr></table>';

        $options[] = [
            'title' => __('Posts by category', 'mywords'),
            'description' => __('Select a category to see the posts published recently.', 'mywords') . ' <a href="javascript:;" onclick="$(\'#categories-feed\').slideToggle(\'slow\');">Show Categories</a>
                            <div id="categories-feed" style="padding: 10px; display: none;">' . $table . '</div>',
        ];

        unset($categories);

        $tags = MWFunctions::get_tags('*', '', '', 99);
        $table = '<table cellpadding="2" cellspacing="2" width="100%"><tr class="even">';
        $count = 0;
        foreach ($tags as $tag) {
            if ($count >= 3) {
                $count = 0;
                $table .= '</tr><tr class="' . tpl_cycle('odd,even') . '">';
            }
            $table .= '<td width="33%"><a href="' . $base_link . '?action=showfeed&amp;mod=mywords&amp;show=tag&amp;tag=' . $tag['id_tag'] . '">' . $tag['tag'] . '</a></td>';
            $count++;
        }
        $table .= '</tr></table>';

        $options[] = [
            'title' => __('Show posts by tag', 'mywords'),
            'description' => __('Select a tag to see the posts published recently.', 'mywords') . ' <a href="javascript:;" onclick="$(\'#tags-feed\').slideToggle(\'slow\');">Show Tags</a>
                            <div id="tags-feed" style="padding: 10px; display: none;">' . $table . '</div>',
        ];

        unset($tags);

        $db = XoopsDatabaseFactory::getDatabaseConnection();
        $sql = 'SELECT * FROM ' . $db->prefix('mod_mywords_editors') . ' ORDER BY name';
        $result = $db->query($sql);
        $editors = [];
        while (false !== ($row = $db->fetchArray($result))) {
            $editors[] = $row;
        }
        asort($editors);

        $table = '<table cellpadding="2" cellspacing="2" width="100%"><tr class="even">';
        $count = 0;
        foreach ($editors as $ed) {
            if ($count >= 3) {
                $count = 0;
                $table .= '</tr><tr class="' . tpl_cycle('odd,even') . '">';
            }
            $table .= '<td width="33%"><a href="' . $base_link . '?action=showfeed&amp;mod=mywords&amp;show=author&amp;author=' . $ed['id_editor'] . '">' . $ed['name'] . '</a></td>';
            $count++;
        }
        $table .= '</tr></table>';

        $options[] = [
            'title' => __('Show posts by author', 'mywords'),
            'description' => __('Select an author to see the posts published recently.', 'mywords') . ' <a href="javascript:;" onclick="$(\'#editor-feed\').slideToggle(\'slow\');">Show Authors</a>
                            <div id="editor-feed" style="padding: 10px; display: none;">' . $table . '</div>',
        ];

        unset($editors);
        unset($table);

        RMTemplate::getInstance()->add_jquery();

        $feed = ['data' => $data, 'options' => $options];
        $feeds[] = $feed;

        return $feeds;
    }
}
