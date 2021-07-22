<?php

/**
 * Our primary plugin SETTINGS plugin handler
 */

use D_CHARGIFY\TRAITS\PRIME as D_PRIME;

class d_settings
{
    use D_PRIME;

    var $actions = [];

    var $filters = [];

    function __construct()
    {
        // Register our Actions and Filters //
        $this->_actions($this->actions);
        $this->_filters($this->filters);

        return $this;
    }
}

if (!function_exists('d__init_settings')) {
    function d__init_settings()
    {
        global $d__settings;

        return (!isset($d__settings) ? new d_settings() : $d__settings);
    }
}

$d__settings = d__init_settings();
