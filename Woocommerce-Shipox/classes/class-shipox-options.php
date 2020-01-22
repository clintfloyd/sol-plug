<?php
/**
 * Created by Shipox.
 * User: Shipox
 * Date: 11/8/2017
 * Time: 2:41 PM
 */

class Shipox_Options
{
    /**
     * Wing_Options constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init'));
    }

    /**
     *   Add menu to Admin Menu Container
     */
    public function add_admin_menu()
    {
        add_menu_page('Shipox', 'Shipox', 'manage_options', 'wing', array($this, 'render_options'), plugins_url('/shipox/assets/images/logo.png'));
    }


    /**
     *   Init Options
     */
    public function init()
    {
        $this->init_service_configuration_fields();
        $this->init_merchant_configuration_fields();
        $this->init_merchant_address_fields();
        $this->init_wing_order_configuration_fields();
    }

    /**
     *  Init Service Configuration Fields
     */
    public function init_service_configuration_fields() {
        register_setting(
            'service_config_section', // Option group
            'wing_service_config'
        );

        add_settings_section(
            'service_config_section_tab',
            __('Service Configuration', 'wing'),
            array($this, 'service_config_section_callback'),
            'wingServiceConfig'
        );

        add_settings_field(
            'instance',
            __('Shipox Instance', 'wing'),
            array($this, 'shipox_instance_render'),
            'wingServiceConfig',
            'service_config_section_tab'
        );

        add_settings_field(
            'test_mode',
            __('Debug Mode', 'wing'),
            array($this, 'test_mode_render'),
            'wingServiceConfig',
            'service_config_section_tab'
        );

        add_settings_field(
            'auto_push',
            __('Auto Push', 'wing'),
            array($this, 'auto_push_render'),
            'wingServiceConfig',
            'service_config_section_tab'
        );
    }

    /**
     *   Merchant Configuration Fields
     */
    public function init_merchant_configuration_fields() {
        register_setting(
            'merchant_config_section', // Option group
            'wing_merchant_config'
        );

        add_settings_section(
            'merchant_config_section_tab',
            __('Service Configuration', 'wing'),
            array($this, 'merchant_config_section_callback'),
            'page_merchant_config'
        );

        add_settings_field(
            'merchant_username',
            __('Merchant Email', 'wing'),
            array($this, 'merchant_username_render'),
            'page_merchant_config',
            'merchant_config_section_tab'
        );

        add_settings_field(
            'merchant_password',
            __('Merchant Password', 'wing'),
            array($this, 'merchant_password_render'),
            'page_merchant_config',
            'merchant_config_section_tab'
        );

        add_settings_field(
            'merchant_get_token',
            '',
            array($this, 'merchant_get_token_render'),
            'page_merchant_config',
            'merchant_config_section_tab'
        );

        add_settings_field(
            'merchant_token',
            '',
            array($this, 'merchant_token_render'),
            'page_merchant_config',
            'merchant_config_section_tab'
        );
    }


    /**
     *  Merchant Address Fields
     */
    public function init_merchant_address_fields() {
        register_setting(
            'merchant_address_section', // Option group
            'wing_merchant_address'
        );

        add_settings_section(
            'merchant_address_section_tab',
            __('Merchant Address', 'wing'),
            array($this, 'merchant_address_section_callback'),
            'page_merchant_address'
        );

        add_settings_field(
            'merchant_company_name',
            __('Company Name', 'wing'),
            array($this, 'merchant_company_name_render'),
            'page_merchant_address',
            'merchant_address_section_tab'
        );

        add_settings_field(
            'merchant_contact_name',
            __('Contact Name', 'wing'),
            array($this, 'merchant_contact_name_render'),
            'page_merchant_address',
            'merchant_address_section_tab'
        );

        add_settings_field(
            'merchant_contact_email',
            __('Contact Email', 'wing'),
            array($this, 'merchant_contact_email_render'),
            'page_merchant_address',
            'merchant_address_section_tab'
        );

        add_settings_field(
            'merchant_city',
            __('City', 'wing'),
            array($this, 'merchant_city_render'),
            'page_merchant_address',
            'merchant_address_section_tab'
        );

        add_settings_field(
            'merchant_postcode',
            __('PostCode', 'wing'),
            array($this, 'merchant_postcode_render'),
            'page_merchant_address',
            'merchant_address_section_tab'
        );

        add_settings_field(
            'merchant_street',
            __('Street', 'wing'),
            array($this, 'merchant_street_render'),
            'page_merchant_address',
            'merchant_address_section_tab'
        );

        add_settings_field(
            'wing_merchant_address',
            __('Address', 'wing'),
            array($this, 'merchant_address_render'),
            'page_merchant_address',
            'merchant_address_section_tab'
        );

        add_settings_field(
            'merchant_phone',
            __('Phone', 'wing'),
            array($this, 'merchant_phone_render'),
            'page_merchant_address',
            'merchant_address_section_tab'
        );

        add_settings_field(
            'merchant_lat_long',
            __('Latitude & Longitude', 'wing'),
            array($this, 'merchant_lat_long_render'),
            'page_merchant_address',
            'merchant_address_section_tab'
        );

        add_settings_field(
            'merchant_details',
            __('Details', 'wing'),
            array($this, 'merchant_details_render'),
            'page_merchant_address',
            'merchant_address_section_tab'
        );
    }


    /**
     *  Init Configuration Fields
     */
    public function init_wing_order_configuration_fields() {
        register_setting(
            'wing_order_config_section', // Option group
            'wing_order_config'
        );

        add_settings_section(
            'wing_order_config_section_tab',
            __('Order Configuration', 'wing'),
            array($this, 'wing_order_config_section_callback'),
            'page_wing_order_config'
        );

        add_settings_field(
            'order_international_availability',
            __('International Order Availability', 'wing'),
            array($this, 'order_international_availability_render'),
            'page_wing_order_config',
            'wing_order_config_section_tab'
        );

        add_settings_field(
            'order_default_weight',
            __('Default Weight', 'wing'),
            array($this, 'order_default_weight_render'),
            'page_wing_order_config',
            'wing_order_config_section_tab'
        );

        add_settings_field(
            'order_default_courier_type',
            __('Default Courier Type', 'wing'),
            array($this, 'order_default_courier_type_render'),
            'page_wing_order_config',
            'wing_order_config_section_tab'
        );

        add_settings_field(
            'order_default_payment_option',
            __('Default Payment Option', 'wing'),
            array($this, 'order_default_payment_option_render'),
            'page_wing_order_config',
            'wing_order_config_section_tab'
        );
    }

    /**
     *   Service Section
     */
    public function service_config_section_callback() {
        echo __('', 'wing');
    }

    /**
     *   Service Test Mode Render
     */
    function shipox_instance_render()
    {
        $options = get_option('wing_service_config');
        ?>
        <select name='wing_service_config[instance]' class="wing-input-class" title="Shipox Instance">
            <option value='1' <?php selected($options['instance'], 1); ?>>Instance 1</option>
            <option value='2' <?php selected($options['instance'], 2); ?>>Instance 2</option>
        </select>
        <?php

    }

    /**
     *   Service Test Mode Render
     */
    function test_mode_render()
    {
        $options = get_option('wing_service_config');
        ?>
        <select name='wing_service_config[test_mode]' class="wing-input-class" title="Debug Mode">
            <option value='1' <?php selected($options['test_mode'], 1); ?>>Yes</option>
            <option value='2' <?php selected($options['test_mode'], 2); ?>>No</option>
        </select>
        <?php

    }

    /**
     *  Service Google Key Render
     */
    function auto_push_render()
    {
        $options = get_option('wing_service_config');
        $order_statuses = wc_get_order_statuses();
        ?>
        <select name='wing_service_config[auto_push]' class="wing-input-class" title="Auto Push">
            <option value="0" <?php selected($options['auto_push'], 0); ?>>Off</option>
            <?php
            foreach ($order_statuses as $key => $status) {
                echo "<option value='$key' ".selected($options['auto_push'], $key).">$status</option>";
            }
            ?>
        </select>
        <p><strong>INFO:</strong> You can select auto push trigger function on which order status. Off - means auto push is disabled</p>
        <?php
    }



    /**
     *   Merchant Section
     */
    public function merchant_config_section_callback() {
        echo __('', 'wing');
    }

    /**
     *  Merchant UserName Field
     */
    public function merchant_username_render() {
        $options = get_option('wing_merchant_config');
        ?>
        <input id='shipox_merchant_username' class="wing-input-class" type='text' required title="Merchant Username"
               name='wing_merchant_config[merchant_username]' value='<?php echo $options['merchant_username']; ?>' />
        <?php
    }

    /**
     *  Merchant Password Render
     */
    function merchant_password_render()
    {
        $options = get_option('wing_merchant_config');
        ?>
        <input id='shipox_merchant_password' class="wing-input-class" type='password' required title="Merchant Password"
               name='wing_merchant_config[merchant_password]'
               value='<?php echo $options['merchant_password']; ?>' />
        <?php
    }

    /**
     *  Merchant Get Token Render
     */
    function merchant_get_token_render()
    {
        ?>
        <input type="hidden" id="shipoxTokenNonce" value="<?php echo wp_create_nonce( "shipox-wp-woocommerse-plugin" ); ?>">
        <button id="shipoxGetToken" class="button button-primary">Get Token</button>
        <?php
    }

    /**
     *  Merchant Token Hidden Field
     */
    function merchant_token_render()
    {
        $options = get_option('wing_merchant_config');
        ?>
        <textarea id="woocommerce_shipox_token" title="Merchant token" style="visibility: hidden"
                  name="wing_merchant_config[merchant_token]"><?php echo $options['merchant_token']; ?></textarea>
        <?php
    }


    /**
     *  Merchant Address Section Callback
     */
    public function merchant_address_section_callback() {
        echo __('', 'wing');
    }

    /**
     *  Merchant Company Name Render
     */
    function merchant_company_name_render()
    {
        $options = get_option('wing_merchant_address');
        ?>
        <input id='wing_woocommerce_company_name' class="wing-input-class" type='text' title="Merchant Company Name"
               name='wing_merchant_address[merchant_company_name]' required value='<?php echo $options['merchant_company_name']; ?>' />
        <?php
    }

    /**
     *  Merchant Contact Name Render
     */
    function merchant_contact_name_render()
    {
        $options = get_option('wing_merchant_address');
        ?>
        <input id='wing_woocommerce_contact_name' class="wing-input-class" type='text' title="Merchant Contact Name"
               name='wing_merchant_address[merchant_contact_name]' required value='<?php echo $options['merchant_contact_name']; ?>' />
        <?php
    }

    /**
     *  Merchant Contact Email Render
     */
    function merchant_contact_email_render()
    {
        $options = get_option('wing_merchant_address');
        ?>
        <input id='wing_woocommerce_contact_name' class="wing-input-class" type='text' title="Merchant Email"
               name='wing_merchant_address[merchant_contact_email]' required value='<?php echo $options['merchant_contact_email']; ?>' />
        <?php
    }

    /**
     *  Merchant City Render
     */
    function merchant_city_render()
    {
        $options = get_option('wing_merchant_address');
        ?>
        <input id='wing_woocommerce_city' class="wing-input-class" type='text' title="Merchant City"
               name='wing_merchant_address[merchant_city]' required value='<?php echo $options['merchant_city']; ?>' />
        <?php
    }

    /**
     *  Merchant Postcode Render
     */
    function merchant_postcode_render()
    {
        $options = get_option('wing_merchant_address');
        ?>
        <input id='wing_woocommerce_postcode' class="wing-input-class" type='text' title="Merchant Postcode"
               name='wing_merchant_address[merchant_postcode]' value='<?php echo $options['merchant_postcode']; ?>' />
        <?php
    }

    /**
     *  Merchant Street Render
     */
    function merchant_street_render()
    {
        $options = get_option('wing_merchant_address');
        ?>
        <input id='wing_woocommerce_street' class="wing-input-class" type='text' title="Merchant Street"
               name='wing_merchant_address[merchant_street]' value='<?php echo $options['merchant_street']; ?>' />
        <?php
    }

    /**
     *  Merchant Address Render
     */
    function merchant_address_render()
    {
        $options = get_option('wing_merchant_address');
        ?>
        <input id='wing_woocommerce_address' class="wing-input-class" type='text' title="Merchant Address"
               name='wing_merchant_address[merchant_address]' required value='<?php echo $options['merchant_address']; ?>' />
        <?php
    }

    /**
     *  Merchant Phone Render
     */
    function merchant_phone_render()
    {
        $options = get_option('wing_merchant_address');
        ?>
        <input id='wing_woocommerce_phone' class="wing-input-class" type='text'  title="Merchant Phone Number"
               name='wing_merchant_address[merchant_phone]' required value='<?php echo $options['merchant_phone']; ?>' />
        <?php
    }

    /**
     *  Merchant Latitude & Longitude Render
     */
    function merchant_lat_long_render()
    {
        $options = get_option('wing_merchant_address');
        ?>
        <input id='wing_woocommerce_lat_long' class="wing-input-class" type='text' title="Merchant Latitude & Longitude"
               name='wing_merchant_address[merchant_lat_long]' required value='<?php echo $options['merchant_lat_long']; ?>' />
        <p><strong>Important:</strong> Merchant Latitude & Longitude is field is required. Latitude & Longitude field should include only numbers and separatd with comma (lat,lon)</p>
        <?php
    }

    /**
     *  Merchant Details Render
     */
    function merchant_details_render()
    {
        $options = get_option('wing_merchant_address');
        ?>
        <textarea id='wing_woocommerce_details' class="wing-input-class" cols="4" rows="8" title="Merchant Details"
                  name='wing_merchant_address[merchant_details]'><?php echo $options['merchant_details']; ?></textarea>
        <?php
    }


    /**
     *   Order Configuration Callback
     */
    public function wing_order_config_section_callback() {
        echo __('', 'wing');
    }


    /**
     *  International Order Availability
     */
    public function order_international_availability_render() {
        $options = get_option('wing_order_config');
        ?>
        <select name='wing_order_config[order_international_availability]' class="wing-input-class"  title="Default International Availability">
            <option value='0' <?php selected($options['order_international_availability'], 0); ?>>Not available</option>
            <option value='1' <?php selected($options['order_international_availability'], 1); ?>>Available</option>
        </select>
        <?php
    }
    /**
     *  Default Weight
     */
    public function order_default_weight_render()
    {
        $options = get_option('wing_order_config');
        ?>
        <select name='wing_order_config[order_default_weight]' class="wing-input-class"  title="Default Weight">
            <?php
            $items = shipox()->wing['menu-type']->toOptionArray();
            foreach ($items as $item) {
                echo '<option value="' . $item['value'] . '" ' . selected($options['order_default_weight'] , $item['value']) . '>' . $item['label'] . '</option>';
            }
            ?>
        </select>
        <?php
    }

    /**
     *  Default Courier Type
     */
    public function order_default_courier_type_render()
    {
        $options = get_option('wing_order_config');

        ?>
        <select name=wing_order_config[order_default_courier_type][]' class="wing-input-class"  title="Default Courier Type" multiple>
            <?php
            $items = shipox()->wing['courier-type']->toOptionArray();
            foreach ($items as $item) {
                $isSelected = in_array($item['value'], $options['order_default_courier_type']);
                echo '<option value="' . $item['value'] . '" ' . selected($isSelected) . '>' . $item['label'] . '</option>';
            }
            ?>
        </select>
        <?php
    }

    /**
     *  Default Payment Type
     */
    public function order_default_payment_option_render()
    {
        $options = get_option('wing_order_config');
        ?>
        <select name='wing_order_config[order_default_payment_option]' class="wing-input-class" title="Default Payment Option">
            <?php
            $items = shipox()->wing['payment-type']->toOptionArray();
            foreach ($items as $item) {
                echo '<option value="' . $item['value'] . '" ' . selected($options['order_default_payment_option'] , $item['value']) . '>' . $item['label'] . '</option>';
            }
            ?>
        </select>
        <?php
    }

    /**
     *  Rendering Options Fields
     */
    public function render_options() {
        if (isset($_GET['tab'])) {
            $active_tab = $_GET['tab'];
        } else {
            $active_tab = 'service_config';
        }
        ?>
        <form action='options.php' method='post'>
            <h1><?php __('Shipox Merchant Settings')?></h1>
            <hr/>
            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo SHIPOX_SLUG; ?>&tab=service_config"
                   class="nav-tab <?php echo $active_tab == 'service_config' ? 'nav-tab-active' : ''; ?>">1. Service
                    Configuration</a>
                <a href="?page=<?php echo SHIPOX_SLUG; ?>&tab=merchant_info"
                   class="nav-tab <?php echo $active_tab == 'merchant_info' ? 'nav-tab-active' : ''; ?>">2. Merchant
                    Credentials</a>
                <a href="?page=<?php echo SHIPOX_SLUG; ?>&tab=merchant_address"
                   class="nav-tab <?php echo $active_tab == 'merchant_address' ? 'nav-tab-active' : ''; ?>">3. Merchant Address
                    Details</a>
                <a href="?page=<?php echo SHIPOX_SLUG; ?>&tab=order_settings"
                   class="nav-tab <?php echo $active_tab == 'order_settings' ? 'nav-tab-active' : ''; ?>">4. Order Settings
                </a>
            </h2>
            <?php
            if ($active_tab == 'service_config') {
                settings_fields( 'service_config_section' );
                do_settings_sections( 'wingServiceConfig' );
            } else if ($active_tab == 'merchant_info') {
                settings_fields('merchant_config_section');
                do_settings_sections('page_merchant_config');
            } else if ($active_tab == 'merchant_address') {
                settings_fields('merchant_address_section');
                do_settings_sections('page_merchant_address');
            } else if ($active_tab == 'order_settings') {
                settings_fields('wing_order_config_section');
                do_settings_sections('page_wing_order_config');
            }
            submit_button();
            ?>
        </form>
        <?php
    }
}

new Shipox_Options();