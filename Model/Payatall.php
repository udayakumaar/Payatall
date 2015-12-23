<?php

class Paymentgateway_Payatall_Model_Payatall extends Mage_Core_Model_Abstract {
    protected function _construct() {
        $this->_init('payatall/payatall');
    }
}
