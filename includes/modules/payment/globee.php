<?php

require __DIR__.'/../../../globee/autoload.php';

define('MODULE_PAYMENT_GLOBEE_TEXT_TITLE', 'GloBee Cryptocurrency Payments');
define('MODULE_PAYMENT_GLOBEE_TEXT_DESCRIPTION', 'Accept payments in multiple crypto currencies, supported by GloBee.');
define('MODULE_PAYMENT_GLOBEE_BAD_CURRENCY', 'The chosen currency is not supported by GloBee.');
define('MODULE_PAYMENT_GLOBEE_ERROR_DEFAULT', 'Unable to process payment using GloBee.');
define('MODULE_PAYMENT_GLOBEE_ERROR_TIMEOUT', 'The checkout process timed out. Please try again.');
define('MODULE_PAYMENT_GLOBEE_ERROR_CONNECTION', 'The checkout failed to connect. Please try again.');

if (!(function_exists('tep_remove_order'))) {
    require 'globee/remove_order.php';
}

class globee
{

    /**
     * @var
     */
    public $code;

    /**
     * @var
     */
    public $title;

    /**
     * @var
     */
    public $description;

    /**
     * @var
     */
    public $enabled;

    /**
     */
    function globee()
    {
        global $order;

        $this->code = 'globee';
        $this->title = MODULE_PAYMENT_GLOBEE_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_GLOBEE_TEXT_DESCRIPTION;
        $this->sort_order = 0;
        $this->enabled = ((MODULE_PAYMENT_GLOBEE_STATUS == true) ? true : false);

        if (is_object($order)) {
            $this->update_status();
        }
    }

    /**
     * Here you can implement using payment zones
     *
     * Called by module's class constructor, checkout_confirmation.php, checkout_process.php
     */
    function update_status()
    {
        global $order;

        # Check that the currency is supported
        if (array_search(
                $order->info['currency'],
                array_map('trim', explode(",", MODULE_PAYMENT_GLOBEE_CURRENCIES))
            ) === false) {
            $this->enabled = false;
        }

        # Check that the Payment API has been setup
        if (!MODULE_PAYMENT_GLOBEE_PAYMENT_API_KEY OR !strlen(MODULE_PAYMENT_GLOBEE_PAYMENT_API_KEY)) {
            $this->enabled = false;
        }
    }

    /**
     * Here you may define client side javascript that will verify any input fields you use in the payment method
     * selection page
     *
     * Called by checkout_payment.php
     *
     * @return boolean
     */
    function javascript_validation()
    {
        return false;
    }

    /**
     * This function outputs the payment method title/text and if required, the input fields
     *
     * Called by checkout_payment.php
     *
     * @return array
     */
    function selection()
    {
        return array(
            'id' => $this->code,
            'module' => $this->title
        );
    }

    /**
     * Use this function to implement any checks of any conditions after payment method has been selected
     *
     * Called by checkout_confirmation.php before any page output
     *
     * @return boolean
     */
    function pre_confirmation_check()
    {
        return false;
    }

    /**
     * Implement any checks or processing on the order information before proceeding to payment confirmation
     *
     * Called by checkout_confirmation.php
     *
     * @return boolean
     */
    function confirmation()
    {
        return false;
    }

    /**
     * Outputs the html form hidden elements sent as POST data to the payment gateway
     *
     * Called by checkout_confirmation.php
     *
     * @return boolean
     */
    function process_button()
    {
        return false;
    }

    /**
     * This is where you will implement any payment verification.
     *
     * Called by checkout_process.php before order is finalised
     *
     * @return false
     */
    function before_process()
    {
        return false;
    }

    /**
     * Here you may implement any post processing of the payment/order after the order has been finalised. At this
     * point you now have a reference to the created osCommerce order id and you would typically update any custom
     * database tables you may have for your module
     *
     * Called by checkout_process.php after order is finalised
     *
     * @return false
     */
    function after_process()
    {
        global $insert_id, $order;
        require_once 'globee/PaymentApi.php';

        $orderSpeed = array(
            "High" => 'high',
            "Medium" => 'medium',
            "Low" => 'low'
        );

        tep_db_query("update " . TABLE_ORDERS
            . " set orders_status = " . intval(MODULE_PAYMENT_GLOBEE_UNPAID_STATUS_ID)
            . " where orders_id = " . intval($insert_id));

        $connector = new \GloBee\PaymentApi\Connectors\GloBeeCurlConnector(
            MODULE_PAYMENT_GLOBEE_PAYMENT_API_KEY, (MODULE_PAYMENT_GLOBEE_LIVENET == 'False' ? false : true)
        );
        $paymentApi = new \GloBee\PaymentApi\PaymentApi($connector);

        $paymentRequest = new \GloBee\PaymentApi\Models\PaymentRequest();

        $paymentRequest->currency = $order->info['currency'];
        $paymentRequest->customerName = $order->customer['firstname'] . ' ' . $order->customer['lastname'];
        $paymentRequest->customerEmail = (isset($order->customer['email_address']) && filter_var($order->customer['email_address'], FILTER_VALIDATE_EMAIL) !== false ? $order->customer['email_address'] : null);
        $paymentRequest->confirmationSpeed = $orderSpeed[MODULE_PAYMENT_GLOBEE_TRANSACTION_SPEED];
        $paymentRequest->callbackData = $insert_id;
        $paymentRequest->customPaymentId = $insert_id;
        $paymentRequest->successUrl = tep_href_link(FILENAME_ACCOUNT, '', 'SSL', true, true);
        $paymentRequest->ipnUrl = tep_href_link('globee_callback.php', '', 'SSL', true, true);
        $paymentRequest->total = $order->info['total'];

        $response = $paymentApi->createPaymentRequest($paymentRequest);

        $_SESSION['cart']->reset(true);
        tep_redirect($response->redirectUrl);

        return false;
    }

    /**
     * For more advanced error handling. When your module logic returns any errors you will redirect to
     * checkout_payment.php with some error information. When implemented correctly, this function can be used to
     * generate the proper error texts for particular errors
     *
     * Called by checkout_payment.php
     *
     * @return boolean
     */
    function get_error()
    {
        global $HTTP_GET_VARS;

        $error = '';
        $error_text['title'] = MODULE_PAYMENT_GLOBEE_ERROR_DEFAULT;
        if (isset($HTTP_GET_VARS['error'])) {
            $error = urldecode($HTTP_GET_VARS['error']);
        }
        switch($error){
            case 'TIMEOUT':
                $error_text['error'] = MODULE_PAYMENT_GLOBEE_ERROR_TIMEOUT;
                break;
            case 'CONNECT_FAIL':
                $error_text['error'] = MODULE_PAYMENT_GLOBEE_ERROR_CONNECTION;
                break;
            default:
                $error_text['error'] = MODULE_PAYMENT_GLOBEE_ERROR_DEFAULT ." ($error)";
                break;
        }
        return $error_text;
    }

    /**
     * Standard functionality for osCommerce to see if the module is installed
     *
     * @return integer
     */
    function check()
    {
        if (!isset($this->_check)) {
            $check_query = tep_db_query(
                "select configuration_value from " . TABLE_CONFIGURATION
                . " where configuration_key = 'MODULE_PAYMENT_GLOBEE_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }

        return $this->_check;
    }

    /**
     * This is where you define module's configurations (displayed in admin)
     */
    function install()
    {
        tep_db_query("insert into " . TABLE_CONFIGURATION
            . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
            . "values ('Enable GloBee Module', 'MODULE_PAYMENT_GLOBEE_STATUS', 'True', 'Do you want to enable GloBee as a payment method?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now());");
        tep_db_query("insert into " . TABLE_CONFIGURATION
            . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
            . "values ('Use Livenet', 'MODULE_PAYMENT_GLOBEE_LIVENET', 'True', 'Set to false if you want to use the GloBee Test System.', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now());");
        tep_db_query("insert into " . TABLE_CONFIGURATION
            . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
            . "values ('API Key', 'MODULE_PAYMENT_GLOBEE_PAYMENT_API_KEY', '', 'Enter you GloBee Payment API Key', '6', '0', now());");
        tep_db_query("insert into " . TABLE_CONFIGURATION
            . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
            . "values ('Transaction Speed', 'MODULE_PAYMENT_GLOBEE_TRANSACTION_SPEED', 'Medium', 'At what speed do you want the transactions to be considered confirmed?', '6', '0', 'tep_cfg_select_option(array(\'High\', \'Medium\', \'Low\'),', now());");
        tep_db_query("insert into " . TABLE_CONFIGURATION
            . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) "
            . "values ('Unpaid Order Status', 'MODULE_PAYMENT_GLOBEE_UNPAID_STATUS_ID', '" . intval(DEFAULT_ORDERS_STATUS_ID) . "', 'Automatically set the status of unpaid orders to this value.', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION
            . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) "
            . "values ('Paid Order Status', 'MODULE_PAYMENT_GLOBEE_PAID_STATUS_ID', '2', 'Automatically set the status of paid orders to this value.', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION
            . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
            . "values ('Currencies', 'MODULE_PAYMENT_GLOBEE_CURRENCIES', 'BTC, XMR, LTC, DOGE, LNBT, DCR, ETH, XMR, USD, EUR, GBP, AUD, BGN, BRL, CAD, CHF, CNY, CZK, DKK, HKD, HRK, HUF, IDR, ILS, INR, JPY, KRW, LTL, LVL, MXN, MYR, NOK, NZD, PHP, PLN, RON, RUB, SEK, SGD, THB, TRY, ZAR', 'Only enable GloBee payments if one of these currencies is selected (note: currency must be supported by globee.com).', '6', '0', now())");
        tep_db_query("insert into " . TABLE_CONFIGURATION
            . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
            . "values ('Sort Order of Display.', 'MODULE_PAYMENT_GLOBEE_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '2', now())");
    }

    /**
     * Standard functionality to uninstall the module
     */
    function remove()
    {
        tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    /**
     * This array must include all the configuration setting keys defined in your install() function
     *
     * @return array
     */
    function keys()
    {
        return array(
            'MODULE_PAYMENT_GLOBEE_STATUS',
            'MODULE_PAYMENT_GLOBEE_LIVENET',
            'MODULE_PAYMENT_GLOBEE_PAYMENT_API_KEY',
            'MODULE_PAYMENT_GLOBEE_TRANSACTION_SPEED',
            'MODULE_PAYMENT_GLOBEE_UNPAID_STATUS_ID',
            'MODULE_PAYMENT_GLOBEE_PAID_STATUS_ID',
            'MODULE_PAYMENT_GLOBEE_SORT_ORDER',
            'MODULE_PAYMENT_GLOBEE_CURRENCIES',
        );
    }
}
