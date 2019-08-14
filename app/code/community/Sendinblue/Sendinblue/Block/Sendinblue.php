<?php
/**
* @author Sendinblue plateform <contact@sendinblue.com>
* @copyright  2013-2014 Sendinblue
* URL:  https:www.sendinblue.com
* Do not edit or add to this file if you wish to upgrade Sendinblue Magento plugin to newer
* versions in the future. If you wish to customize Sendinblue magento plugin for your
* needs then we can't provide a technical support.
**/

class Sendinblue_Sendinblue_Block_Sendinblue extends Mage_Core_Block_Template
{
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }  
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }
    
    protected function _toHtml()
    {
        $sendinblueData = Mage::getModel('sendinblue/sendinblue');
        $getEnableStatus = $sendinblueData->getEnableStatus();  
        $getTrackingStatus = $sendinblueData->getTrackingStatus();
        $getOrderStatus = $sendinblueData->getOrderSmsStatus();
        $getUserLists = $sendinblueData->getUserlists();
        $smtpData = $sendinblueData->trackingSmtp();

        $attributesName = $sendinblueData->allAttributesName();
        $afterArrayMerge = array();

        $lastOrderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $order = Mage::getModel('sales/order')->loadByIncrementId($lastOrderId);
        $dataDisplay = $order->getBillingAddress()->getData();
        $orderData = $order->getData();
        $custmerData = $customer->getData();
        $localeCode = Mage::app()->getLocale()->getLocaleCode();

        if($getEnableStatus && $getOrderStatus) {
            if (!empty($dataDisplay['telephone']) && !empty($dataDisplay['country_id'])) {
                $countryCode = $sendinblueData->getCountryCode($dataDisplay['country_id']);                 
                $dataDisplay['telephone'] = $sendinblueData->checkMobileNumber($dataDisplay['telephone'],$countryCode);
            }
            $referenceNumber = $orderData['increment_id'];
            $orderprice = $orderData['grand_total'];
            $currencyCode = $orderData['base_currency_code'];
            if ($localeCode == 'fr_FR') {
                $orderCreatedDate = date('d/m/Y', strtotime($orderData['created_at']));
            }
            else {
                $orderCreatedDate = date('m/d/Y', strtotime($orderData['created_at']));
            }

            $totalPay = $orderprice.' '.$currencyCode;
            $msgbody = $sendinblueData->getSendSmsmOrderMessage();
            $firstName = str_replace('{first_name}', $dataDisplay['firstname'], $msgbody);
            $lastName = str_replace('{last_name}', $dataDisplay['lastname']."\r\n", $firstName);
            $procuctPrice = str_replace('{order_price}', $totalPay, $lastName);
            $orderDate = str_replace('{order_date}', $orderCreatedDate."\r\n", $procuctPrice);
            $msgbody = str_replace('{order_reference}', $referenceNumber, $orderDate);

            $sendSmsData = array();
            $sendSmsData['to'] = $dataDisplay['telephone'];
            $sendSmsData['from'] = $sendinblueData->getSendSmsOrderSubject();
            $sendSmsData['text'] = $msgbody;
            $responce = $sendinblueData->sendSmsApi($sendSmsData);
        }
        $allData = array_merge($dataDisplay, $custmerData);
        $afterArrayMerge = $sendinblueData->mergeMyArray($attributesName, $allData);
        $client = (!empty($custmerData['firstname'])|| !empty($custmerData['firstname'])) ? 1 : 0 ;
        
        $afterArrayMerge['CLIENT'] = $client;
        $email = $custmerData['email']; // for email address
        $costomerInformation = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
        $nlStatus = $costomerInformation->getStatus();

        if ($nlStatus == 1) {
            $sendinblueData->emailAdd($email, $afterArrayMerge, $nlStatus);
        }

        if ($getEnableStatus == 1 && $getTrackingStatus == 1 && $nlStatus == 1) {
            $valueConfig = $sendinblueData->getApiConfigValue();
            if (isset($valueConfig['data']['date_format']) && $valueConfig['data']['date_format'] == 'dd-mm-yyyy') {
                $date = date('d-m-Y', strtotime($orderData['created_at']));
            }
            else {
                $date = date('m-d-Y', strtotime($orderData['created_at']));
            }
                      
            $getUserLists = array($getUserLists);
            $attributesValues = array("PRENOM" => $custmerData['firstname'], "NOM" => $custmerData['lastname'], "ORDER_ID" => $referenceNumber, "ORDER_DATE" => $date, "ORDER_PRICE" => $orderprice);
            $sendinblueData->importTransactionalData($email, $attributesValues, $getUserLists);
        }                
    }
}
