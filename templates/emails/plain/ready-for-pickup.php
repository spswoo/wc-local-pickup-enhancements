Order is ready for pickup

Hello <?php echo esc_html($order->get_billing_first_name()); ?>,

Your order is ready for pickup.

Location: <?php echo esc_html(get_option('wc_lpe_settings')['address'] ?? ''); ?>
Hours: <?php echo esc_html(get_option('wc_lpe_settings')['hours'] ?? ''); ?>
Instructions: <?php echo esc_html(get_option('wc_lpe_settings')['instructions'] ?? ''); ?>