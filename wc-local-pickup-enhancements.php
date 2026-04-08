<?php
/**
 * Plugin Name: WooCommerce Local Pickup Enhancements
 * Description: Complete Local Pickup UX enhancements with settings, modal, emails, and custom order status.
 * Version: 2.1
 * Author: spswoo
 */

if (!defined('ABSPATH'))
    exit;

class WC_Local_Pickup_Enhancements
{

    private $options;

    public function __construct()
    {
        $this->options = get_option('wc_lpe_settings');

        // Admin
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);

        // Checkout & Frontend
        add_action('wp_footer', [$this, 'enqueue_scripts']);
        add_filter('woocommerce_cart_needs_shipping_address', [$this, 'disable_shipping_for_pickup']);
        add_action('woocommerce_review_order_before_payment', [$this, 'pickup_notice']);

        // Emails
        add_action('woocommerce_email_after_order_table', [$this, 'add_pickup_info_to_emails'], 10, 4);

        // Custom Order Status
        add_action('init', [$this, 'register_ready_for_pickup_status']);
        add_filter('wc_order_statuses', [$this, 'add_ready_for_pickup_to_order_statuses']);
        add_action('woocommerce_order_status_changed', [$this, 'send_pickup_email_on_status'], 10, 3);

        // Add new email woocommerce email class
        add_filter('woocommerce_email_classes', [$this, 'register_ready_for_pickup_email']);

        // Ensure that no shipping method is pre-selected
        add_filter('woocommerce_shipping_chosen_method', '__return_false', 99);
        // Alternative/Additional snippet to force no selection
        add_action('woocommerce_before_checkout_form', 'bbloomer_uncheck_default_shipping_method');
        function bbloomer_uncheck_default_shipping_method()
        {
            WC()->session->set('chosen_shipping_methods', null);
        }

    }

    /** ----------------------------
     * Admin Settings
     ----------------------------*/
    public function add_admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            'Local Pickup',
            'Local Pickup',
            'manage_woocommerce',
            'pickup-settings',
            [$this, 'settings_page']
        );
    }

    public function register_settings()
    {
        register_setting('wc_lpe_group', 'wc_lpe_settings');
    }

    public function settings_page()
    {
        ?>
        <div class="wrap">
            <h1>Local Pickup Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wc_lpe_group'); ?>
                <?php $opts = get_option('wc_lpe_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th>Pickup Address</th>
                        <td><input type="text" name="wc_lpe_settings[address]"
                                value="<?php echo esc_attr($opts['address'] ?? ''); ?>" style="width:400px;"></td>
                    </tr>
                    <tr>
                        <th>Pickup Hours</th>
                        <td><input type="text" name="wc_lpe_settings[hours]"
                                value="<?php echo esc_attr($opts['hours'] ?? ''); ?>" style="width:400px;"></td>
                    </tr>
                    <tr>
                        <th>Instructions</th>
                        <td><textarea name="wc_lpe_settings[instructions]" rows="4"
                                style="width:400px;"><?php echo esc_textarea($opts['instructions'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Google Maps URL</th>
                        <td><input type="text" name="wc_lpe_settings[map_url]"
                                value="<?php echo esc_attr($opts['map_url'] ?? ''); ?>" style="width:400px;"></td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /** ----------------------------
     * Checkout Frontend
     ----------------------------*/
    public function enqueue_scripts()
    {
        if (!is_checkout())
            return;

        $address = esc_js($this->options['address'] ?? 'Store location');
        $hours = esc_js($this->options['hours'] ?? '');
        $instructions = esc_js($this->options['instructions'] ?? '');
        $map_url = esc_js($this->options['map_url'] ?? '');


        ?>
        <style>
            #pickup-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.6);
                z-index: 9999;
            }

            #pickup-modal .modal-content {
                background: #fff;
                max-width: 400px;
                margin: 10% auto;
                padding: 20px;
                border-radius: 6px;
                text-align: center;
            }

            #pickup-modal button {
                margin: 10px;
                padding: 10px 15px;
                cursor: pointer;
            }
        </style>

        <div id="pickup-modal">
            <div class="modal-content">
                <h3>Confirm Store Pickup</h3>
                <p>
                    You selected <strong>Store Pickup</strong>.<br><br>
                    Pickup
                    Location:<br><strong><?php echo esc_html($this->options['address'] ?? 'Store location'); ?></strong><br>
                    <?php echo esc_html($this->options['hours'] ?? ''); ?><br><br>
                    <?php echo esc_html($this->options['instructions'] ?? ''); ?><br><br>
                    No shipping will be provided.
                </p>
                <button id="confirm-pickup">Yes, I'll pick it up</button>
                <button id="cancel-pickup">Choose Shipping Instead</button>
            </div>
        </div>

        <script>
            jQuery(function ($) {

                // Flag to prevent init() from resetting UI mid-confirmation flow
                let pickupConfirmed = false;

                // Store reference to the local pickup radio so we can re-select it after confirmation
                let $pendingPickupInput = null;

                function setPickupUI() {
                    //localStorage.setItem('shippingUI', document.querySelector('.woocommerce-shipping-fields').innerHTML);

                    $('.woocommerce-shipping-fields')
                        .hide()
                        .css('display', 'none');
                    $('#ship-to-different-address').hide();

                    if ($('#pickup-box').length === 0) {
                        $('.woocommerce-shipping-fields').before(
                            '<div id="pickup-box" style="padding:15px; background:#f8f8f8; border:1px solid #ddd; margin-bottom:20px;"></div>'
                        );
                    }

                    $('#pickup-box').html(`
                        <h3>Store Pickup Details</h3>
                        <p>
                            <strong><?php echo $address; ?></strong><br>
                            <?php echo $hours; ?><br><br>
                            You will receive a separate notification that your order is ready for pickup.<br>
                            <?php echo $instructions; ?>
                        </p>
                        <a href="<?php echo $map_url; ?>" target="_blank" style="display:block; margin-top:10px;">
                        <span style="display:block; text-align:left; margin-top:5px;">View on Google Maps</span>
        </a>
                    `).show();
                }

                function setShippingUI() {

                    //localStorage.setItem('shippingUI', document.querySelector('.woocommerce-shipping-fields').innerHTML)

                    if (localStorage.getItem('shippingUI') !== undefined) {
                        //document.querySelector('.woocommerce-shipping-fields').innerHTML = localStorage.getItem('shippingUI')
                    }

                    //console.log("Plugin Local Pickup: setShippingUI()");
                    $('.woocommerce-shipping-fields')
                        .show()
                        .css('display', 'block')
                        .removeClass('hidden');
                    $('#ship-to-different-address').show();
                    $('#pickup-box').hide();
                }

                function bindEvents() {
                    // FIX: Bind to WooCommerce's native shipping method radio buttons
                    $(document.body).off('change.pickup', 'input[name^="shipping_method"]')
                        .on('change.pickup', 'input[name^="shipping_method"]', function () {
                            let val = $(this).val() || '';

                            if (val.includes('local_pickup')) {

                                // If already confirmed, just update the UI — don't show the modal again
                                if (pickupConfirmed) {
                                    setPickupUI();
                                    return;
                                }
                                // Store reference to this input so we can re-select it after confirmation
                                $pendingPickupInput = $(this);

                                // Revert selection to whatever was previously checked until user confirms
                                $(this).prop('checked', false);
                                $(document.body).trigger('update_checkout');

                                // Show the confirmation modal
                                $('#pickup-modal').fadeIn();

                            } else {
                                pickupConfirmed = false;
                                $pendingPickupInput = null;
                                setShippingUI();
                            }
                        });

                    $('#confirm-pickup').off('click.pickup').on('click.pickup', function () {
                        $('#pickup-modal').fadeOut();
                        pickupConfirmed = true;

                        // Re-select the local pickup radio the user originally chose
                        if ($pendingPickupInput && $pendingPickupInput.length) {
                            $pendingPickupInput.prop('checked', true);
                            $(document.body).trigger('update_checkout');
                        } else {
                            // Fallback: find any local_pickup input
                            $('input[name^="shipping_method"][value*="local_pickup"]').prop('checked', true);
                        }

                        setPickupUI();
                        $pendingPickupInput = null;
                    });

                    $('#cancel-pickup').off('click.pickup').on('click.pickup', function () {
                        $('#pickup-modal').fadeOut();
                        pickupConfirmed = false;
                        $pendingPickupInput = null;

                        // Restore to the first non-pickup shipping method
                        let $shipping = $('input[name^="shipping_method"]').not('[value*="local_pickup"]').first();
                        if ($shipping.length) {
                            $shipping.prop('checked', true).trigger('change');
                        }
                        console.log("Cancelled Pickup");
                        setShippingUI();
                    });
                }

                function init() {
                    console.log("Plugin Local Pickup: Initialization");
                    // Don't reset UI while the user is mid-confirmation
                    //if (pickupConfirmed) return;

                    bindEvents();

                    // FIX: Guard against undefined .val() after checkout refresh
                    let selected = $('input[name^="shipping_method"]:checked').val() || '';

                    if (selected.includes('local_pickup')) {
                        pickupConfirmed = true;
                        setPickupUI();
                    } else {
                        setShippingUI();
                    }
                }

                // Initial load
                //$(document.body).on('updated_checkout', function () {
                init();

                //});

                // Re-run after checkout updates (e.g. coupon applied, address changed)
                $(document.body).on('updated_checkout', function () {
                    console.log("Plugin Local Pickup: Event: updated_checkout");
                    bindEvents();

                    let selected = $('input[name^="shipping_method"]:checked').val() || '';

                    if (selected.includes('local_pickup')) {
                        if (pickupConfirmed) {
                            setPickupUI();
                        } else {
                            setShippingUI(); // don't force pickup until confirmed
                        }
                    } else {
                        pickupConfirmed = false;
                        setShippingUI();
                    }
                });

            });
        </script>
        <?php
    }

    public function disable_shipping_for_pickup($needs_shipping)
    {
        if (is_checkout()) {
            $chosen_methods = WC()->session->get('chosen_shipping_methods');
            if (!empty($chosen_methods) && strpos($chosen_methods[0], 'local_pickup') !== false)
                return false;
        }
        return $needs_shipping;
    }

    public function pickup_notice()
    {
        $address = esc_html($this->options['address'] ?? '');
        $hours = esc_html($this->options['hours'] ?? '');
        $instructions = esc_html($this->options['instructions'] ?? '');
        echo "<div id='pickup-notice' style='display:none;padding:12px;background:#f1f1f1;margin-bottom:15px;border-left:4px solid #333;'>
            <strong>Store Pickup Selected</strong><br>
            Pickup at:<br><strong>{$address}</strong><br>{$hours}<br>{$instructions}
        </div>";
    }

    /** ----------------------------
     * Email Injection
     ----------------------------*/
    public function add_pickup_info_to_emails($order, $sent_to_admin, $plain_text, $email)
    {
        if ($sent_to_admin)
            return;

        $pickup_selected = false;
        foreach ($order->get_shipping_methods() as $method) {
            if (strpos($method->get_method_id(), 'local_pickup') !== false) {
                $pickup_selected = true;
                break;
            }
        }
        if (!$pickup_selected)
            return;

        $address = esc_html($this->options['address'] ?? '');
        $hours = esc_html($this->options['hours'] ?? '');
        $instructions = esc_html($this->options['instructions'] ?? '');

        if ($plain_text) {
            echo "\n\nPickup Information:\nLocation: {$address}\nHours: {$hours}\nInstructions: {$instructions}\n";
        } else {
            echo '<h2>Pickup Information</h2><p><strong>Location:</strong> ' . $address . '<br><strong>Hours:</strong> ' . $hours . '<br><strong>Instructions:</strong> ' . $instructions . '</p>';
        }
    }

    /** ----------------------------
     * Custom Order Status: Ready for Pickup
     ----------------------------*/
    public function register_ready_for_pickup_status()
    {
        register_post_status('wc-ready-for-pickup', [
            'label' => 'Ready for Pickup',
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Ready for Pickup (%s)', 'Ready for Pickup (%s)')
        ]);
    }

    public function add_ready_for_pickup_to_order_statuses($order_statuses)
    {
        $order_statuses['wc-ready-for-pickup'] = 'Ready for Pickup';
        return $order_statuses;
    }

    public function send_pickup_email_on_status($order_id, $old_status, $new_status)
    {
        if ($new_status === 'ready-for-pickup') {
            do_action('woocommerce_order_status_ready-for-pickup_notification', $order_id);
        }
    }

    public function register_ready_for_pickup_email($emails)
    {
        require_once plugin_dir_path(__FILE__) . 'includes/class-wc-email-ready-for-pickup.php';
        $emails['WC_Email_Ready_For_Pickup'] = new WC_Email_Ready_For_Pickup();
        return $emails;
    }

}

new WC_Local_Pickup_Enhancements();