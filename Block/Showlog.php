<?php

class Paymentgateway_Payatall_Block_Showlog extends Mage_Adminhtml_Block_Widget_Grid_Container {

	public function __construct() {
		$this->_controller = 'showlog';
		$this->_blockGroup = 'payatall';
		$this->_headerText = Mage::helper('payatall')->__('Pay@all Transaction Details');
		$this->_addButtonLabel = '';
		parent::__construct();
		$this->_removeButton('add');
	}
}

