<?php

/**
 * Copyright since 2024 Storno
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author    Storno <support@storno.ro>
 * @copyright Since 2024 Storno
 * @license   https://opensource.org/licenses/MIT MIT License
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
