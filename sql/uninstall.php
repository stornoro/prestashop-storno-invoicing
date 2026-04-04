<?php
/**
 * Database table removal
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'storno_invoices`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'storno_webhook_log`';

foreach ($sql as $query) {
    Db::getInstance()->execute($query);
}
