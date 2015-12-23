<?php

class Paymentgateway_Payatall_PaymentController extends Mage_Core_Controller_Front_Action {

    public function redirectAction() {
        try {
            $payatall = array();

            $now = Mage::getModel('core/date')->timestamp(time());

            $order = Mage::getModel('sales/order');
            $payatall['invoice_no'] = Mage::getSingleton('checkout/session')->getLastRealOrderId();
            
            $payatallModel = Mage::getModel('payatall/payatall')->load($payatall['invoice_no'], 'invoice_no');
            if ($payatallModel->getId() === NULL) { // To avoid refresh duplicate entry in the redirect
                $order->loadByIncrementId($payatall['invoice_no']);
                $payment = $order->getPayment()->getMethodInstance();

                // Get Payatall Parameters from Order
                $payatall['customer_name'] = $order->getCustomerName();
                $payatall['customer_phone'] = $order->getBillingAddress()->telephone;
                $payatall['customer_email'] = $order->customer_email;
                $payatall['amount'] = round(ceil($order->base_grand_total), 2);
                $payatall['expiry_date'] = date('Y-m-d H:i:s', $now + $payment->getExpiryDays() * 24 * 60 * 60);

                // Get Payatall Parameters from Admin Configuration
                $payatall['service_id'] = $payment->getServiceId();
                $payatall['payatall_mid'] = $payment->getMid();
                $payatall['post_url'] = $payment->getPostURL();
                $payatall['payatall_lang'] = $payment->getLanguage();
                $payatall['currency_type'] = $payment->getCurrencyType();
                $payatall['response_url_confirm'] = $payment->getRespURLConfirm();
                $payatall['response_url_cancel'] = $payment->getRespURLCancel();
                $payatall['response_url_back'] = $payment->getRespURLBack();
                $payatall['description'] = $payment->getDescription() . "Order No.: " . $payatall['invoice_no'];
                $payatall['message_slip1'] = $payment->getMessageOne();
                $payatall['message_slip2'] = $payment->getMessageTwo();

                // Insert data into paymentgateway_payatall Table
                Mage::getModel('payatall/payatall')
                        ->setAmount($payatall['amount'])
                        ->setInvoiceNo($payatall['invoice_no'])
                        ->setCustomerName(addslashes($payatall['customer_name']))
                        ->setCustomerPhone($payatall['customer_phone'])
                        ->setCustomerEmail($payatall['customer_email'])
                        ->setPaymentStatus('Waiting for Payment')
                        ->setExpiryDate($payatall['expiry_date'])
                        ->setAddedOn(date('Y-m-d H:i:s', $now))
                        ->setPayatallRedirectIp($this->get_user_ip())
                        ->save();
                
                if ($order->getState() != Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {
                    $order->setState(
                        Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, Mage::helper('payatall')->__('Customer redirected to Payatall')
                    )->save();
                }
            }
            
            // Add array to registry which can be accessed in view file
            Mage::register('payatall', $payatall);
            
            $this->loadLayout();
            $this->getLayout()->getBlock('root')->setTemplate('page/1column.phtml');
            $block = $this->getLayout()->createBlock('Mage_Core_Block_Template', 'payatall', array('template' => 'payatall/redirect.phtml'));
            $this->getLayout()->getBlock('content')->insert($block);
            $this->renderLayout();
            return;
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
        } catch (Exception $e) {
            $this->log('Payatall error: ' . $e->getMessage());
            Mage::logException($e);
        }

        Mage::getSingleton('checkout/session')->addNotice(Mage::helper('payatall')->__('Payment cannot proceed. Please try again.'));
        $this->_redirect('checkout/cart');
    }

    public function responseAction() {
        $responseReturnValue = 'F';

        $postArray = array();
        if ($this->getRequest()->isPost()) {
            $postArray = $this->getRequest()->getPost();
            if (count($postArray) > 0) {
                foreach($postArray as $key => $values) {
                    $this->log($key . " = " . $values);
                    if ($key == 'status' and $values == 'S') {
                        $responseReturnValue = 'S';
                    }
                }

                $this->setPayatallStatus($postArray);
                $this->setOrderStatus($postArray);
                $this->log('RESPONSEAction: Order ' . $postArray['inv'] . ' status as -' . $postArray['status'] . '- by Payatall.');
            }
        }

        echo $responseReturnValue;
    }

    public function cancelAction() {
        $payatall = array();
        $payatall['inv'] = 0;
        
        $order = Mage::getModel('sales/order');
        $payatall['inv'] = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $payatall['status'] = 'C';
        
        if ($payatall['inv'] !== NULL && $payatall['inv'] > 0) {
            $this->setPayatallStatus($payatall);
            $this->setOrderStatus($payatall);
            $this->log('CANCELAction: Invoice ' . $payatall['inv'] . ' is cancelled by Payatall.');
        }
        
        Mage::getSingleton('checkout/session')->addNotice(Mage::helper('payatall')->__('Error occurred during the payment process. Please try again.'));
        $this->_redirect('checkout/cart');
    }

    public function backAction() {
        // Clear the cart before redirection...
        $cart = Mage::getModel('checkout/cart');                
        $cart->truncate();
        $cart->save();
        $this->_redirect('checkout-finish/index/success');
    }
    
    public function serviceAction() {
        $si = Mage::getModel('payatall/serviceinquiry');
        $si->run();
    }
    
    protected function setPayatallStatus($payatall = array()) {
        if (array_key_exists('inv', $payatall) && array_key_exists('status', $payatall)) {
            $now = Mage::getModel('core/date')->timestamp(time());

            $ptransid = array_key_exists('ptransid', $payatall)?$payatall['ptransid']:NULL;
            $payment_date = array_key_exists('payment_date', $payatall)?$payatall['payment_date']:NULL;
            $process_date = array_key_exists('process_date', $payatall)?$payatall['process_date']:NULL;

            $payatallstatus = array(
                'A' => 'Waiting for Payment',
                'S' => 'Payment Done',
                'E' => 'Transaction Expired',
                'C' => 'Cancel',
                'Z' => 'Invalid Data',
                'N' => 'Invoice Not Found'
            );

            if (array_key_exists($payatall['status'], $payatallstatus)) {
                $payatallModel = Mage::getModel('payatall/payatall')->load($payatall['inv'], 'invoice_no');
                if ($payatallModel->getId() !== NULL) {
                    if ($payatallModel['payment_status'] != $payatallstatus[$payatall['status']]) {
                        $payatallModel->setPaymentStatus($payatallstatus[$payatall['status']])
                                ->setPtransid($ptransid)
                                ->setPayatallPaymentDate($payment_date)
                                ->setPayatallProcessDate($process_date)
                                ->setLastModifiedOn(date('Y-m-d H:i:s', $now))
                                ->save();
                    }
                }
            }
        }
    }

    protected function setOrderStatus($payatall = array()) {
        if (array_key_exists('inv', $payatall) && array_key_exists('status', $payatall)) {
            $ptransid = array_key_exists('ptransid', $payatall)?$payatall['ptransid'] : '';
            $orderModel = Mage::getModel('sales/order')->loadByIncrementId($payatall['inv']);
            
            $payatallOrderDetails = array(
                'S' => array('status' => Mage_Sales_Model_Order::STATE_PROCESSING, 'comment' => 'Paid by Customer. TransID: ' . $ptransid, 'sendEmail' => 1),
                'C' => array('status' => Mage_Sales_Model_Order::STATE_CANCELED, 'comment' => 'Payment cancelled in Payatall. Order cancelled.', 'sendEmail' => 0),
                'E' => array('status' => Mage_Sales_Model_Order::STATE_CANCELED, 'comment' => 'Transaction expired in Payatall. Order cancelled.', 'sendEmail' => 0),
                'Z' => array('status' => Mage_Sales_Model_Order::STATE_CANCELED, 'comment' => 'Invalid data. Order cancelled.', 'sendEmail' => 0),
                'A' => array('status' => Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, 'comment' => 'Payment pending from Customer side.', 'sendEmail' => 0),
                'N' => array('status' => Mage_Sales_Model_Order::STATE_CANCELED, 'comment' => 'Data not accepted by Payatall. Order cancelled.', 'sendEmail' => 0),
            );
            
            if (array_key_exists($payatall['status'], $payatallOrderDetails)) {
                if ($orderModel->getId() !== NULL) {
                    if ($orderModel->getState() != $payatallOrderDetails[$payatall['status']]['status']) {
                        $orderModel->setState(
                            $payatallOrderDetails[$payatall['status']]['status'], true, Mage::helper('payatall')->__($payatallOrderDetails[$payatall['status']]['comment'])
                        )->save();

                        if ($payatallOrderDetails[$payatall['status']]['sendEmail'] == 1) {
                            $orderModel->sendNewOrderEmail();
                            $orderModel->setEmailSent(true);
                            $orderModel->save();                        
                        }
                    }
                }
            } else {
                $this->log('Invalid status captured for ' . $payatall['inv']);
            }
        }
    }

    private function get_user_ip() {
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
                return $_SERVER["HTTP_X_FORWARDED_FOR"];

            if (isset($_SERVER["HTTP_CLIENT_IP"]))
                return $_SERVER["HTTP_CLIENT_IP"];

            return $_SERVER["REMOTE_ADDR"];
        }

        if (getenv('HTTP_X_FORWARDED_FOR'))
            return getenv('HTTP_X_FORWARDED_FOR');

        if (getenv('HTTP_CLIENT_IP'))
            return getenv('HTTP_CLIENT_IP');

        return getenv('REMOTE_ADDR');
    }
    
    public function log($logMessage) {
        $logFile = Mage::getBaseDir('media') . '/log/payatall.log';
        $log = fopen($logFile, 'a');
        fwrite($log, date('Y-m-d H:i:s') . ' ' . $logMessage . PHP_EOL);
        fclose($log);
    }
        
}

