<?php
if($_POST)
{
    if(isset($_POST['order']))
    {
        require_once(ABSPATH . 'wp-config.php');
        $cityList = getCities();
        $orders = $_POST['order'];

        $ch = curl_init();

        $msgs = '';

        $date = new DateTime("now", new DateTimeZone('Asia/Karachi'));
        $date = $date->format('Y-m-d');

        foreach($orders as $order)
        {
            $cityId = array_search(strtolower($order['consignee_city']), array_map('strtolower', $cityList));

            $data = [];
            $data['order_id'] = $order['order_id'];
            $data['service_type_id'] = $order['shipping_mode'];
            $data['pickup_address_id'] = $order['pickup_address'];
            $data['information_display'] = 1;
            $data['consignee_city_id'] = $cityId;
            $data['consignee_name'] = trim($order['consignee_name']);
            $data['consignee_address'] = trim($order['consignee_address']);
            $data['consignee_phone_number_1'] = trim($order['consignee_phone']);

            if (!empty(trim($order['consignee_email']))) {
                $data['consignee_email_address'] = trim($order['consignee_email']);
            }

            $data['item_product_type_id'] = 24;
            $data['item_description'] = trim($order['item_description']);
            $data['item_quantity'] = (int)$order['item_quantity'];
            $data['item_insurance'] = 0;
            $data['item_price'] = '';
            $data['pickup_date'] = $date;

            if(trim($order['spec_instructions']))
            {
                $data['special_instructions'] = trim($order['spec_instructions']);
            }

            $data['estimated_weight'] = 1;
            $data['shipping_mode_id'] = (int)$order['shipping_mode'];
            $order_details = wc_get_order( $order['order_id'] );
            $payment_method = $order_details->payment_method;
            $amount = 0;
            if($payment_method == 'cod')
            {
                $amount = (double)$order['amount'];
            }
            $data['amount'] = $amount;
            $data['payment_mode_id'] = 1;
            $data['charges_mode_id'] = 4;

            curl_setopt($ch,CURLOPT_URL, $apiUrl);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch,CURLOPT_POST, true);
            curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            try
            {
                //execute post
                $result = curl_exec($ch);
            }
            catch(Exception $e)
            {
                die($e->getMessage());
            }

            $response = json_decode($result,true);

            $msgs .= "\r\n\r\n<h3><u>Order#".$order['order_id'].'</u></h3>';

            if($response['status'] != 0)
            {
                if (isset($response['errors'])) {
                    foreach($response['errors'] as $field => $err)
                    {
                        $msgs .= "\r\n".$err[0];
                    }
                }
                else {
                    $msgs .= "\r\n".$response['message'];
                }
            }
            else
            {
                $note = "Shipment has been booked. CN # {$response['tracking_number']}.\r\n<a target='_blank' href={$baseUrl}\"tracking?tracking_number={$response['tracking_number']}\">Click to Track order</a>";

                $order_id = $order['order_id'];

                $order = new WC_Order($order['order_id']);

                //Add Order comment
                $order->add_order_note("Order Status updated to Booked at Trax");
                $order->add_order_note($note);

                //Update status
                // $order->update_status('trax-booked');

                global $wpdb;
                $wpdb->insert($wpdb->prefix."postmeta", array('post_id' => $order_id, 'meta_key' => 'booked_by', 'meta_value' => 'CourierX'));
                $wpdb->insert($wpdb->prefix."postmeta", array('post_id' => $order_id, 'meta_key' => 'tracking_number', 'meta_value' => $response['tracking_number']));

                $msgs .= "\r\n".$response['message'].' Tracking # '.$response['tracking_number'];
            }
        }

        //close connection
        curl_close($ch);

        if($msgs!=''){

            // wp_head();
            echo '<pre>';
            echo $msgs;
            echo "\r\n";
            ?>
            <input
                type='button'
                id= 'go-back-btn'
                value='Go Back'
            />
            <?php
            // wp_footer();
            ?>

            <script>
                document.getElementById("go-back-btn").onclick = function ()
                {
                    location.href = "<?= get_admin_url(null, 'edit.php?post_type=shop_order') ?>";
                };
            </script>
            <?php
        }
    }
}
