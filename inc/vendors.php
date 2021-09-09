<?php

/* Load in required vendors */

use  D\CHARGIFY\TRAITS\PRIME as D_PRIME;

class d_vendor
{

    use D_PRIME;

    // Vendor Vars / Configs //
    var $vendors = [
            'plugin-update-checker' => [
                'dir' => 'plugin-update-checker/',
            ]
        ],
        $dir = '/vendors/';

    function __construct()
    {
        $this->_define();
    }

    function _define()
    {
    }

    function setup()
    {
        $this->init_vendors();
    }

    function init_vendors()
    {
        global $d_plugin_dirs;
        $vendors = $this->vendors;

        if ($vendors) {
            foreach ($vendors as $ns => $config) {
                $file = 'd__setup.php';
                $dir = $d_plugin_dirs['plugin'] . '/' . $d_plugin_dirs['inc'] . $this->dir . $config['dir'];

                if (file_exists($dir . $file)) {
                    require_once $dir . $file;
                }
            }
        }
    }
}

if (!function_exists('d__init_vendor')) {
    function d__init_vendor()
    {
        global $d__vendor;

        return (!isset($d__vendor) ? new d_vendor() : $d__vendor);
    }
}

$d__vendor = d__init_vendor();
$d__vendor->setup();
