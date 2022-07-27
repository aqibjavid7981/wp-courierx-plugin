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
            $cityIds = array_column($cityList,'CityId');
            $cityNames = array_column($cityList,'CityName');
            $cities = [];
            $i = 0;
            foreach ($cityNames as $cityName)
            {
                $cities[$cityIds[$i]] =  $cityName;
                $i++;
            }

            $cityId = array_search(strtolower($order['consignee_city']), array_map('strtolower', $cities));
            $data = [];
            $data['order_id'] = $order['order_id'];
            $data['service_type_id'] = $order['shipping_mode'];
//            $data['pickup_address'] = $order['pickup_address'];
//            $data['information_display'] = 1;
            $data['destination_city'] = $cityId;
            $data['shipper_city'] = $order['courierx_shipper_city'];
            $data['shipper_code'] = $order['courierx_username'];
            $data['consignee_name'] = trim($order['consignee_name']);
            $data['consignee_address'] = trim($order['consignee_address']);
            $data['customer_phone'] = trim($order['consignee_phone']);

//            if (!empty(trim($order['consignee_email']))) {
//                $data['consignee_email_address'] = trim($order['consignee_email']);
//            }

            $data['item_product_type_id'] = 24;
            $data['item_description'] = trim($order['item_description']);
            $data['pieces'] = (int)$order['item_quantity'];
//            $data['item_insurance'] = 0;
//            $data['item_price'] = '';
//            $data['pickup_date'] = $date;

            if(trim($order['spec_instructions']))
            {
                $data['special_instructions'] = trim($order['spec_instructions']);
            }
            $data['weight'] = trim($order['item_weight']);
            $data['shipping_mode_id'] = (int)$order['shipping_mode'];
            $data['amount'] = $order['amount'];

            $response = generateCN($data);

            $msgs .= "\r\n\r\n<h3><u>Order#".$order['order_id'].'</u></h3>';

            if($response['status'] == 0)
            {
                $msgs .= "\r\n".$response['response'];
            }
            else
            {
                $note = "Shipment has been booked. CN # {$response['response']}";

                $order_id = $order['order_id'];

                $order = new WC_Order($order['order_id']);

                //Add Order comment
                $order->add_order_note("Order Status updated to Booked at CourierX");
                $order->add_order_note($note);

                //Update status
                // $order->update_status('trax-booked');

                global $wpdb;
                $wpdb->insert($wpdb->prefix."postmeta", array('post_id' => $order_id, 'meta_key' => 'booked_by', 'meta_value' => 'CourierX'));
                $wpdb->insert($wpdb->prefix."postmeta", array('post_id' => $order_id, 'meta_key' => 'tracking_number', 'meta_value' => $response['response']));

                $msgs .= "\r\n".$response['message'].' Tracking # '.$response['response'];
            }
        }


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
