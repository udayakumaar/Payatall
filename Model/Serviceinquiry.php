<?php

//error_reporting(E_ALL | E_STRICT);
//ini_set('display_errors',1);
//
//define('MAGENTO_ROOT', getcwd());
//
//$mageFilename = MAGENTO_ROOT . '/../app/Mage.php';
//require_once $mageFilename;
//
//Mage::app('admin')->setUseSessionInUrl(false);

class Paymentgateway_Payatall_Model_Serviceinquiry {
    protected $_orderdetails;
    protected $_payatalldetails;
    protected $_payatallPOSTfields;
    protected $serviceJSONoutput;
    protected $payatallch;
    protected $_now;

    protected $orderModel;
    protected $payatallModel;
    
    public function run() {
        try {
            $this->_now = Mage::getModel('core/date')->timestamp(time());
            $this->_setPayatallOrderDetails();
            $this->setPayatallPostFields();

            $this->orderModel = Mage::getModel('sales/order');
            $this->payatallch = curl_init();

            if (Mage::getStoreConfig('payment/payatall/test_mode') == 1) {
                curl_setopt($this->payatallch, CURLOPT_URL, Mage::getStoreConfig('payment/payatall/test_service_inquiry'));
            } else {
                curl_setopt($this->payatallch, CURLOPT_URL, Mage::getStoreConfig('payment/payatall/service_inquiry'));
            }

            $payatalls = Mage::getModel('payatall/payatall')->getCollection()
                            ->addFieldToFilter(
                                array('payment_status'),
                                array(
                                    array('Waiting for Payment')
                                )
                            )->addFieldToFilter(
                                array('DATE(expiry_date)'),
                                array(
                                    array('lt' => date('Y-m-d'))
                                )
                            );

            foreach($payatalls as $payatall) {
                $this->payatallModel = $payatall;
                $this->getCurlServiceInquiry();
                
                $this->log('Invoice: ' . $this->serviceJSONoutput->inv);
                $this->log('Status: ' . $this->serviceJSONoutput->status);
                
                if ($this->serviceJSONoutput !== false) {
                    $this->setPayatallModel();
                    $this->setOrderModel();
                }
            }
            curl_close($this->payatallch);
        } catch (Mage_Core_Exception $e) {
            $this->log('Error: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->log('Error: ' . $e->getMessage());
            Mage::logException($e);
        }
    }
    
    public function getCurlServiceInquiry() {
        if (count($this->payatallModel) > 0) {
            $this->_payatallPOSTfields['inv'] = urlencode($this->payatallModel['invoice_no']);
            $this->_payatallPOSTfields['amt'] = urlencode($this->payatallModel['amount']);
            
            $payatallFieldsString = '';
            foreach($this->_payatallPOSTfields as $fkey => $fvalue) {
                $payatallFieldsString .= $fkey.'='.$fvalue.'&';
            }

            $payatallFieldsString = rtrim($payatallFieldsString, '&');

            curl_setopt($this->payatallch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->payatallch, CURLOPT_POST, count($this->_payatallPOSTfields));
            curl_setopt($this->payatallch, CURLOPT_POSTFIELDS, $payatallFieldsString);

            $this->serviceJSONoutput = json_decode(curl_exec($this->payatallch));
        } else {
            $this->serviceJSONoutput = false;
        }
    }
    
    public function setPayatallPostFields() {
        $this->_payatallPOSTfields = array(
            'mid' => urlencode(Mage::getStoreConfig('payment/payatall/payatall_mid')),
            'serviceid' => urlencode(Mage::getStoreConfig('payment/payatall/service_id')),
            'username' => urlencode(Mage::getStoreConfig('payment/payatall/payatall_username')),
            'password' => urlencode(Mage::getStoreConfig('payment/payatall/payatall_password')),
        );
    }
    
    public function setPayatallModel() {
        $ptransid = $this->serviceJSONoutput->ptransid?$this->serviceJSONoutput->ptransid:NULL;
        $payment_date = NULL;
        if ($this->serviceJSONoutput->payment_date !== NULL && $this->serviceJSONoutput->payment_date != "")
            $payment_date = $this->serviceJSONoutput->payment_date;
        
        $process_date = NULL;
        if ($this->serviceJSONoutput->process_date !== NULL && $this->serviceJSONoutput->process_date != "")
            $process_date = $this->serviceJSONoutput->process_date;
        
        if (array_key_exists($this->serviceJSONoutput->status, $this->_payatalldetails)) {
            if ($this->payatallModel['payment_status'] != $this->_payatalldetails[$this->serviceJSONoutput->status]) {
                /* Update Paymentgateway_payatall table */
                $this->payatallModel->setPaymentStatus($this->_payatalldetails[$this->serviceJSONoutput->status])
                        ->setPtransid($ptransid)
                        ->setPayatallPaymentDate($payment_date)
                        ->setPayatallProcessDate($process_date)
                        ->setLastModifiedOn(date('Y-m-d H:i:s', $this->_now))
                        ->setPayatallUrl($this->serviceJSONoutput->url)
                        ->setPayatallPaycode($this->serviceJSONoutput->paycode)
                        ->save();
                if ($this->serviceJSONoutput->status == 'S') {
                    $this->_orderdetails['S']['comment'] = 'Paid by Customer. TransID: ' . $ptransid;
                }
            }
        }
    }
    
    public function setOrderModel() {
        if (array_key_exists($this->serviceJSONoutput->status, $this->_orderdetails)) {
            $this->orderModel->loadByIncrementId($this->payatallModel['invoice_no']);
            if ($this->orderModel->getId() !== NULL) {
                if ($this->orderModel->getState() != $this->_orderdetails[$this->serviceJSONoutput->status]['status']) {
                    $this->orderModel->setState(
                        $this->_orderdetails[$this->serviceJSONoutput->status]['status'], true, Mage::helper('payatall')->__($this->_orderdetails[$this->serviceJSONoutput->status]['comment'])
                    )->save();

                    if ($this->_orderdetails[$this->serviceJSONoutput->status]['sendEmail'] == 1) {
                        $this->orderModel->sendNewOrderEmail();
                        $this->orderModel->setEmailSent(true);
                        $this->orderModel->save();
                    }
                }
            }
        }
    }
    
    protected function _setPayatallOrderDetails() {
        $this->_payatalldetails = array(
            'A' => 'Waiting for Payment',
            'S' => 'Payment Done',
            'E' => 'Transaction Expired',
            'C' => 'Cancel',
            'Z' => 'Invalid Data',
            'N' => 'Invoice Not Found'
        );

        $this->_orderdetails = array(
            'S' => array('status' => Mage_Sales_Model_Order::STATE_PROCESSING, 'comment' => 'Paid by Customer. TransID: ', 'sendEmail' => 1),
            'C' => array('status' => Mage_Sales_Model_Order::STATE_CANCELED, 'comment' => 'Payment cancelled in Payatall. Order cancelled.', 'sendEmail' => 0),
            'E' => array('status' => Mage_Sales_Model_Order::STATE_CANCELED, 'comment' => 'Transaction expired in Payatall. Order cancelled.', 'sendEmail' => 0),
            'Z' => array('status' => Mage_Sales_Model_Order::STATE_CANCELED, 'comment' => 'Invalid data. Order cancelled.', 'sendEmail' => 0),
            'A' => array('status' => Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'comment' => 'Payment pending from Customer side.', 'sendEmail' => 0),
            'N' => array('status' => Mage_Sales_Model_Order::STATE_CANCELED, 'comment' => 'Data not accepted by Payatall. Order cancelled.', 'sendEmail' => 0),
        );
    }
    
    public function log($logMessage) {
        $logFile = Mage::getBaseDir('media') . '/log/serviceinquiry_payatall.log';
        $log = fopen($logFile, 'a');
        fwrite($log, date('Y-m-d H:i:s') . ' ' . $logMessage . PHP_EOL);
        fclose($log);
    }

}
