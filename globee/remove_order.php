<?php

/**
 * @param string $orderId
 * @param boolean $restock
 */
function tep_remove_order($orderId, $restock = false)
{
    if ($restock == 'on') {
        while ($order = tep_db_fetch_array(tep_db_query(
            "select products_id, products_quantity from " . TABLE_ORDERS_PRODUCTS
            . " where orders_id = '" . (int)$orderId . "'"
        ))) {
            tep_db_query("update " . TABLE_PRODUCTS
                . " set products_quantity = products_quantity + " . $order['products_quantity']
                . ", products_ordered = products_ordered - " . $order['products_quantity']
                . " where products_id = '" . (int)$order['products_id'] . "'");
        }
    }

    tep_db_query("delete from " . TABLE_ORDERS . " where orders_id = '" . (int)$orderId . "'");
    tep_db_query("delete from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$orderId . "'");
    tep_db_query("delete from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" . (int)$orderId . "'");
    tep_db_query("delete from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . (int)$orderId . "'");
    tep_db_query("delete from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$orderId . "'");
}