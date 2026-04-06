<?php

if (!defined('ABSPATH')) exit;

class WC_Email_Ready_For_Pickup extends WC_Email {

    public function __construct() {

        $this->id             = 'ready_for_pickup';
        $this->title          = 'Ready for Pickup';
        $this->description    = 'Sent to customer when order is ready for pickup.';

        $this->heading        = 'Your order is ready for pickup';
        $this->subject        = 'Your order #{order_number} is ready for pickup';

        $this->customer_email = true;

        $this->template_html  = 'emails/ready-for-pickup.php';
        $this->template_plain = 'emails/plain/ready-for-pickup.php';

        add_action('woocommerce_order_status_ready-for-pickup_notification', [$this, 'trigger'], 10, 2);

        parent::__construct();
    }

    public function trigger($order_id, $order = false) {

        if ($order_id && !$order) {
            $order = wc_get_order($order_id);
        }

        if (!$order) return;

        $this->object = $order;
        $this->recipient = $order->get_billing_email();

        if (!$this->is_enabled() || !$this->get_recipient()) return;

        $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
    }

    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            ['order' => $this->object],
            '',
            plugin_dir_path(__FILE__) . '../templates/'
        );
    }

    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            ['order' => $this->object],
            '',
            plugin_dir_path(__FILE__) . '../templates/'
        );
    }
}