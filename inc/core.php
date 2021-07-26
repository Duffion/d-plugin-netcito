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
        'modules', 'api', 'vendors'
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
            'd-register-meta-box' => [
                'hook' => 'add_meta_boxes',
                'function' => 'add_dynamic_product_fields'
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
        add_shortcode( 'chargify_product_form', array( &$this, 'chargify_form_shortcode' ) );
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

    public static function chargify_form_shortcode( $atts = array() )
    {
        // Enqueue the javascript handler for the form
        wp_enqueue_script( 'chargify-api-form-handler' );

        // Grab the form template
        $form = file_get_contents( plugin_dir_path( __FILE__ ) . '../templates/forms/product-form.php' );

        // Extract the shortcode attributes passed and define defaults
        extract(shortcode_atts(array(
            'product' => 'all'
        ), $atts));

        // Create the options for the products dropdown
        $product_options = '';

        // Disable the form if a product was passed in the shortcode params
        if ( $product != 'all' ) {
            $product_options = '<select id="product" name="product" disabled>';
        } else {
            $product_options = '<select id="product" name="product">';
        }

        // Pull all products listed in settings
        $products = get_option( 'chargify_api_products' );
    
        foreach ( $products AS $product_id ) {
            // Add selected if a product has been passed in the shortcode params
            $selected = ( $product_id == $product ) ? ' selected' : '';
            $product_options .= '<option value="' . $product_id . '"' . $selected . '>' . $product_id . '</option>';
        }

        // Close up product options
        $product_options .= '</select>';

        // Add product options to form template
        $form = str_replace( '[product_select]', $product_options, $form );


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

        foreach( $expiration_months AS $month ) {
            $exp_month_options .= '<option value="' . substr( $month, 0, 2 ) . '">' . $month . '</option>';
        }

        // Add expiration month options to form template
        $form = str_replace( '[exp_month_options]', $exp_month_options, $form );

        // Get Current Year
        $current_year = date("Y");
        $expiration_years = range( $current_year, $current_year + 50 );

        foreach( $expiration_years AS $year ) {
            $exp_year_options .= '<option value="' . $year . '">' . $year . '</option>';
        }

        // Add expiration year options to form template
        $form = str_replace( '[exp_year_options]', $exp_year_options, $form );

        return $form;
    }

    /**
     * Enqueue scripts needed for Meta Box
     */
    function enqueue_scripts( $hook )
    {
        wp_register_script( 'chargify-api-form-handler', plugin_dir_url( __FILE__ ) . '../assets/js/form.js', array(), '1.0.0', true );
    }

    /**
     * Initialize the Settings Page sections and fields
     */
    function settings_init()
    {
        // Register the settings for "settings" page
        register_setting( 'chargify-api-settings', 'chargify_api_env' );
        register_setting( 'chargify-api-settings', 'staging_api_key' );
        register_setting( 'chargify-api-settings', 'prod_api_key' );
        register_setting( 'chargify-api-settings', 'confirmation_page' );
        register_setting( 'chargify-api-settings', 'chargify_api_products' );

        // Register an Environment section in the Settings page
        add_settings_section(
            'environment_section',
            __( 'Environment', 'd-text' ), 
            array( &$this, 'environment_section_callback' ),
            'chargify-api-settings'
        );

        // Register an active environment radio switch for staging and production
        add_settings_field(
            'chargify_api_env',
            __( 'Active Environment', 'd-text' ),
            array( &$this, 'chargify_api_env_input_cb' ),
            'chargify-api-settings',
            'environment_section',
            array(
                'label_for'         => 'chargify_api_env',
            )
        );

        // Register an API section in the Settings page
        add_settings_section(
            'chargify_api_section',
            __( 'Chargify API Keys', 'd-text' ), 
            array( &$this, 'chargify_api_section_callback' ),
            'chargify-api-settings'
        );

        // Register both a staging and production API field in the API section
        add_settings_field(
            'staging_api_key',
            __( 'Staging API Key', 'd-text' ),
            array( &$this, 'text_input_cb' ),
            'chargify-api-settings',
            'chargify_api_section',
            array(
                'label_for'         => 'staging_api_key',
            )
        );
        add_settings_field(
            'prod_api_key',
            __( 'Production API Key', 'd-text' ),
            array( &$this, 'text_input_cb' ),
            'chargify-api-settings',
            'chargify_api_section',
            array(
                'label_for'         => 'prod_api_key',
            )
        );

        // Register a Confirmation Section in the Settings page
        add_settings_section(
            'confirmation_section',
            __( 'Form Confirmation', 'd-text' ), 
            array( &$this, 'confirmation_section_callback' ),
            'chargify-api-settings'
        );

        // Register both a staging and production API field in the API section
        add_settings_field(
            'confirmation_page',
            __( 'Confirmation Page', 'd-text' ),
            array( &$this, 'text_input_cb' ),
            'chargify-api-settings',
            'confirmation_section',
            array(
                'label_for'         => 'confirmation_page',
            )
        );

        // Register a Products section in the Settings page
        add_settings_section(
            'products_section',
            __( 'Products', 'd-text' ), 
            array( &$this, 'products_section_callback' ),
            'chargify-api-settings'
        );

        // Register a products field to start the array of products ids and names
        add_settings_field(
            'chargify_api_products',
            __( 'Product IDs/Name', 'd-text' ),
            array( &$this, 'chargify_api_products_input_cb' ),
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
    public function environment_section_callback( $args )
    {
        // Output content below the Environment Section title here
        echo '<p>Switch between staging and production environments.</p>';
    }

    /**
     * API Section callback function
     * 
     * @param array $args  The settings array, defining title, id, callback
     */
    public function chargify_api_section_callback( $args )
    {
        // Output content below the API Section title here
    }

    /**
     * Confirmation Section callback function
     * 
     * @param array $args  The settings array, defining title, id, callback
     */
    public function confirmation_section_callback( $args )
    {
        // Output content below the Confirmation Section title here
        echo '<p>Enter the page to load after a successful submission.</p>';
    }

    /**
     * Products Section callback function
     * 
     * @param array $args  The settings array, defining title, id, callback
     */
    public function products_section_callback( $args )
    {
        // Output content below the Confirmation Section title here
        echo '<p>Enter the product IDs or names below. Click "Add Product" to create another input.</p>';
    }

    /**
     * Environment input field callback function
     * 
     * @param array $args
     */
    public function chargify_api_env_input_cb( $args )
    {
        // Get the value of the setting we've registered with register_setting()
        $label_for = $args['label_for'];
        $key = get_option( $label_for );
        echo '<div>';
            echo '<input type="radio" id="' . $label_for . '" name="' . $label_for . '" value="staging"' . ($key == 'staging' ? ' checked' : '') . '>';
            echo '<label for="' . $label_for . '">Staging</label>';
        echo '</div>';
        echo '<div>';
            echo '<input type="radio" id="' . $label_for . '" name="' . $label_for . '" value="production"' . ($key == 'production' ? ' checked' : '') . '>';
            echo '<label for="' . $label_for . '">Production</label>';
        echo '</div>';
    }

    /**
     * Products input field callback function
     * 
     * @param array $args
     */
    public function chargify_api_products_input_cb( $args )
    {
        // Get the value of the setting we've registered with register_setting()
        $count = 0;
        $products = array();
        $label_for = $args['label_for'];

        echo '<ul class="product_list">' . "\n";
        if ( get_option( $label_for ) !== FALSE ) {
            $products = get_option( $label_for );
       
            foreach ( $products AS $key => $product ) {
                echo '<li><input type="text" id="' . $label_for . '[' . $key . ']" name="' . $label_for . '[' . $key . ']" value="' . $product . '"></li>' . "\n";
                $count = $key;
            }
        } else {
            echo '<li><input type="text" id="' . $label_for . '[' . $count . ']" name="' . $label_for . '[' . $count . ']" value=""></li>' . "\n";
            $count++;
        }
        echo '</ul>' . "\n";

        echo '<a class="add_product">' . __( 'Add Product', 'd-text' ) . '</a>' . "\n";
        echo '<script type="text/javascript">' . "\n";
            echo 'jQuery(document).ready(function() {' . "\n";
                echo 'var count = ' . $count . ';' . "\n";
                echo 'jQuery(".add_product").click(function() {' . "\n";
                    echo 'console.log("Hello World!");' . "\n";
                    echo 'count = count + 1;' . "\n";
                    echo 'jQuery(".product_list").append(\'<li><input type="text" id="' . $label_for . '[\' + count + \']" name="' . $label_for . '[\' + count + \']" value=""></li>\');' . "\n";
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
    public function text_input_cb( $args )
    {
        // Get the value of the setting we've registered with register_setting()
        $label_for = $args['label_for'];
        $key = get_option( $label_for );
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
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // add error/update messages
        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error( 'chargify-api-settings-messages', 'settings_message', __( 'Settings Saved', 'chargify-api-settings' ), 'updated' );
        }

        do_action( 'add_meta_boxes', $hook_suffix );

        // show error/update messages
        settings_errors( 'chargify-api-settings-messages' );

        // We need to load in the dashboard template //
        echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
        echo '<form action="options.php" method="post">';
            settings_fields( 'chargify-api-settings' );
            do_settings_sections( 'chargify-api-settings' );
            submit_button( 'Save Settings' );
        echo '</form>';
        echo '</div>';
    }
}

if (!function_exists('d__init_core'))
{
    function d__init_core()
    {
        global $d__core;

        return (!isset($d__core) ? new d_core() : $d__core);
    }
}

$d__core = d__init_core();
$d__core->setup();
