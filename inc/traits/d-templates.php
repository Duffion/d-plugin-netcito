<?php

namespace D\CHARGIFY\TRAITS;

trait TEMPLATES
{

    var $template_dir = '/templates/';

    function partial($sub, $filename, $args = [])
    {
        global $d_plugin_dirs;

        $output = '';
        $dir = $d_plugin_dirs['plugin'] . $this->template_dir . $sub . '/';
        $file = $filename . '.php';
        if (file_exists($dir . $file)) {
            include_once($dir . $file);
        }
    }

    function start()
    {
    }

    function create_nonce()
    {
        return wp_create_nonce('netcito_nonce');
    }
}
