<?php
/**
 * Plugin Name: CourierX
 * Description: YOUR RELIABLE E-COMMERCE DELIVERY PARTNERS
 * Version: 1.0
 * Author: Gigasol
 * Author URI: https://gigasol.com/
 **/

require_once(ABSPATH . 'wp-config.php');
const CITY_API_REQUEST_URL = 'https://cod.courierx.pk/api/PortalAPI/GetCity';
const SERVICES_API_REQUEST_URL = 'https://testcod.courierx.pk/api/PortalAPI/GetServiceTypes';
const SAVE_BOOKING_API = 'https://testcod.courierx.pk/api/PortalAPI/SaveBooking';
function register_shipment_arrival_order_status_courierx() {
    register_post_status( 'wc-courierx-booked', array(
        'label'                     => 'Booked at CourierX',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop( 'Booked at CourierX <span class="count">(%s)</span>', 'Booked at CourierX <span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', 'register_shipment_arrival_order_status_courierx' );

function add_awaiting_shipment_to_order_statuses_courierx( $order_statuses ) {
    $new_order_statuses = array();
    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-courierx-booked'] = 'Booked at CourierX';
        }
    }
    return $new_order_statuses;
}

add_filter( 'wc_order_statuses', 'add_awaiting_shipment_to_order_statuses_courierx' );

function courierX_function($columns){
    $new_columns = (is_array($columns)) ? $columns : array();
    unset( $new_columns['order_actions'] );

    //all of your columns will be added before the actions column
    $new_columns['courierx'] = 'courierX Tracking Id';
    //stop editing

    $new_columns['order_actions'] = $columns['order_actions'];
    return $new_columns;
}

add_filter( 'manage_edit-shop_order_columns', 'courierX_function' );

function courierx_value_function($column){
    global $post;
    $data = get_post_meta($post->ID);

    if ($column == 'courierx') {
        global $wpdb;

        $result = $wpdb->get_results("SELECT *  FROM ".$wpdb->prefix."postmeta where  post_id =".$post->ID);

        $tracking_link = '';

        foreach($result as $row) {
            if($row->meta_key == 'tracking_number') {
//                $tracking_link = '<a target="_blank" href="https://sonic.pk/tracking?tracking_number=' . $row->meta_value . '">Track Shipment: ' . $row->meta_value . '</a>';
                                $tracking_link = '<a  href="javascript:void(0);">CN'. $row->meta_value . '</a>';
            }
        }

        if (!empty($tracking_link)) {
            echo $tracking_link;
        }
    }
}

add_action( 'manage_shop_order_posts_custom_column', 'courierx_value_function', 2 );

function submit_form_courierx()
{
    if (!empty($_POST['form_submitted']))
    {
        $courierx_username  = get_option('courierx_username');
        $courierx_password  = get_option('courierx_password');
        $courierx_shipper_city  = get_option('courierx_shipper_city');
        $courierx_shipper_name  = get_option('courierx_shipper_name');
        $courierx_shipper_email  = get_option('courierx_shipper_email');
        $courierx_shipper_phone  = get_option('courierx_shipper_phone');
        $courierx_shipper_address  = get_option('courierx_shipper_address');

        if($courierx_username == '' && $courierx_password == '' && $courierx_shipper_city == '' &&
            $courierx_shipper_name == '' && $courierx_shipper_email == '' && $courierx_shipper_phone == '' && $courierx_shipper_address == '')
        { die('Please specify all mandatory fields for courierX settings'); }

        ob_start();
        include('submit_booking.php');
        echo ob_get_clean();
        die;
    }
}

add_action( 'init', 'submit_form_courierx' );

//Trax plugin -- START

add_filter( 'bulk_actions-edit-shop_order', 'courierx_bulk_actions' );

function courierx_bulk_actions( $bulk_array ) {

    $bulk_array['courierx_orders'] = 'Book at CourierX';
    return $bulk_array;

}

add_filter( 'handle_bulk_actions-edit-shop_order', 'courierx_bulk_action_handler', 10, 3 );

function courierx_bulk_action_handler( $redirect, $doaction, $object_ids )
{
    if ($doaction == 'courierx_orders') {
        $service_types = getServices();
        wp_head();
        require_once( ABSPATH . 'wp-admin/admin-header.php' );
        ?>
        <style>
            @import url('https://fonts.googleapis.com/css?family=Roboto&display=swap');

            *
            {
                font-family: 'Roboto', sans-serif;
            }

            .form-box
            {
                padding: 21px 29px;
                width: 50%;
            }

            .form-box h3
            {
                color: #736969;
            }

            .form-box h1,h2
            {
                text-transform: uppercase;
                border-bottom: 1px solid #dedede;
                padding-bottom: 15px;
                color: #736969;
            }

            .form-box .order-label
            {
                background: #23282d;
            }

            .form-box .order-label h2
            {
                border: 0;
                margin-bottom: 0;
                color: #ffffff;
                font-size: 20px;
                padding: 10px 15px;
            }

            .form-box table
            {
                text-align: left;
                width: 100%;
                background: #f7f7f7;
                padding: 27px 30px;
                margin-bottom: 35px;
            }

            .form-box table tr
            {
                margin-bottom: 13px;
                display: block;
            }

            .form-box table td

            {
                padding: 5px 3px;
                display: block;
            }

            .form-box table th
            {
                width: 100%;
                display: block;
            }

            .form-box input
            {
                width: 100%;
                padding: 12px 10px;
                border: 1px solid #e6e4e4;
                margin-top: 4px;
                border-radius: 5px;
            }

            .form-box select
            {
                width: 100%;
                width: 100%;
                padding: 12px 10px;
                border: 1px solid #e6e4e4;
                margin-top: 4px;
                border-radius: 5px;
            }

            .form-box textarea
            {
                width: 100%;
                padding: 12px 10px;
                border: 1px solid #e6e4e4;
                margin-top: 4px;
                border-radius: 5px;
                min-height: 130px;
                resize: none;
                outline: none;
            }

            .form-box input[type=submit]
            {
                width: 159px;
                text-transform: uppercase;
                background: #0673aa;
                color: #fff;
                font-weight: bold;
            }

            .form-box label
            {
                color: #524b4b;
                font-weight: 300;
            }



        </style>
        <div class="form-box">
            <?php //screen_icon(); ?>
            <h1>CourierX  Bookings</h1>
            <form method="post">
                <input type="hidden" name="form_submitted" value="true" />
                <?php  submit_button(); ?>
                <?php
                $count = 0;
                $skip = 0;

                foreach($object_ids as $orderId)
                {
                    $getOrder = new WC_Order($orderId);
                    $getOrder = $getOrder->get_data();

                    if($getOrder['status'] == 'courierx-booked')
                    {
                        $skip++;

                        if(count($object_ids) == $skip)
                        {
                            wp_redirect(wp_get_referer());
                            die;
                        }


                        continue;
                    }

                    $count++;
                    $itemDesc = '';
                    $itemQty = 0;
                    $item_weight = 0;

                    //Get Order Info
                    $order = new WC_Order($orderId);
                    $orderData = $order->get_data();

                    $key = 0;
                    foreach($orderData['line_items'] as $item)
                    {
                        $item_data = $item->get_data();

                        $itemQty += $item_data['quantity'];
                        $item_weight += $item_data['weight'];
                        if($key != 0)
                        {
                            $itemDesc .= ", ";
                        }
                        $itemDesc .= $item_data['quantity'] . " X ".$item_data['name'];
                        $key++;
                    }

                    if (trim($orderData['shipping']['first_name']) != '') {
                        $first_name = $orderData['shipping']['first_name'];
                    }
                    else {
                        $first_name = $orderData['billing']['first_name'];
                    }

                    if (trim($orderData['shipping']['last_name']) != '') {
                        $last_name = $orderData['shipping']['last_name'];
                    }
                    else {
                        $last_name = $orderData['billing']['last_name'];
                    }

                    if (trim($orderData['shipping']['address_1']) != '') {
                        $address_1 = $orderData['shipping']['address_1'];
                    }
                    else {
                        $address_1 = $orderData['billing']['address_1'];
                    }

                    if (trim($orderData['shipping']['address_2']) != '') {
                        $address_2 = $orderData['shipping']['address_2'];
                    }
                    else {
                        $address_2 = $orderData['billing']['address_2'];
                    }

                    if (trim($orderData['shipping']['phone']) != '') {
                        $phone = $orderData['shipping']['phone'];
                    }
                    else {
                        $phone = $orderData['billing']['phone'];
                    }

                    if (trim($orderData['shipping']['email']) != '') {
                        $email = $orderData['shipping']['email'];
                    }
                    else {
                        $email = $orderData['billing']['email'];
                    }

                    if (trim($orderData['shipping']['city']) != '') {
                        $city = $orderData['shipping']['city'];
                    }
                    else {
                        $city = $orderData['billing']['city'];
                    }
                    ?>
                    <div class='order-label'><h2><?= $count ?>. Order # <?= $orderId?></h2></div>
                    <table>
                        <input type="hidden" id="order_id_<?= $count ?>" name="order[<?= $count ?>][order_id]" value="<?= $orderId ?>" required/>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="consignee_name_<?= $count ?>">Consignee Name</label>
                            </th>
                            <td>
                                <input type="text" id="consignee_name_<?= $count ?>" name="order[<?= $count ?>][consignee_name]" value="<?= $first_name.' '.$last_name?>" required/>
                            </td>
                        </tr>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="consignee_address_<?= $count ?>">Consignee Address</label>
                            </th>
                            <td>
                                <textarea required id="consignee_address_<?= $count ?>" name="order[<?= $count ?>][consignee_address]"><?= $address_1."\r\n".$address_2?></textarea>
                            </td>
                        </tr>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="consignee_phone_<?= $count ?>">Consignee Phone#</label>
                            </th>
                            <td>
                                <input type="text" id="consignee_phone_<?= $count ?>" name="order[<?= $count ?>][consignee_phone]" value="<?= $phone?>" pattern="[0-9]{3,11}" placeholder='03xxxxxxxxx' required/>
                            </td>
                        </tr>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="consignee_email_<?= $count ?>">Consignee Email</label>
                            </th>
                            <td>
                                <input type="email" id="consignee_email_<?= $count ?>" name="order[<?= $count ?>][consignee_email]" value="<?= $email?>" />
                            </td>
                        </tr>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="consignee_city_<?= $count ?>">Consignee City</label>
                            </th>
                            <td>
                                <input type="text" id="consignee_city_<?= $count ?>" name="order[<?= $count ?>][consignee_city]" value="<?= $city?>" required/>
                            </td>
                        </tr>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="pickup_address_<?= $count ?>">Pickup Address</label>
                            </th>
                            <td>
                                <textarea id="pickup_address_<?= $count ?>" name="order[<?= $count ?>][pickup_address]"><?= get_option('courierx_shipper_address'); ?></textarea>
                            </td>
                        </tr>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="shipping_mode_<?= $count ?>">Shipping Mode</label>
                            </th>
                            <td>
                                <select id="shipping_mode_<?= $count ?>" name="order[<?= $count ?>][shipping_mode]">
                                    <?php foreach ($service_types as $service_type):?>
                                        <option value="<?= $service_type['ServiceTypeId'];?>"><?= $service_type['ServiceTypeName'];?></option>
                                    <?php endforeach;?>
                                </select>
                            </td>
                        </tr>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="amount_<?= $count ?>">COD Amount (PKR)</label>
                            </th>
                            <td>
                                <input type="text" id="amount_<?= $count ?>" name="order[<?= $count ?>][amount]" value="<?= $orderData['total']?>" required/>
                            </td>
                        </tr>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="item_description_<?= $count ?>">Item Description</label>
                            </th>
                            <td>
                                <textarea id="item_description_<?= $count ?>" name="order[<?= $count ?>][item_description]" required><?= $itemDesc ?></textarea>
                            </td>
                        </tr>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="item_quantity_<?= $count ?>">Item Quantity</label>
                            </th>
                            <td>
                                <input type="number" min="1" id="item_quantity_<?= $count ?>" name="order[<?= $count ?>][item_quantity]" value="<?= $itemQty ?>" required/>
                            </td>
                        </tr>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="item_weight_<?= $count ?>">Item Weight(Kg)</label>
                            </th>
                            <td>
                                <input type="number" min="0" step="any" id="item_weight_<?= $count ?>" name="order[<?= $count ?>][item_weight]" value="<?= $item_weight ?>" required/>
                                <input type="hidden"  id="courierx_username_<?= $count ?>" name="order[<?= $count ?>][courierx_username]" value="<?php echo get_option('courierx_username'); ?>"/>
                                <input type="hidden"  id="courierx_shipper_city<?= $count ?>" name="order[<?= $count ?>][courierx_shipper_city]" value="<?php echo get_option('courierx_shipper_city'); ?>"/>
                            </td>
                        </tr>
                        <tr valign="middle">
                            <th scope="row">
                                <label for="spec_instructions_<?= $count ?>">Special Instructions</label>
                            </th>
                            <td>
                                <textarea id="spec_instructions_<?= $count ?>" name="order[<?= $count ?>][spec_instructions]"><?= $orderData['customer_note']?></textarea>
                            </td>
                        </tr>
                    </table>
                    <?php
                }
                ?>
                <?php  submit_button(); ?>
            </form>
        </div>
        <?php wp_footer(); ?>

        <?php
        exit;
    }
    else {
        return $redirect;
    }
}

add_action( 'admin_init', 'courierx_register_settings' );

function courierx_register_settings() {
    add_option('courierx_username','');
    add_option('courierx_password','');
    add_option('courierx_shipper_city','');
    add_option('courierx_shipper_name','');
    add_option('courierx_shipper_email','');
    add_option('courierx_shipper_phone','');
    add_option('courierx_shipper_address','');
    register_setting( 'courierx_options_group', 'courierx_username', 'courierx_callback' );
    register_setting( 'courierx_options_group', 'courierx_password', 'courierx_callback' );
    register_setting( 'courierx_options_group', 'courierx_shipper_city', 'courierx_callback' );
    register_setting( 'courierx_options_group', 'courierx_shipper_name', 'courierx_callback' );
    register_setting( 'courierx_options_group', 'courierx_shipper_email', 'courierx_callback' );
    register_setting( 'courierx_options_group', 'courierx_shipper_phone', 'courierx_callback' );
    register_setting( 'courierx_options_group', 'courierx_shipper_address', 'courierx_callback' );
}

add_action('admin_menu', 'courierx_register_options_page');

function courierx_register_options_page() {
    add_options_page('Configure CourierX Credentials', 'Configure CourierX Credentials', 'manage_options', 'courierx', 'courierx_options_page');
}

function courierx_options_page()
{
    $cities = getCities();
    ?>
    <div>
        <?php screen_icon(); ?>
        <h2>CourierX Credentials</h2>
        <form method="post" action="options.php">
            <?php settings_fields( 'courierx_options_group' ); ?>
<!--            <h3>Shipment Booking</h3>-->
            <table>
                <tr valign="top">
                    <th scope="row"><label for="courierx_username">User Name*: </label></th>
                    <td><input type="text" id="courierx_username" name="courierx_username" value="<?php echo get_option('courierx_username'); ?>" placeholder="e.g 10019" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="courierx_password">Password*: </label></th>
                    <td><input type="password" id="courierx_password" name="courierx_password" value="<?php echo get_option('courierx_password'); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="courierx_shipper_city">Shipper City*: </label></th>
                    <td>
                        <select name="courierx_shipper_city" id="courierx_shipper_city" required>
                            <?php foreach ($cities as $city):?>
                                <option value="<?= $city['CityId'];?>" <?php
                                if(get_option('courierx_shipper_city') == $city['CityId']){
                                  echo "selected";
                                } ?>><?= $city['CityName'];?></option>
                            <?php endforeach;?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="courierx_shipper_name">Shipper Name*: </label></th>
                    <td><input type="text" id="courierx_shipper_name" name="courierx_shipper_name" value="<?php echo get_option('courierx_shipper_name'); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="courierx_shipper_email">Shipper Email*: </label></th>
                    <td><input type="email" id="courierx_shipper_email" name="courierx_shipper_email" value="<?php echo get_option('courierx_shipper_email'); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="courierx_shipper_phone">Shipper Phone Number*: </label></th>
                    <td><input type="number" min="0" id="courierx_shipper_phone" name="courierx_shipper_phone" value="<?php echo get_option('courierx_shipper_phone'); ?>" required/></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="courierx_shipper_address">Shipper Address*: </label></th>
                    <td>
                        <textarea id="courierx_shipper_address" name="courierx_shipper_address" required><?php echo get_option('courierx_shipper_address'); ?></textarea>
                    </td>
                </tr>
            </table>
            <?php  submit_button(); ?>
        </form>
    </div>
    <?php
}

function get_request_api($url)
{
    $ch                     = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url); //Url together with parameters
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
    //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
    curl_setopt($ch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
    curl_setopt($ch, CURLOPT_HEADER, 0);

    $result = curl_exec($ch);

    curl_close($ch);
    return $result;
}

function getCities(){
    return json_decode(get_request_api(CITY_API_REQUEST_URL),true);
}
function getServices(){
    return json_decode(get_request_api(SERVICES_API_REQUEST_URL),true);
}
function generateCN($dataArray){


    if (!empty($dataArray['special_instructions'])){
        $instructions = urlencode($dataArray['special_instructions']);
    }
    if (!empty($dataArray['consignee_address'])){
        $address = urlencode($dataArray['consignee_address']);
        $customer_name = urlencode($dataArray['consignee_name']);
    }
    if(!empty($dataArray["item_description"])){
        $product_details = urlencode($dataArray['item_description']);
    }
    $url = SAVE_BOOKING_API.'?Clientcode='.$dataArray["shipper_code"].
        '&FromCityId='.$dataArray["shipper_city"].'&ToCityId='.$dataArray["destination_city"].
        '&ServiceTypeId='.$dataArray["service_type_id"].'&ConsigneeName='.$customer_name.
        '&ConsigneeRef='.$dataArray["order_id"].'&ConsigneeAddress='.$address.
        '&ConsigneeMobile='.$dataArray["customer_phone"].'&Weight='.$dataArray["weight"].
        '&Pcs='.$dataArray["pieces"].'&CODAmount='.$dataArray["amount"].'&Remarks='.$instructions.
        '&ProductDetail='.$product_details;
    $response = get_request_api($url);
    $result = json_decode($response,true);
//    echo "<pre>";
//    print_r($dataArray["shipper_code"]);
//    exit();
    $statusArray = [
        'status' => 0,
        'response' => ''
    ];

    if ($result['ErrorMsg'] == 'Booking has been created'){
        $statusArray['status'] = 1;
        $statusArray['response'] = $result['CN'];
    }
    else{
        $statusArray['status'] = 0;
        $statusArray['response'] = $result['ErrorMsg'];
    }
    return $statusArray;
}
