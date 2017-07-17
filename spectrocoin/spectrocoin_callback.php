<?php
require_once 'includes/application_top.php';
require_once 'includes/modules/payment/spectrocoin.php';

$type = $_GET['type'];

if (!in_array($type, array('callback', 'success', 'fail'))) {
    header("HTTP/1.1 404 Page not found");
    exit();
}

switch ($type) {
    case 'callback':
        $options = array();

        $client = spectrocoin::getSpectrocoinClient($options);
        $callback = $client->parseCreateOrderCallback($_REQUEST);

        $newStatus = $options['statuses'][(int) $callback->getStatus()];
        $orderId = (int) $_REQUEST['orderId'];

        $db->Execute("update ". TABLE_ORDERS. " set orders_status = " . $newStatus['inner_status'] . " where orders_id = ". $orderId);
        $db->Execute(
            "insert into ". TABLE_ORDERS_STATUS_HISTORY
                    . " (orders_id, orders_status_id, date_added) values ("
                    .implode(',', array($orderId, $newStatus['inner_status'], 'now()'))
                    .")"
        );

        echo '*ok*';
        exit();
        break;
    case 'success':
        header('Location:'.$_SERVER['HTTP_HOST'].'/index.php?main_page=account_history');
        echo 'Order was paid successful';
        exit();
    case 'fail':
        header('Location:/index.php?main_page=account_history');
        echo 'There was a payment error';
        exit();
    default:
        header("HTTP/1.1 404 Page not found");
}
