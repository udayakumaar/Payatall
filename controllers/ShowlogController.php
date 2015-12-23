<?php

class Paymentgateway_Payatall_ShowlogController extends Mage_Adminhtml_Controller_Action {
    /**
     * Acl check for admin
     * @return bool
     */
    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed("payatall/showlog");
    }

    public function indexAction() {
            $this->_title($this->__('Payatall Show Log'));

            $this->_initAction();

            $this->loadLayout();
            $block = Mage::app()->getLayout()->createBlock('payatall/showlog');
            $this->getLayout()->getBlock('content')->append($block);
            $this->renderLayout();
    }

    /**
    * Init action
    */
    protected function _initAction() {
            $this->loadLayout()->_setActiveMenu('payatall/showlog')->_addBreadcrumb(Mage::helper('adminhtml')->__('Payatall Show Log'),Mage::helper('adminhtml')->__('Payatall Show Log'));
            return $this;
    }

    /**
    * Export order grid to CSV format
    */
    public function exportCsvAction() {
            $file_name  = 'payatall_show_log.csv';
            $grid       = $this->getLayout()->createBlock('payatall/showlog_grid');
            $this->_prepareDownloadResponse($file_name, $grid->getCsvFile());
    } 

    /**
    *  Export order grid to Excel XML format
    */
    public function exportExcelAction() {
            $file_name  = 'payatall_show_log.xml';
            $grid       = $this->getLayout()->createBlock('payatall/showlog_grid');
            $this->_prepareDownloadResponse( $file_name, $grid->getExcelFile($file_name));
    }
}
