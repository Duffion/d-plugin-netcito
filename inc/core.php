<?php

/**
 * Our primary CORE plugin handler
 */

use  D\CHARGIFY\TRAITS\PRIME as D_PRIME;
use  D\CHARGIFY\TRAITS\TEMPLATES as D_TEMPLATES;

class d_core
{
    use D_PRIME, D_TEMPLATES;

    // [ 'directory-namespace' => 'directory folder' ]
    private $auto_dirs = [
        'modules'
    ];

    private $frontend_scripts = [
        'fe-shortcode-helper.js'
    ];

    function __construct()
    {
        $this->_define();
        // Register our Actions and Filters //
        $this->_actions($this->actions);
        $this->_filters($this->filters);
    }

    function _define()
    {
        $this->actions = [
            'd-enqueue-scripts' => [
                'hook' => 'wp_enqueue_scripts',
                'function' => 'enqueue_scripts'
            ],
            'd-register-settings' => [
                'hook' => 'admin_init',
                'function' => 'settings_init'
            ],
            'd-register-shortcodes' => [
                'hook' => 'init',
                'function' => 'add_shortcodes'
            ],
            'd-register-menu' => [
                'hook' => 'admin_menu',
                'function' => 'add_admin_menu'
            ],
            // 'd-register-meta-box' => [
            //     'hook' => 'add_meta_boxes',
            //     'function' => 'add_dynamic_product_fields'
            // ],
            'd-on-submit-nopriv-check' => [
                'hook' => 'admin_post_nopriv_submit_subscription',
                'function' => 'submit_subscription'
            ],
            'd-on-submit-check' => [
                'hook' => 'admin_post_submit_subscription',
                'function' => 'submit_subscription'
            ]
        ];

        $this->filters = [];
    }

    // Action targets //
    function setup()
    {
        // Lets setup our autoloaders and build out our core plugin needs //
        $this->autoloader($this->auto_dirs);
    }

    public function add_shortcodes()
    {
        add_shortcode('chargify_product_form', array(&$this, 'chargify_form_shortcode'));
    }

    function add_admin_menu()
    {
        // Register our Dashboard area //
        $menu = [
            'primary' => [
                'page_title' => __('Chargify API Helper - Dashboard', 'd-text'),
                'menu_title' => 'Chargify Helper',
                'capability' => 'manage_options',
                'menu_slug' => 'chargify-api',
                'function' => 'view_dashboard',
                'icon_url' => '',
                'position' => 40,
                'subpages' => [
                    'settings' => [
                        'page_title' => __('Plugin Settings', 'd-text'),
                        'menu_title' => 'Settings',
                        'capability' => 'manage_options',
                        'menu_slug' => 'chargify-api-settings',
                        'function' => 'view_settings',
                        'position' => 9
                    ]
                ]
            ],
        ];

        // $subpages = ;
        // Lets add in a filter to allow us to append more sub pages via our modules //
        $this->register_settings($menu);
    }

    var $sub_data = [
        'subscription' => [
            'product_handle' => false,
            'customer_attributes' => [],
            'credit_card_attributes' => []
        ]
    ];

    private function get_opts()
    {
        $options = [];
        $options['env'] = $env = get_option('chargify_api_env');
        $options['key'] = get_option($env . '_api_key');
        $options['chargifyjs'] = get_option($env . '_chargify_key');
        $options['subdomain'] = get_option($env . '_api_subdomain');
        $options['thank-you'] = get_option('confirmation_page');

        return $options;
    }

    private function chargify($target, $data = null, $method = 'post')
    {

        $options = $this->get_opts();
        $apiUrl = "https://{$options['subdomain']}.chargify.com{$target}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_USERPWD, $options['key'] . ':x');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        if ($method == 'put') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        }
        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        }
        if ($method == 'delete') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $result = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (substr($httpCode, 0, 1) != 2) {
            $res = json_decode($result);
            $e = '';
            if (isset($res->errors[0]))
                return $res;
        }

        curl_close($ch);
        return json_decode($result);
    }

    public function submit_subscription()
    {
        // lets check if the nonce was submitted then we have our proper form //
        $nonce = $_REQUEST['d_chargify_nonce'];
        $options = $this->get_opts();
        $token = $_REQUEST['d_chargify_token'];
        if ($nonce) {
            $p = $this->validate_request(false);
            $query = $this->sub_data;

            if ($p['product'] && $slug = $p['product']) {
                // make sure we have a product selected //
                $query['subscription']['product_handle'] = $slug;
                $query['subscription']['customer_attributes'] = $customer = [
                    'first_name' => $p['first_name'],
                    'last_name' => $p['last_name'],
                    'email' => $p['email']
                ];

                $query['subscription']['credit_card_attributes'] = $credit_card = [
                    'chargify_token' => $p['d_chargify_token']
                ];

                // Lets add our coupon code to our order //
                if (isset($p['coupon']) && $p['coupon'] !== '') {
                    $query['subscription']['metafields'] = [
                        'coupon' => $p['coupon']
                    ];
                }
                $results = $this->chargify('/subscriptions.json', $query);

                if ($results && isset($results->subscription) && $results->subscription->state === 'active') {
                    // we have an active success //
                    // log it //
                    $logs = (get_option('d__chargify_logs') ? get_option('d__chargify_logs') : []);
                    $logs[time()] = [
                        'time' => time(),
                        'query' => $query,
                        'results' => [
                            'status' => $results->subscription->state,
                            'subscription_id' => $results->subscription->id,
                            'email' => $p['email']
                        ]
                    ];
                    ksort($logs);
                    update_option('d__chargify_logs', $logs);

                    // we now need to route user to the conf page //
                    if (isset($options['thank-you']) && $options['thank-you'] && $options['thank-you'] !== '') {
                        wp_safe_redirect($options['thank-you']);
                    }
                } else if (isset($results->errors)) {
                    // we need to let the user know that the submission failed and why //
                    update_option('d__last_errors', ['time' => time(), 'query' => $query, 'errors' => $results->errors]);
                    wp_safe_redirect($_SERVER['HTTP_REFERER'] . '?errors=' . implode(',', $results->errors), 301);
                }
            }
            // we now have our data and need to package it up for submission to a curl request //

        }
    }

    public function load_scripts()
    {
        global $d_chargify;
        $scripts = $this->frontend_scripts;
        $output = '';
        $script = '<script type="text/javascript" src="';
        $_script = '" defer></script>';
        if ($scripts && count($scripts) > 0) {
            foreach ($scripts as $script) {
                $file = $d_chargify->dirs['plugin'] . '/' . $d_chargify->dirs['scripts'] . '/' . $script;
                if (file_exists($file)) {
                    $output .= $script . $file . $_script;
                }
            }
        }

        return $output;
    }

    public function chargify_form_shortcode($atts = array())
    {
        global $d__core;
        // Enqueue the javascript handler for the form
        wp_enqueue_script('chargify-api-form-handler');

        // Lets add in the javascript directly to the template //
        $form = $this->load_scripts();

        // Extract the shortcode attributes passed and define defaults
        extract(shortcode_atts(array(
            'product' => 'all'
        ), $atts));

        // Grab the form template
        $form .= file_get_contents(plugin_dir_path(__FILE__) . '../templates/forms/product-form.php');


        // Create the options for the products dropdown
        $product_options = '';

        // Disable the form if a product was passed in the shortcode params
        if ($product != 'all') {
            $product_options = '<input type="hidden" name="product" value="' . $product . '" /><select id="product" class="cfy-input cfy-input--product" name="view_product" disabled>';
        } else {
            $product_options = '<select class="cfy-input cfy-input--product" id="product" name="product">';
        }

        // Pull all products listed in settings
        $products = get_option('chargify_api_products');
        foreach ($products as $_product) {
            if ($_product['id'] && $_product['id'] !== '') {
                // Add selected if a product has been passed in the shortcode params
                $selected = ($_product['id'] == $product) ? ' selected' : '';
                $product_options .= '<option value="' . $_product['id'] . '"' . $selected . '>' . $_product['name'] . '</option>';
            }
        }

        // Close up product options
        $product_options .= '</select>';

        // Add product options to form template
        $form = str_replace('[product_select]', $product_options, $form);

        // Add in the options for api keys and form confirmations
        $options = $this->get_opts();
        if ($options['chargifyjs']) {
            $form = str_replace('[api-key]', $options['chargifyjs'], $form);
        }

        if ($options['subdomain']) {
            $form = str_replace('[subdomain]', $options['subdomain'], $form);
        }

        if ($options['thank-you']) {
            $form = str_replace('[thank-you]', $options['thank-you'], $form);
        }

        if ($product && $product !== '') {
            $form = str_replace('[hide-product-select]', ' hidden', $form);
        }

        $nonce = $this->create_nonce();
        if ($nonce) {
            $form = str_replace('[nonce]', $nonce, $form);
        }
        $posturl = esc_url(admin_url('admin-post.php'));
        $form = str_replace('[admin-post]', $posturl, $form);

        // Populate arrays with the expiration months and years to add into the dropdowns for the Expiration Date
        $exp_month_options = '';
        $expiration_months = array(
            '01 - January',
            '02 - February',
            '03 - March',
            '04 - April',
            '05 - May',
            '06 - June',
            '07 - July',
            '08 - August',
            '09 - September',
            '10 - October',
            '11 - November',
            '12 - December'
        );

        foreach ($expiration_months as $month) {
            $exp_month_options .= '<option value="' . substr($month, 0, 2) . '">' . $month . '</option>';
        }

        // Add expiration month options to form template
        $form = str_replace('[exp_month_options]', $exp_month_options, $form);

        // Get Current Year
        $current_year = date("Y");
        $expiration_years = range($current_year, $current_year + 50);
        $exp_year_options = '';
        foreach ($expiration_years as $year) {
            $exp_year_options .= '<option value="' . $year . '">' . $year . '</option>';
        }

        // Add expiration year options to form template
        $form = str_replace('[exp_year_options]', $exp_year_options, $form);

        return $form;
    }

    /**
     * Enqueue scripts needed for Meta Box
     */
    function enqueue_scripts($hook)
    {
        wp_register_script('chargify-api-form-handler', plugin_dir_url(__FILE__) . '../assets/js/fe/main.js', array(), '1.0.0', true);
    }

    /**
     * Initialize the Settings Page sections and fields
     */
    function settings_init()
    {
        // Register the settings for "settings" page
        register_setting('chargify-api-settings', 'chargify_api_env');
        register_setting('chargify-api-settings', 'staging_api_key');
        register_setting('chargify-api-settings', 'staging_chargify_key');
        register_setting('chargify-api-settings', 'staging_api_subdomain');
        register_setting('chargify-api-settings', 'prod_api_key');
        register_setting('chargify-api-settings', 'prod_chargify_key');
        register_setting('chargify-api-settings', 'prod_api_subdomain');
        register_setting('chargify-api-settings', 'confirmation_page');
        register_setting('chargify-api-settings', 'chargify_api_products');

        // Register an Environment section in the Settings page
        add_settings_section(
            'environment_section',
            __('Environment', 'd-text'),
            array(&$this, 'environment_section_callback'),
            'chargify-api-settings'
        );

        // Register an active environment radio switch for staging and production
        add_settings_field(
            'chargify_api_env',
            __('Active Environment', 'd-text'),
            array(&$this, 'chargify_api_env_input_cb'),
            'chargify-api-settings',
            'environment_section',
            array(
                'label_for'         => 'chargify_api_env',
            )
        );

        // Register an API section in the Settings page
        add_settings_section(
            'chargify_api_section',
            __('Chargify API Keys', 'd-text'),
            array(&$this, 'chargify_api_section_callback'),
            'chargify-api-settings'
        );

        // Register both a staging and production API field in the API section
        add_settings_field(
            'staging_api_key',
            __('Staging API Key', 'd-text'),
            array(&$this, 'text_input_cb'),
            'chargify-api-settings',
            'chargify_api_section',
            array(
                'label_for'         => 'staging_api_key',
            )
        );
        add_settings_field(
            'staging_chargify_key',
            __('Staging Chargify Global Key', 'd-text'),
            array(&$this, 'text_input_cb'),
            'chargify-api-settings',
            'chargify_api_section',
            array(
                'label_for'         => 'staging_chargify_key',
            )
        );
        add_settings_field(
            'staging_api_subdomain',
            __('Staging API Subdomain', 'd-text'),
            array(&$this, 'text_input_cb'),
            'chargify-api-settings',
            'chargify_api_section',
            array(
                'label_for'         => 'staging_api_subdomain',
            )
        );
        add_settings_field(
            'prod_api_key',
            __('Production API Key', 'd-text'),
            array(&$this, 'text_input_cb'),
            'chargify-api-settings',
            'chargify_api_section',
            array(
                'label_for'         => 'prod_api_key',
            )
        );
        add_settings_field(
            'prod_chargify_key',
            __('Production API Key', 'd-text'),
            array(&$this, 'text_input_cb'),
            'chargify-api-settings',
            'chargify_api_section',
            array(
                'label_for'         => 'prod_chargify_key',
            )
        );
        add_settings_field(
            'prod_api_subdomain',
            __('Production API Subdomain', 'd-text'),
            array(&$this, 'text_input_cb'),
            'chargify-api-settings',
            'chargify_api_section',
            array(
                'label_for'         => 'prod_api_subdomain',
            )
        );

        // Register a Confirmation Section in the Settings page
        add_settings_section(
            'confirmation_section',
            __('Form Confirmation', 'd-text'),
            array(&$this, 'confirmation_section_callback'),
            'chargify-api-settings'
        );

        // Register both a staging and production API field in the API section
        add_settings_field(
            'confirmation_page',
            __('Confirmation Page', 'd-text'),
            array(&$this, 'text_input_cb'),
            'chargify-api-settings',
            'confirmation_section',
            array(
                'label_for'         => 'confirmation_page',
            )
        );

        // Register a Products section in the Settings page
        add_settings_section(
            'products_section',
            __('Products', 'd-text'),
            array(&$this, 'products_section_callback'),
            'chargify-api-settings'
        );

        // Register a products field to start the array of products ids and names
        add_settings_field(
            'chargify_api_products',
            __('Product Slug | Name', 'd-text'),
            array(&$this, 'chargify_api_products_input_cb'),
            'chargify-api-settings',
            'products_section',
            array(
                'label_for'         => 'chargify_api_products',
            )
        );
    }

    /**
     * Environment Section callback function
     *
     * @param array $args  The settings array, defining title, id, callback
     */
    public function environment_section_callback($args)
    {
        // Output content below the Environment Section title here
        echo '<p>Switch between staging and production environments.</p>';
    }

    /**
     * API Section callback function
     *
     * @param array $args  The settings array, defining title, id, callback
     */
    public function chargify_api_section_callback($args)
    {
        // Output content below the API Section title here
    }

    /**
     * Confirmation Section callback function
     *
     * @param array $args  The settings array, defining title, id, callback
     */
    public function confirmation_section_callback($args)
    {
        // Output content below the Confirmation Section title here
        echo '<p>Enter the page to load after a successful submission.</p>';
    }

    /**
     * Products Section callback function
     *
     * @param array $args  The settings array, defining title, id, callback
     */
    public function products_section_callback($args)
    {
        // Output content below the Confirmation Section title here
        echo '<p>Enter the product IDs or names below. Click "Add Product" to create another input.</p>';
    }

    /**
     * Environment input field callback function
     *
     * @param array $args
     */
    public function chargify_api_env_input_cb($args)
    {
        // Get the value of the setting we've registered with register_setting()
        $label_for = $args['label_for'];
        $key = get_option($label_for);
        echo '<div>';
        echo '<input type="radio" id="' . $label_for . '" name="' . $label_for . '" value="staging"' . ($key == 'staging' ? ' checked' : '') . '>';
        echo '<label for="' . $label_for . '">Staging</label>';
        echo '</div>';
        echo '<div>';
        echo '<input type="radio" id="' . $label_for . '" name="' . $label_for . '" value="production"' . ($key == 'production' ? ' checked' : '') . '>';
        echo '<label for="' . $label_for . '">Production</label>';
        echo '</div>';
    }

    private function form__input_helper($label_for, $product = '', $product_name = '')
    {

        return '<input type="text" id="" name="' . $label_for . '[id]" placeholder="Product ID" value="' . $product . '"><input type="text" id="' . $label_for . '[name]" name="' . $label_for . '[name]" placeholder="Product Name" value="' . $product_name . '">';
    }

    /**
     * Products input field callback function
     *
     * @param array $args
     */
    public function chargify_api_products_input_cb($args)
    {
        // Get the value of the setting we've registered with register_setting()
        $count = 0;
        $products = array();
        $label_for = $args['label_for'];

        echo '<ul class="product_list">' . "\n";
        if (get_option($label_for) !== FALSE) {
            $products = get_option($label_for);

            foreach ($products as $key => $product) {
                if ($product['id'] && $product['id'] !== '') {
                    $l = $label_for . '[' . $key . ']';
                    echo '<li>' . $this->form__input_helper($l, $product['id'], $product['name']) . '</li>' . "\n";
                    $count++;
                }
            }
        } else {
            $l = $label_for . '[' . $count . ']';
            echo '<li>' . $this->form__input_helper($l) . '</li>' . "\n";
            $count++;
        }
        echo '</ul>' . "\n";

        echo '<a class="add_product">' . __('Add Product', 'd-text') . '</a>' . "\n";
        echo '<script type="text/javascript">' . "\n";
        echo 'jQuery(document).ready(function() {' . "\n";
        echo 'var count = ' . $count . ';' . "\n";
        echo 'jQuery(".add_product").click(function() {' . "\n";
        echo 'count = count + 1;' . "\n";
        echo 'jQuery(".product_list").append(\'<li>' . $this->form__input_helper($label_for . '[' . $count . ']') . '</li>\');' . "\n";
        echo 'return false;' . "\n";
        echo '});' . "\n";
        echo '});' . "\n";
        echo '</script>' . "\n";
    }

    /**
     * Text input field callback function
     *
     * @param array $args
     */
    public function text_input_cb($args)
    {
        // Get the value of the setting we've registered with register_setting()
        $label_for = $args['label_for'];
        $key = get_option($label_for);
        echo '<input type="text" id="' . $label_for . '" name="' . $label_for . '" value="' . $key . '">';
    }

    function view_dashboard()
    {
        echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>Chargify API Helper Dashboard</h2>';
        echo '<p>Please enter all information and add all products in settings and submit before trying to use the shortcode.<p>';
        echo '<p>The shortcode for a product form is <strong>[chargify_product_form product=x]</strong> where x is the product ID or name you added in settings. If you remove the product parameter from the shortcode, it will display all products listed in settings.<p>';
        echo '</div>';
    }

    function view_settings()
    {
        // check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // add error/update messages
        if (isset($_GET['settings-updated'])) {
            add_settings_error('chargify-api-settings-messages', 'settings_message', __('Settings Saved', 'chargify-api-settings'), 'updated');
        }

        $hook_suffix = [];

        // do_action('add_meta_boxes');

        // show error/update messages
        settings_errors('chargify-api-settings-messages');

        // We need to load in the dashboard template //
        echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h1>' . esc_html(get_admin_page_title()) . '</h1>';
        echo '<form action="options.php" method="post">';
        settings_fields('chargify-api-settings');
        do_settings_sections('chargify-api-settings');
        submit_button('Save Settings');
        echo '</form>';
        echo '</div>';
    }
}

if (!function_exists('d__init_core')) {
    function d__init_core()
    {
        global $d__core;

        return (!isset($d__core) ? new d_core() : $d__core);
    }
}

$d__core = d__init_core();
$d__core->setup();
