<?php
require_once dirname(__FILE__) . '/coinzone/coinzone_api.php';

class Coinzone
{

    private $apiResponse;

    function Coinzone()
    {
        $this->code = 'coinzone';
        $this->version = '1.0.0';
        $this->title = MODULE_PAYMENT_COINZONE_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_COINZONE_TEXT_DESCRIPTION;
        $this->sort_order = 3;
        $this->enabled = true;
    }

    function update_status()
    {
        return false;
    }

    function javascript_validation()
    {
        return false;
    }

    function selection()
    {
        global $order;
        return array('id' => $this->code, 'module' => $this->title, 'description' => 'Bitcoin Payment');
    }

    function pre_confirmation_check()
    {
        return false;
    }

    function process_button()
    {
        return false;
    }
//
//    function before_process()
//    {
//        return false;
//    }

    function after_process()
    {
        global $insert_id, $order;

        $sqlClientCode = tep_db_query("SELECT configuration_value
                                           FROM " . TABLE_CONFIGURATION . "
                                          WHERE configuration_key = 'MODULE_PAYMENT_COINZONE_CLIENT_CODE'");
        $sqlApiKey = tep_db_query("SELECT configuration_value
                                           FROM " . TABLE_CONFIGURATION . "
                                          WHERE configuration_key = 'MODULE_PAYMENT_COINZONE_API_KEY'");

        $clientCode = tep_db_fetch_array($sqlClientCode)['configuration_value'];
        $apiKey = tep_db_fetch_array($sqlApiKey)['configuration_value'];

        $schema = isset($_SERVER['HTTPS']) ? "https://" : "http://";
        $notifUrl = $schema . $_SERVER['HTTP_HOST'] . '/coinzone_callback.php';

        /* create payload array */
        $payload = array(
            'amount' => $order->info['total'],
            'currency' => $order->info['currency'],
            'merchantReference' => $insert_id,
            'email' => $order->customer['email_address'],
            'notificationUrl' => $notifUrl
        );
        $coinzone = new CoinzoneApi($clientCode, $apiKey);
        $response = $coinzone->callApi('transaction', $payload);

        if ($response->status->code == 201) {
            tep_redirect($response->response->url);
        }
        return false;
    }

    function confirmation()
    {

        return false;
    }

    function before_process()
    {
        return false;
    }

    function get_error()
    {
        $error = false;
        if (!empty($_GET['payment_error'])) {
            $error = array(
                'title' => MODULE_PAYMENT_COINZONE_TEXT_ERROR,
                'error' => MODULE_PAYMENT_COINZONE_TEXT_PAYMENT_ERROR
            );
        }

        return $error;
    }

    function output_error()
    {
        return false;
    }

    function check()
    {
        if (!isset($this->check)) {
            $check_query = tep_db_query("SELECT configuration_value
                                           FROM " . TABLE_CONFIGURATION . "
                                          WHERE configuration_key = 'MODULE_PAYMENT_COINZONE_STATUS'");
            $this->check = tep_db_num_rows($check_query);
        }
        return $this->check;
    }

    function install()
    {
        tep_db_query(
            "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added)
            VALUES
            ('Enable Coinzone Module', 'MODULE_PAYMENT_COINZONE_STATUS', 'False', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query(
            "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added)
            VALUES
            ('Paid Order Status', 'MODULE_PAYMENT_COINZONE_PAID_STATUS', '2', 'Automatically set the status of paid orders to this value.', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");

        tep_db_query(
            "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_group_id, sort_order, date_added)
            VALUES
            ('Client Code', 'MODULE_PAYMENT_COINZONE_CLIENT_CODE', '', '6', '2', now()),
            ('API Key', 'MODULE_PAYMENT_COINZONE_API_KEY', '', '6', '3', now())");
    }

    function remove()
    {
        tep_db_query(
            "DELETE FROM " . TABLE_CONFIGURATION . "
            WHERE
            configuration_key IN ('MODULE_PAYMENT_COINZONE_STATUS', 'MODULE_PAYMENT_COINZONE_API_KEY', 'MODULE_PAYMENT_COINZONE_CLIENT_CODE', 'MODULE_PAYMENT_COINZONE_PAID_STATUS')");
    }

    function keys()
    {
        return array(
            'MODULE_PAYMENT_COINZONE_STATUS',
            'MODULE_PAYMENT_COINZONE_CLIENT_CODE',
            'MODULE_PAYMENT_COINZONE_API_KEY',
            'MODULE_PAYMENT_COINZONE_PAID_STATUS'
        );
    }
}
