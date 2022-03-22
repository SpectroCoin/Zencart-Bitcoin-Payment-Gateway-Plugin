<?php

require_once dirname(__FILE__).'/spectrocoin/Spectrocoin.php';

class spectrocoin extends base{

    var $code, $title, $description, $enabled;

    protected static $options = array(
        array(
            'configuration_title' => 'Enable SPECTROCOIN Module',
            'configuration_key' => 'MODULE_PAYMENT_SPECTROCOIN_STATUS',
            'configuration_value' => 'True',
            'configuration_description' => 'Enable the SPECTROCOIN bitcoin plugin?',
            'configuration_group_id' => '6',
            'sort_order' => '0',
            'set_function' => "zen_cfg_select_option(array(\'True\', \'False\'), ",
            '!date_added' => 'now()',
        ),
        array(
            'configuration_title' => 'SpectroCoin API URL',
            'configuration_key' => 'MODULE_PAYMENT_SPECTROCOIN_API_URL',
            'configuration_value' => 'https://spectrocoin.com/api/merchant/1',
            'configuration_description' => 'URL to API of SpectroCoin?',
            'configuration_group_id' => '6',
            'sort_order' => '0',
            '!date_added' => 'now()',
        ),
        array(
            'configuration_title' => 'Project ID',
            'configuration_key' => 'MODULE_PAYMENT_SPECTROCOIN_PROJECT_ID',
            'configuration_value' => '***',
            'configuration_description' => 'Enter your project id',
            'configuration_group_id' => '6',
            'sort_order' => '0',
            '!date_added' => 'now()',
        ),
        array(
            'configuration_title' => 'Merchant ID',
            'configuration_key' => 'MODULE_PAYMENT_SPECTROCOIN_MERCHANT_ID',
            'configuration_value' => '***',
            'configuration_description' => 'Enter your merchant id',
            'configuration_group_id' => '6',
            'sort_order' => '0',
            '!date_added' => 'now()',
        ),
        array(
            'configuration_title' => 'Project private key',
            'configuration_key' => 'MODULE_PAYMENT_SPECTROCOIN_PRIVATE_KEY',
            'configuration_value' => '***',
            'configuration_group_id' => '6',
            'sort_order' => '0',
            'set_function' => 'zen_cfg_textarea(',
            '!date_added' => 'now()',
        ),
        array(
            'configuration_title' => 'Should the fee be paid by a "client" or by "shop"?',
            'configuration_key' => 'MODULE_PAYMENT_SPECTROCOIN_PAYMENT_METHOD',
            'configuration_value' => 'BTC',
            'configuration_description' => 'In case the fee is paid by the client, the fee amount will be added to the total price of the item/service. E.g item/service price is 100 USD and fee is 0.5% then the client will pay 100.5 USD. You will receive the full item price – 100 USD. In case the fee is paid by you, the fee will be deducted from the total item price, hence you will receive 99.5 USD.',
            'configuration_group_id' => '6',
            'sort_order' => '0',
            'set_function' => "zen_cfg_select_option(array(\'shop\', \'client\'), ",
            '!date_added' => 'now()',
        ),
    );

    // Constructor
    function __construct() {

        $this->code = 'spectrocoin';
        $this->title = MODULE_PAYMENT_SPECTROCOIN_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_SPECTROCOIN_TEXT_DESCRIPTION;
        $this->enabled = ((MODULE_PAYMENT_SPECTROCOIN_STATUS == 'True') ? true : false);
        //$this->sort_order = -2;
        //$this->order_status = 'Pending';
    }


    public static function getSpectrocoinStatuses() {
        return array(
            OrderStatusEnum::$New     => array(
                'name' => 'New',
                'optionName' => 'MODULE_PAYMENT_SPECTROCOIN_STATUS_ID_NEW'
            ),
            OrderStatusEnum::$Pending => array(
                'name' => 'Pending',
                'optionName' => 'MODULE_PAYMENT_SPECTROCOIN_STATUS_ID_PENDING'
            ),
            OrderStatusEnum::$Paid    => array(
                'name' => 'Paid',
                'optionName' => 'MODULE_PAYMENT_SPECTROCOIN_STATUS_ID_PAID'
            ),
            OrderStatusEnum::$Failed  => array(
                'name' => 'Failed',
                'optionName' => 'MODULE_PAYMENT_SPECTROCOIN_STATUS_ID_FAILED'
            ),
            OrderStatusEnum::$Expired => array(
                'name' => 'Expired',
                'optionName' => 'MODULE_PAYMENT_SPECTROCOIN_STATUS_ID_EXPIRED'
            ),
            OrderStatusEnum::$Test    => array(
                'name' => 'Test',
                'optionName' => 'MODULE_PAYMENT_SPECTROCOIN_STATUS_ID_TEST'
            ),
        );
    }


    protected static function generateStatusOptions() {
        global $db;

        $spectrocoinStatuses = self::getSpectrocoinStatuses();

        $query = $db->Execute("SELECT orders_status_id, orders_status_name FROM " . TABLE_ORDERS_STATUS." ORDER BY orders_status_id ASC");

        // Generate description
        $description = '<ul>';
        $listItems = array();
        $selectOptions = array();
        foreach ($query as $row) {
            $listItems[] = "<li>Status: ${row['orders_status_id']} = ${row['orders_status_name']}</li>";
            $selectOptions[] = "\\'".(string) $row['orders_status_id']."\\'";
        }
        $description .= implode("\n", $listItems);
        $description .= '</ul>';

        $options = array();
        foreach ($spectrocoinStatuses as $key => $status) {
            $options[] = array(
                'configuration_title' => 'SpectroCoin order status "'.$status['name'].'"',
                'configuration_key' => $status['optionName'],
                'configuration_value' => DEFAULT_ORDERS_STATUS_ID,
                'configuration_description' => $description,
                'configuration_group_id' => '6',
                'sort_order' => '0',
                'set_function' => 'zen_cfg_select_drop_down(array('.implode(', ', $selectOptions).'), ',
                '!date_added' => 'now()',
            );
        }

        return $options;
    }

    protected static function getConfigurationOptions() {
        return array_merge(self::$options, self::generateStatusOptions());
    }


    /**
     * Returns INSERT statement for the configuration
     * @param array $options
     * @return string
     */
    private static function getInsertStatement(array $options) {
        $keys = array();
        $values = array();
        foreach ($options as $k=>$v) {
            $value = "'${v}'";
            $key = $k;
            if (substr($k, 0, 1) == '!') {
                $key = substr($k, 1);
                $value = $v;
            }

            $keys[] = $key;
            $values[] = $value;
        }

        $keys = implode(', ', $keys);
        $values = implode(', ', $values);

        return "INSERT INTO ".TABLE_CONFIGURATION." (${keys}) VALUES (${values})";
    }

    /**
     * JS validation which does error-checking of data-entry if this module is selected for use
     */
    public function javascript_validation() {
        return false;
    }

    /**
     * Evaluate the Credit Card Type for acceptance and the validity of the Credit Card Number & Expiration Date
     */
    public function pre_confirmation_check() {
        return false;
    }

    /**
     * Display Credit Card Information on the Checkout Confirmation Page
     */
    public function confirmation() {
        return false;
    }

    public function selection() {
        return array('id' => $this->code,
            'module' => MODULE_PAYMENT_SPECTROCOIN_TEXT_CHECKOUT
        );
    }

    public function process_button() {
        return false;
    }

    public function before_process() {
        return false;
    }


    public static function getSpectrocoinClient(&$options) {
        global $db;

        // Default values
        $apiUrl = '';
        $apiProjectId = '';
        $apiuserId = '';
        $apiPrivateKey = '';
        $receiveCurrency = 'BTC';
        $paymentMethod = '';

        // Set variables
        $query = $db->Execute("SELECT configuration_key, configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key LIKE 'MODULE_PAYMENT_SPECTROCOIN_%'");
        $statusOptions = array();
        foreach ($query as $row) {
            switch ($row['configuration_key']) {
                case 'MODULE_PAYMENT_SPECTROCOIN_API_URL':
                    $apiUrl = $row['configuration_value'];
                    break;
                case 'MODULE_PAYMENT_SPECTROCOIN_PROJECT_ID':
                    $apiProjectId = $row['configuration_value'];
                    break;
                case 'MODULE_PAYMENT_SPECTROCOIN_MERCHANT_ID':
                    $apiuserId = $row['configuration_value'];
                    break;
                case 'MODULE_PAYMENT_SPECTROCOIN_PRIVATE_KEY':
                    $apiPrivateKey = $row['configuration_value'];
                    break;
                case 'MODULE_PAYMENT_SPECTROCOIN_PAYMENT_METHOD':
                    $paymentMethod = $row['configuration_value'];
                    break;
                default:
                    foreach (self::getSpectrocoinStatuses() as $key => $option) {
                        if ($option['optionName'] == $row['configuration_key']) {
                            $statusOptions[$key] = array(
                                'inner_status' => $row['configuration_value'],
                                'status' => $option
                            );
                        }
                    }
            }
        }

        // Initialize client
        $client = new SCMerchantClient($apiUrl, $apiuserId, $apiProjectId, false);
        $client->setPrivateMerchantKey($apiPrivateKey);

        $options = array(
            'apiUrl' => $apiUrl,
            'apiProjectId' => $apiProjectId,
            'apiuserId' => $apiuserId,
            'apiPrivateKey' => $apiPrivateKey,
            'receiveCurrency' => $receiveCurrency,
            'paymentMethod' => $paymentMethod,
            'statuses' => $statusOptions
        );



        return $client;
    }

    public function after_process() {
        global $insert_id, $db, $order, $messageStack;

        $info = $order->info;

        $options = array();
        $client = self::getSpectrocoinClient($options);


        // Create new request
        $callback = zen_href_link('spectrocoin_callback.php', $parameters='', $connection='NONSSL', $add_session_id=true, $search_engine_safe=true, $static=true );

        if ($options['paymentMethod'] == 'shop') {
            $request = new CreateOrderRequest(
                $insert_id,
                strtoupper($info['currency']),
                $info['total'],
                strtoupper($info['currency']),
                null,
                "Order #" . $insert_id,
                isset($_SESSION['languages_code']) ? $_SESSION['languages_code'] : 'en',
                $callback . "?type=callback",
                $callback . "?type=success",
                $callback . "?type=fail"
            );
        }
        else {
            $request = new CreateOrderRequest(
                $insert_id,
                strtoupper($info['currency']),
                null,
                strtoupper($info['currency']),
                $info['total'],
                "Order #" . $insert_id,
                isset($_SESSION['languages_code']) ? $_SESSION['languages_code'] : 'en',
                $callback . "?type=callback",
                $callback . "?type=success",
                $callback . "?type=fail"
            );
        }

        $response = $client->createOrder($request);

        if($response instanceof CreateOrderResponse) {
            $_SESSION['cart']->reset(true);
            $_SESSION['SPECTROCOIN_order_id'] = $insert_id;
            zen_redirect($response->getRedirectUrl());
            return false;
        }
        elseif($response instanceof ApiError) {
            $messageStack->add_session("SpectroCoin error: " . $response->getCode() . ": " . $response->getMessage());
        }
        else {
            $messageStack->add_session("Unknown SpectroCoin error.");
        }

        return false;
    }

    public function check() {
        global $db;
        if (!isset($this->_check)) {
            $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_SPECTROCOIN_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }


    public function install() {
        global $db, $messageStack;

        if (defined('MODULE_PAYMENT_SPECTROCOIN_STATUS')) {
            $messageStack->add_session('SPECTROCOIN module already installed.', 'error');
            zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=SPECTROCOIN', 'NONSSL'));
            return 'failed';
        }

        foreach (self::getConfigurationOptions() as $option) {
            $sql = self::getInsertStatement($option);
            $db->Execute($sql);
        }
    }


    public function remove() {
        global $db;
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key LIKE 'MODULE\_PAYMENT\_SPECTROCOIN\_%'");
    }


    public function keys() {
        $options = array(
            'MODULE_PAYMENT_SPECTROCOIN_STATUS',
            'MODULE_PAYMENT_SPECTROCOIN_API_URL',
            'MODULE_PAYMENT_SPECTROCOIN_PROJECT_ID',
            'MODULE_PAYMENT_SPECTROCOIN_MERCHANT_ID',
            'MODULE_PAYMENT_SPECTROCOIN_PRIVATE_KEY',
            'MODULE_PAYMENT_SPECTROCOIN_PAYMENT_METHOD'
        );

        foreach (self::getSpectrocoinStatuses() as $status) {
            $options[] = $status['optionName'];
        }

        return $options;
    }

}