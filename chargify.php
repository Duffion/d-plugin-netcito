<?php

namespace D\CHARGIFY;

/*
 * Plugin Name: Chargify API Helper Tool
 * Plugin URI: https://duffion.com
 * Description: This is a custom built tool that allows for easy integration with the Chargify API
 * Version: 1.0.1
 * Author: Christopher "Duffs" Crevling & John Underwood
 * Text Domain: chargify-api-helper
 * Author URI: https://duffion.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('D_CHARGIFY')) :

    // Load in global vars //
    $d_plugin_dirs = [];
    $d_modules = [];

    class D_CHARGIFY
    {

        var $version = '1.0.1';

        public $settings = [];

        public $modules = [];

        public $dirs = [
            'partials' => 'templates/partials',
            'modules' => 'inc/modules',
            'inc' => 'inc',
            'traits' => 'inc/traits',
            'assets' => 'assets',
            'scripts' => 'assets/js',
            'styles' => 'assets/css',
            'fe_styles' => 'assets/css/fe',
            'templates' => 'templates',
            'modules' => 'inc/modules',
            'templates-modules' => 'templates/modules',
            'vendors' => 'inc/vendors'
        ];

        // [ 'filename without php' => 'name of dir from above config' ] //
        private $_loading = [
            'core' => 'inc',
            'enqueue' => 'inc'
        ];

        private $instance = [];

        /**
         * __construct - []
         *
         */
        function __construct()
        {
            $this->_define();
        }

        function _git_updater()
        {
            require $this->dirs['plugin'] . '/' . $this->dirs['vendors'] . '/plugin-update-checker/plugin-update-checker.php';

            $config = [
                'git' => 'https://github.com/Duffion/d-plugin-netcito/',
                'target_branch' => 'production'
            ];

            $this->updater = \Puc_v4_Factory::buildUpdateChecker(
                $config['git'],
                __FILE__,
                'd-plugin-netcito'
            );

            //Set the branch that contains the stable release.
            $this->updater->setBranch($config['target_branch']);
        }

        /**
         * _load - []
         * We need to load in all the required core files / traits
         */
        function _load()
        {
            global $d_instance, $d_loaded;
            // Lets create a global instance to make sure we only load items not already loaded //
            $d_loaded = [];
            $d_instance = (!isset($d_instance) ? [] : $d_instance);

            require_once $this->dirs['plugin'] . '/' . $this->dirs['inc'] . '/util.php';
            require_once $this->dirs['plugin'] . '/' . $this->dirs['traits'] . '/d-primary.php';
            require_once $this->dirs['plugin'] . '/' . $this->dirs['traits'] . '/d-templates.php';
            require_once $this->dirs['plugin'] . '/' . $this->dirs['inc'] . '/vendors.php';

            $this->_git_updater();

            // Lets now load in our other flies with the util loader //
            if ($this->_loading && count($this->_loading) > 0) {
                foreach ($this->_loading as $file => $dir_name) {
                    $file_loc = (isset($this->dirs[$dir_name]) ? $this->dirs['plugin'] . '/' . $this->dirs[$dir_name] . '/' . $file . '.php' : false);
                    if ($file_loc) d_req($file_loc);

                    $this->instance['loaded'] = $d_loaded;
                }
            }
        }

        /**
         * _define - []
         *
         */
        function _define($r = false)
        {
            global $d_plugin_dirs;

            $this->dirs['plugin'] = rtrim(plugin_dir_path(__FILE__), '/');

            $d_plugin_dirs = $this->dirs;
        }

        /**
         * init - []
         *
         */
        function init()
        {
            // Load in any needed configs or passable globals here so loaded items can use properly //

            // Lets manually load in our starting files //
            $this->_load();

            // Do anything extra after we have loaded in the core //

        }
    }

    /**
     * Global Functionset - D_CHARGIFY() - only run once []
     *
     */
    function D_CHARGIFY()
    {
        global $d_chargify;

        if (!isset($d_chargify)) {
            $d_chargify = new d_chargify();
            $d_chargify->init();
        }

        return $d_chargify;
    }

    // Instantiate
    D_CHARGIFY();

endif;
