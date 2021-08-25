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
                if (is_dir($dir)) {
                    $scan = scandir($dir);

                    if ($scan && count($scan) > 0 && $dir !== $d_plugin_dirs['plugin'] . '/') {
                        foreach ($scan as $i => $file) {
                            if ($file !== '.' && $file !== '..' && $file !== '.git' && $file !== '' && file_exists($dir . '/' . $file)) {
                                require_once $dir . '/' . $file;
                            }
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

    function filter_input($input = [])
    {

        return $input;
    }

    function sanitizer($value = '', $_type = false)
    {
        $check = false;
        $error = ['status' => 'Failed', 'message' => 'Field validation failed on submitted data.'];
        $type = ($_type && $_type !== false) ? $_type : gettype($value);
        switch ($type):
            case 'array':
                // TODO: sanitize array values //
                return $value;
                break;
            case 'email':
                return sanitize_email($value);
                break;
            case 'string':
            default:
                return sanitize_text_field($value);

                break;
        endswitch;


        return $this->handle_response($error, 403);
    }

    var $is_ajax = false;

    function validate_request($is_ajax = false)
    {
        $this->is_ajax = $is_ajax;
        // Get the request data //
        $_data = (isset($_REQUEST) && isset($_REQUEST['action'])) ? $_REQUEST : false;
        $error = ['status' => 'Failed', 'message' => 'Initial Error'];
        // Validate request NONCE legitimacy //
        if (!isset($_data['d_chargify_nonce']) || !wp_verify_nonce($_data['d_chargify_nonce'], 'netcito_nonce'))
            return $this->handle_response(['status' => 'Failed', 'message' => 'There was either no security nonce submitted or it was invalid'], 403);

        $values = [];
        if ($_data === false) {
            $error['message'] = 'The required data is either blank or not present';
            $this->handle_response($error, 403);
        } else {
            // we have our needed data / and security validated for request origin //

            // Check to see if our required data was submitted //
            // If our validation map is present and not empty then we validate input otherwise let it pass //
            if (isset($this->validations) && !empty($this->validations)) {
                foreach ($this->validations as $validation) {

                    $value = (isset($_data[$validation['key']]) ? $_data[$validation['key']] : false);
                    if (isset($validation['multi']) && $validation['multi']) {
                        // find all the matching submissions //
                        $value[$validation['key']] = [];
                        foreach ($_data as $ns => $val) {
                            $key = (strpos($ns, $validation['key']) !== false ? $ns : false);
                            if ($key) {
                                $x = explode('-', $key);
                                $id = $x[count($x) - 1];
                                $value[$validation['key']][$id] = $val;
                            }
                        }
                    }

                    if ((isset($validation['key']) && $validation['key'] !== '')) {
                        // if there is a rule set then check it otherwise if not empty it passes //
                        $error['field'] = $validation['key'];
                        $error['message'] = 'Validation of input fields has failed. Please check the data submitted. Field: "' . $validation['key'] . '"';

                        if (isset($validation['rules'])) {

                            foreach ($validation['rules'] as $ns => $rule) {
                                $passed = $stype = false;
                                $append = (isset($validation['append']) && $validation['append']) ? $validation['append'] : '';

                                $type = (is_array($rule) && isset($rule['type']) ? $rule['type'] : $ns);
                                $error['rule'] = $type;

                                switch ($type):
                                    case 'is_array':
                                        $passed = (is_array($value));

                                        break;
                                    case 'required':
                                        $passed = ($value && (is_string($value) && $value !== '' || (is_array($value) && !empty($value))));

                                        break;
                                    case 'is_email':
                                        $passed = (strpos($value, '@') >= 0);
                                        if ($passed)
                                            $stype = 'email';

                                        break;
                                    case 'is_int':
                                        $passed = (is_int($value));

                                        break;
                                    case 'not_empty':
                                    default:
                                        $passed = ((is_string($value) && $value !== '') || (is_array($value) && !empty($value)));

                                        break;
                                endswitch;

                                $error['field'] = $key = $validation['key'] . $append;

                                if ($passed) {
                                    $values[$key] = $this->sanitizer($value, $stype);
                                } else {
                                    return $this->handle_response($error, 403);
                                }
                            }
                        } else if (isset($_data[$validation['key']])) {
                            $values[$validation['key']] = $this->sanitizer($_data[$validation['key']]);
                        } else if (!isset($_data[$validation['key']]) && isset($validation['multi']) && $validation['multi'] && isset($value[$validation['key']])) {
                            $values[$validation['key']] = $value[$validation['key']];
                        }
                    }
                }
            } else {
                // Process values without running normal rules //
                foreach ($_data as $ns => $value) {
                    $values[$ns] = $this->sanitizer($value);
                }
            }
            if ($values && !empty($values)) {
                return $values;
            }
        }


        // If valid return scrubbed post data //
        return $this->handle_response($error, 404);
    }

    function handle_response($response = ['status' => 'Failed', 'message' => 'No status was passed to the response handler.', 'default_error' => true], $code = 200)
    {
        if (!isset($response['status']) || (isset($response['default_error']) && $response['default_error'])) {
            return $this->error_out($response, 403);
        }

        if ($code !== 200) {
            // failed handler //
            $this->error_out($response, $code);
        } else {
            $this->success($response);
        }

        return false;
    }

    function error_out($response, $code)
    {
        // Do some stuff if something errors out like create logs and email to PLUGIN management //
        // TODO: Add logger here for the error in our wp_options = 'fulcrum_errors' = ['module' => 'module name', 'users current page / request', 'is ajax request', 'timestamp'] //
        if (isset($this->is_ajax) && $this->is_ajax) {
            $this->end_ajax($response, $code);
        } else {
            return $response;
        }
    }

    function success($response)
    {
        if (isset($this->is_ajax) && $this->is_ajax) {
            $this->end_ajax($response, 200);
        } else {
            return $response;
        }
    }

    function end_ajax($response = ['status' => 'failed', 'message' => 'default init error'], $code = 400)
    {
        wp_send_json($response, $code);
    }
}
