<?php
/**
 * Database table installation
 *
 * @author  Storno <support@storno.ro>
 * @license MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

// Main mapping table: PrestaShop orders ↔ Storno invoices
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'storno_invoices` (
    `id_storno_invoice` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_order` INT UNSIGNED NOT NULL,
    `storno_invoice_id` VARCHAR(36) NOT NULL DEFAULT "",
    `storno_invoice_number` VARCHAR(50) NOT NULL DEFAULT "",
    `storno_status` VARCHAR(30) NOT NULL DEFAULT "pending",
    `storno_error` TEXT,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_storno_invoice`),
    UNIQUE KEY `idx_order` (`id_order`),
    KEY `idx_storno_id` (`storno_invoice_id`),
    KEY `idx_status` (`storno_status`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Webhook event log for debugging
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'storno_webhook_log` (
    `id_log` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `event` VARCHAR(255) NOT NULL,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_log`),
    KEY `idx_order` (`id_order`),
    KEY `idx_date` (`date_add`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}
