<?php
$installer = $this;
$installer->startSetup();

$sql=<<<SQLTEXT
CREATE TABLE IF NOT EXISTS `{$this->getTable('paymentgateway_payatall')}` (
	`payatall_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`customer_name` varchar(255) NOT NULL,
	`customer_phone` varchar(100) NOT NULL,
	`customer_email` varchar(255) NOT NULL,
	`invoice_no` varchar(20) NOT NULL,
	`amount` float unsigned NOT NULL,
	`expiry_date` datetime NOT NULL,
	`ptransid` bigint(20) unsigned DEFAULT NULL,
	`payment_status` varchar(32) DEFAULT NULL,
	`payatall_payment_date` datetime DEFAULT NULL,
	`payatall_process_date` datetime DEFAULT NULL,
	`payatall_url` varchar(250) DEFAULT NULL,
	`payatall_paycode` bigint(20) DEFAULT NULL,
	`payatall_redirect_ip` varchar(50) NOT NULL,
	`added_on` datetime NOT NULL,
	`last_modified_on` datetime DEFAULT NULL,
	PRIMARY KEY (`payatall_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SQLTEXT;

$installer->run($sql);
$installer->endSetup();
