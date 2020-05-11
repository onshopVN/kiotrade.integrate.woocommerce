<?php
defined( 'ABSPATH' ) || exit;

class Saleshub_WC_Integration extends WC_Integration
{
    public function __construct()
    {
        global $woocommerce;
        $this->id = 'saleshub-integration';
        $this->method_title = 'SalesHub';
        $this->method_description = 'SalesHub Integration';

        $this->init_form_fields();
        $this->init_settings();

        $this->saleshub_base_url = $this->get_option('saleshub_base_url');
        add_action( 'woocommerce_update_options_integration_' .  $this->id, [ $this, 'process_admin_options' ] );
    }

    public function init_form_fields()
    {
        $this->form_fields = [
            'saleshub_base_url' => [
                'title' => __( 'Base URL' ),
                'type' => 'text',
                'description' => __( 'This is base url of SalesHub, follow instruction of SalesHub to fill correct value.' ),
                'desc_tip' => true,
                'default' => ''
            ]
        ];
    }
}
