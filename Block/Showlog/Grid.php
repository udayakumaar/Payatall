<?php

class Paymentgateway_Payatall_Block_Showlog_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId("payatallshowlogGrid");
        $this->setDefaultSort("payatall_id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel("payatall/payatall")->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        // Add columns to grid
        $this->addColumn('payatall_id', array(
                'header' => Mage::helper('payatall')->__('ID'),
                'align' => 'right',
                'width' => '50px',
                'type' => 'number',
                'index' => 'payatall_id',
        ));
        $this->addColumn('customer_name', array(
                'header' => Mage::helper('payatall')->__('Customer Name'),
                'type' => 'text',
                'index' => 'customer_name',
        ));
        $this->addColumn('customer_email', array(
                'header' => Mage::helper('payatall')->__('Email'),
                'type' => 'text',
                'index' => 'customer_email',
        ));
        $this->addColumn('customer_phone', array(
                'header' => Mage::helper('payatall')->__('Phone'),
                'type' => 'text',
                'index' => 'customer_phone',
        ));
        $this->addColumn('invoice_no', array(
                'header' => Mage::helper('payatall')->__('Order No'),
                'type' => 'text',
                'index' => 'invoice_no',
        ));
        $this->addColumn('amount', array(
                'header' => Mage::helper('payatall')->__('Amount'),
                'type' => 'text',
                'index' => 'amount',
        ));
        $this->addColumn('ptransid', array(
                'header' => Mage::helper('payatall')->__('Trans ID'),
                'type' => 'text',
                'index' => 'ptransid',
        ));
        $this->addColumn('payment_status', array(
                'header' => Mage::helper('payatall')->__('Payment Status'),
                'type' => 'text',
                'index' => 'payment_status',
        ));
        $this->addColumn('expiry_date', array(
                'header' => Mage::helper('payatall')->__('Slip Expiry Date'),
                'type' => 'datetime',
                'index' => 'expiry_date',
        ));
        $this->addColumn('payatall_payment_date', array(
                'header' => Mage::helper('payatall')->__('Payment Date'),
                'type' => 'datetime',
                'index' => 'payatall_payment_date',
        ));
        $this->addColumn('payatall_process_date', array(
                'header' => Mage::helper('payatall')->__('Process Date'),
                'type' => 'datetime',
                'index' => 'payatall_process_date',
        ));
        $this->addColumn('added_on', array(
                'header' => Mage::helper('payatall')->__('Added On'),
                'type' => 'datetime',
                'index' => 'added_on',
        ));
        $this->addColumn('last_modified_on', array(
                'header' => Mage::helper('payatall')->__('Last Modified On'),
                'type' => 'datetime',
                'index' => 'last_modified_on',
        ));
        $this->addColumn('payatall_redirect_ip', array(
                'header' => Mage::helper('payatall')->__('Redirect IP'),
                'type' => 'text',
                'index' => 'payatall_redirect_ip',
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV')); 
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
        return '#';
    }
}

