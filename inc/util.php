<?php

/**
 * Template function starter:
 * if (!function_exists('FUNCT')) {
 *  function FUNCT()
 *  {
 *      // Something here //
 *   }
 * }
 */

$d_loaded = $d_dirs = [];
/**
 * d_req() -- Our custom require statement
 */
if (!function_exists('d_req')) {
    function d_req($file_loc)
    {
        global $d_loaded;
        if (isset($d_loaded) && !in_array($file_loc, $d_loaded) && file_exists($file_loc)) {
            require_once($file_loc);
            $d_loaded[] = $file_loc;
        }
    }
}

/**
 * d_register_asset( $namespace = 'string', $location = 'uri', $type = 'script' $version = '0.0.1', $params = [] )
 * Our debug functionality for better work structure in wp
 */
if (!function_exists('d_register_asset')) {
    function d_register_asset($ns, $location, $type = 'script', $version = false, $params = [])
    {
        switch ($type):
            case 'scripts':
                return wp_register_script($ns, $location, (isset($params['deps']) ? $params['deps'] : []), $version, (isset($params['in_footer']) ? $params['in_footer'] : false));
                break;
            case 'styles':
                return wp_register_style($ns, $location, (isset($params['deps']) ? $params['deps'] : []), $version, (isset($params['media']) ? $params['media'] : 'all'));
                break;
        endswitch;
    }
}

/**
 * wpp( $object_to_see, $kill_everything )
 * Our debug functionality for better work structure in wp
 */
if (!function_exists('wpp')) {
    function wpp($obj = [], $die = false)
    {
        print_r('<pre>') . print_r($obj) . print_r('</pre>');

        if ($die) die;
    }
}
