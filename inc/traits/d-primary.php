<?php

namespace D\CHARGIFY\TRAITS;

use function PHPUnit\Framework\directoryExists;

trait PRIME
{

    /*
    Menu items expectations:
    [
        'page_title' => __('Custom Menu Title', 'textdomain'),
        'menu_title' => '',
        'capability' => '',
        'menu_slug' => '',
        'function' => '',
        'icon_url' => '',
        'position' => 10
    ]
    */
    var $added_menu = [];

    function add_submenu($submenu)
    {
        if (count($submenu) > 0) {
            $submenu[] = $this->menu_item;
        }

        return $submenu;
    }

    function register_settings($menu_items = [], $that = false)
    {
        if (!$that) $that = $this;

        global $d_admin_menus;

        if (isset($d_admin_menus)) array_push($menu_items, $d_admin_menus);

        if (!empty($menu_items)) {
            foreach ($menu_items as $key => $item) {
                $item['subpages'] = apply_filters('d-add-subpages--' . $key, $item['subpages']);
                $this->added_menu[$key] = [];

                $this->added_menu[$key]['parent'] = add_menu_page(
                    $item['page_title'],
                    $item['menu_title'],
                    $item['capability'],
                    $item['menu_slug'],
                    (!is_array($item['function']) ? [$that, $item['function']] : $item['function']),
                    $item['icon_url'],
                    $item['position']
                );

                // wpp($item);
                if (isset($item['subpages']) && !empty($item['subpages'])) {
                    $this->added_menu[$key]['children'] = [];
                    foreach ($item['subpages'] as $ns => $sub_item) {
                        // wpp($sub_item);
                        $this->added_menu[$key]['children'][$ns] = add_submenu_page(
                            $item['menu_slug'],
                            $sub_item['page_title'],
                            $sub_item['menu_title'],
                            $sub_item['capability'],
                            $sub_item['menu_slug'],
                            (!is_array($sub_item['function']) ? [$that, $sub_item['function']] : $sub_item['function']),
                            (isset($sub_item['icon_url']) ? $sub_item['icon_url'] : ''),
                            (isset($sub_item['position']) ? $sub_item['position'] : NULL)
                        );
                    }
                }
            }
        }
        // wpp($this->added_menu, 1);
        return $this->added_menu;
    }

    var $scripts = [];

    var $styles = [];

    /**
     * autoload($directory)
     */
    function autoloader($dirs)
    {
        global $d_plugin_dirs;

        if ($dirs && count($dirs) > 0) {
            foreach ($dirs as $ns) {
                $dir = $d_plugin_dirs['plugin'] . '/' . $d_plugin_dirs[$ns];
                $scan = scandir($dir);

                if ($scan && count($scan) > 0) {

                    foreach ($scan as $i => $file) {
                        if ($file !== '.' && $file !== '..' && $file !== '' && file_exists($dir . '/' . $file)) {
                            require_once $dir . '/' . $file;
                        }
                    }
                }
            }
        }
    }

    /**
     * _enqueue($type, $targets = [])
     */
    function _enqueue($type, $targets)
    {
        if (is_array($targets) && count($targets) > 0) {
            foreach ($targets as $reg_ns => $reged) {
                if ($reged) {
                    switch ($type):
                        case 'scripts':
                            wp_enqueue_script($reg_ns);
                            break;
                        case 'styles':
                            wp_enqueue_style($reg_ns);
                            break;
                    endswitch;
                }
            }
        }
    }

    var $actions = [];
    // Register actions dynamically //
    /**
     * Expects:
     * $items = [
     * '{unique_function_name}' => [
     *       'hook' => '',
     *       'function' => '',
     *       'priority' => 0,
     *       'args' => 0
     *   ]
     * ];
     */
    function _actions($items = [], $that = false)
    {
        if (!$that) $that = &$this;
        if (count($items) > 0) {
            foreach ($items as $key => $params) {
                if (isset($params['function'])) {
                    add_action(
                        (!isset($params['hook']) ? $key : $params['hook']),
                        [$that, $params['function']],
                        (isset($params['priority']) ? $params['priority'] : 1),
                        (isset($params['accepted_args']) ? $params['accepted_args'] : 1)
                    );
                }
            }
        }
    }

    var $filters = [];
    /**
     * Expects:
     * $items = [
     * '{unique_function_name}' => [
     *       'hook' => '',
     *       'function' => '',
     *       'priority' => 0,
     *       'args' => 0
     *   ]
     * ];
     */
    function _filters($items = [], $that = false)
    {
        if (!$that) $that = $this;

        if (count($items) > 0) {
            foreach ($items as $key => $params) {
                if (isset($params['function'])) {
                    // $funct = (isset($params['self']) && $params['self']) ? $params['function'] : [$that, $params['function']];

                    add_filter(
                        (!isset($params['hook']) ? $key : $params['hook']),
                        [$that, $params['function']],
                        (isset($params['priority']) ? $params['priority'] : 1),
                        (isset($params['accepted_args']) ? $params['accepted_args'] : 1)
                    );
                }
            }
        }
    }
}
