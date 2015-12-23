<?php

class Paymentgateway_Payatall_Model_Payment extends Mage_Payment_Model_Method_Abstract {
    protected $_code = 'payatall';
    protected $_paymentMethod = 'payment';

    protected $_isGateway               = false;
    protected $_canAuthorize            = false;
    protected $_canCapture              = false;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;

    protected $_defaultLocale = 'en';

    public function getServiceId() {
        return(Mage::getStoreConfig('payment/payatall/service_id'));
    }

    public function getMid() {
        return(Mage::getStoreConfig('payment/payatall/payatall_mid'));
    }
    
    public function getPostURL() {
        if (Mage::getStoreConfig('payment/payatall/test_mode') == 1) {
            return(Mage::getStoreConfig('payment/payatall/test_post_url'));
        } else {
            return(Mage::getStoreConfig('payment/payatall/post_url'));
        }
    }

    public function getServiceInquiryURL() {
        if (Mage::getStoreConfig('payment/payatall/test_mode') == 1) {
            return(Mage::getStoreConfig('payment/payatall/test_service_inquiry'));
        } else {
            return(Mage::getStoreConfig('payment/payatall/service_inquiry'));
        }
    }

    public function getLanguage() {
        return(Mage::getStoreConfig('payment/payatall/payatall_lang'));
    }

    public function getCurrencyType() {
        return(Mage::getStoreConfig('payment/payatall/currency_type'));
    }

    public function getRespURLConfirm() {
        return(Mage::getStoreConfig('payment/payatall/response_url_confirm'));
    }

    public function getRespURLCancel() {
        return(Mage::getStoreConfig('payment/payatall/response_url_cancel'));
    }

    public function getRespURLBack() {
        return(Mage::getStoreConfig('payment/payatall/response_url_back'));
    }
    
    public function getExpiryDays() {
        return((int) Mage::getStoreConfig('payment/payatall/expiry_days'));
    }
    
    public function getDescription() {
        return(Mage::getStoreConfig('payment/payatall/description'));
    }
    
    public function getMessageOne() {
        return(Mage::getStoreConfig('payment/payatall/message_slip1'));
    }
    
    public function getMessageTwo() {
        return(Mage::getStoreConfig('payment/payatall/message_slip2'));
    }
    
    public function getPayatallUsername() {
        return(Mage::getStoreConfig('payment/payatall/payatall_username'));
    }
    
    public function getPayatallPassword() {
        return(Mage::getStoreConfig('payment/payatall/payatall_password'));
    }

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('payatall/payment/redirect', array('_secure' => true));
    }

}
