<?php

namespace D\CHARGIFY\CORE;

/**
 * Enqueue our scripts into wordpress
 */

use  D\CHARGIFY\TRAITS\PRIME as D_PRIME;

class d_enqueue
{
    use D_PRIME;

    var $registered = [
        'scripts' => [],
        'styles' => []
    ];


    function _define()
    {
        $this->actions = [
            'add_admin_styles' => [
                'function' => 'admin_styles',
                'hook' => 'admin_enqueue_scripts'
            ],
            'add_admin_scripts' => [
                'function' => 'admin_scripts',
                'hook' => 'admin_enqueue_scripts'
            ],
        ];

        $this->scripts = [
            'd-admin-main-js' => [
                'file_name' => 'main.js',
                'params' => [
                    'deps' => ['jquery', 'jquery-ui-core']
                ],
                'version' => '0.0.1',
            ]
        ];

        $this->styles = [
            'd-admin-main-css' => [
                'file_name' => 'main.css',
                'version' => '0.0.1',
            ]
        ];

        // $this->filters = [];
    }

    function __construct()
    {
        $this->_define();
        // Register our Actions and Filters //
        $this->_actions($this->actions);
        $this->_filters($this->filters);
    }

    function init()
    {
        $this->register();
    }

    private function _reg($type, $objs = [])
    {
        global $d_plugin_dirs;

        if (isset($d_plugin_dirs[$type]) && count($objs) > 0) {
            if (!isset($this->registered[$type])) $this->registered[$type] = [];
            foreach ($objs as $ns => $opts) {
                $uri = plugin_dir_url(__DIR__) . $d_plugin_dirs[$type] . '/' . $opts['file_name'];

                $this->registered[$type][$ns] = d_register_asset(
                    $ns,
                    $uri,
                    $type,
                    (isset($opts['version']) ? $opts['version'] : false),
                    (isset($opts['params']) ? $opts['params'] : [])
                );
            }
        }
    }

    public function register()
    {
        // Lets enqueue and register scripts and styles for proper enqueue //
        if (!empty($this->scripts)) {
            $this->_reg('scripts', $this->scripts);
        }

        if (!empty($this->styles)) {
            $this->_reg('styles', $this->styles);
        }
    }

    public function admin_scripts()
    {
        $this->_admin_('scripts');
    }

    public function admin_styles()
    {
        $this->_admin_('styles');
    }

    private function _admin_($type)
    {
        $registered = (isset($this->registered[$type]) ? $this->registered[$type] : false);
        // Add any extra processors for our enqueue scripts //

        // TODO: Add action here to turn off styles and a filter to add custom scripts from the theme //
        if ($registered) {
            $this->_enqueue($type, $registered);
        }
    }
}

if (!function_exists('d__init_enqueue')) {
    function d__init_enqueue()
    {
        global $d__enqueue;

        return (!isset($d__enqueue) ? new d_enqueue() : $d__enqueue);
    }
}

$d__enqueue = d__init_enqueue();
$d__enqueue->init();
