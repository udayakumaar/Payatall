<?php

class Paymentgateway_Payatall_Model_Mysql4_Payatall extends Mage_Core_Model_Mysql4_Abstract {
	protected function _construct() {
		$this->_init('payatall/payatall', 'payatall_id');
	}
}
