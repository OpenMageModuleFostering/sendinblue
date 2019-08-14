<?php
/**
* @author Sendinblue plateform <contact@sendinblue.com>
* @copyright  2013-2014 Sendinblue
* URL:  https:www.sendinblue.com
* Do not edit or add to this file if you wish to upgrade Sendinblue Magento plugin to newer
* versions in the future. If you wish to customize Sendinblue magento plugin for your
* needs then we can't provide a technical support.
**/

class Sendinblue_Sendinblue_Adminhtml_MyformController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $params = Mage::app()->getRequest()->getParams();
        if (isset($params['sendin_apikey']) && $params['sendin_apikey'] != ''){
            $this->CreateFolderCaseTwo();
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    public function syncronizepostAction()
    {
        $requestParameter = $this->getRequest()->getPost();
        try {
            if (empty($requestParameter)){
                Mage::throwException($this->__('Invalid form data.'));
            }
            $sendinSwitch = Mage::getModel('core/config');
            
            if (isset($requestParameter['syncronizeSubmit'])) {
                $sendinSwitch->saveConfig('sendinblue/syncronize', $requestParameter['syncronize']);
                if (!empty($requestParameter['subscribe_confirm_type'])) {
                    $sendinSwitch->saveConfig('sendinblue/SendinSubscribeConfirmType', $requestParameter['subscribe_confirm_type']);
                    $sendinSwitch->saveConfig('sendinblue/SendinTemplateId', $requestParameter['template_simple']);
                    $sendinSwitch->saveConfig('sendinblue/SendinOptinRedirectUrlCheck', $requestParameter['optin_redirect_url_check']);
                    $sendinSwitch->saveConfig('sendinblue/SendinDoubleoptinRedirectUrl', $requestParameter['doubleoptin-redirect-url']);
                    $sendinSwitch->saveConfig('sendinblue/SendinDoubleoptinTemplateId', $requestParameter['doubleoptin_template_id']);
                    $sendinSwitch->saveConfig('sendinblue/SendinFinalConfirmEmail', $requestParameter['final_confirm_email']);
                    $sendinSwitch->saveConfig('sendinblue/SendinTemplateFinal', $requestParameter['template_final']);
                    $sendinModule = Mage::getModel('sendinblue/sendinblue');
                    if ($requestParameter['subscribe_confirm_type'] === 'doubleoptin') {
                        $responseDoubleOption = $sendinModule->checkFolderListDoubleoptin();
                        if (!empty($responseDoubleOption['optin_id'])) {
                            $sendinSwitch->saveConfig('sendinblue/SendinOptinListId', $responseDoubleOption['optin_id']);
                        }
                        if ($responseDoubleOption === false) {
                            $optinId = $sendinModule->createListIdDoubleoptin();
                            $sendinSwitch->saveConfig('sendinblue/SendinOptinListId', $optinId);
                        }
                    }
                    $message = $this->__('Your setting has been successfully saved');
                }

                if (!empty($requestParameter['sendin_list'])) {
                    $list = implode('|', $requestParameter['sendin_list']);
                    $sendinSwitch->saveConfig('sendinblue/list', $list);
                    $message = $this->__('Your setting has been successfully saved');
                    Mage::getModel('adminhtml/session')->addSuccess($message);
                } 
                else {
                    $message = $this->__('Please select a list');
                    Mage::getModel('adminhtml/session')->addError($message);
                }
            }
        }
        catch (Exception $exception) {
            Mage::getModel('adminhtml/session')->addError($exception->getMessage());
        }
        $this->_redirect('*/*');
    }
    
    public function reimportpostAction()
    {
        $requestParameter = $this->getRequest()->getPost();
        try {
            if (empty($requestParameter)){
                Mage::throwException($this->__('Invalid form data.'));
            }
            $sendinModule = Mage::getModel('sendinblue/sendinblue');
            $sendinSwitch = Mage::getModel('core/config');
            if (!empty($requestParameter['importoldSubmit'])) {
                $list = $sendinModule->getUserlists();
                $listId = str_replace('|', ',', $list);
                $allemail = $sendinModule->getcustomers();
                if ($allemail > 0) {
                    $userData = array();
                    $userData['url'] = Mage::getBaseUrl('media').'sendinblue_csv/ImportSubUsersToSendinblue.csv';
                    $userData['listids'] = $listId;
                    $userData['notify_url'] = Mage::getBaseUrl().'sendinblue/ajax/emptySubsUserToSendinblue';
                    $apiDetails['api_key'] = $sendinModule->getApiKey();
                    $psmailinObj = Mage::getModel('sendinblue/psmailin',$apiDetails);
                    $responseImportUser = $psmailinObj->importUsers($userData);
                    $sendinSwitch->saveConfig('sendinblue/importOldUserStatus', 0);
                    if (empty($responseImportUser['data']['process_id'])) {
                        $sendinSwitch->saveConfig('sendinblue/importOldUserStatus', 1);
                        $message = $this->__('Old subscribers not imported successfully, please click on Import Old Subscribers button to import them again');
                        Mage::getModel('adminhtml/session')->addError($message);
                    }
                    else {
                        $message = $this->__('Your setting has been successfully saved');
                        Mage::getModel('adminhtml/session')->addSuccess($message);
                    }
                }
            }
        }
        catch (Exception $exception) {
            Mage::getModel('adminhtml/session')->addError($exception->getMessage());
        }
        $this->_redirect('*/*');
    }

    public function apikeypostAction()
    {
        $requestParameter = $this->getRequest()->getPost();
        $sendinModule = Mage::getModel('sendinblue/sendinblue');
        try {
            if (empty($requestParameter)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
            else if (!empty($requestParameter['sendin_apikey'])) {
                $sendinSwitch = Mage::getModel('core/config');
                $apiKeyStatus = $sendinModule->checkApikey($requestParameter['sendin_apikey']);
                if (empty($apiKeyStatus['error'])) {
                    $getKey = $sendinModule->getApiKey();
                    if ($getKey == '' || $getKey != $requestParameter['sendin_apikey']) {
                        $sendinModule->createFolderName($requestParameter['sendin_apikey']);
                    }

                    $sendinSwitch->saveConfig('sendinblue/api', trim($requestParameter['sendin_apikey']));
                    $sendinSwitch->saveConfig('sendinblue/enabled', $requestParameter['sendin_api_status']);
                    $sendinSwitch->saveConfig('sendinblue/Sendin_Notify_Cron_Executed', 0, 'default', 0);
                    $sendinSwitch->saveConfig('sendinblue/syncronize', 1);
                    $sendinModule->removeOldEntry();
                    if($sendinModule->getImportOldSubsStatus() == 1) {
                        $message = $this->__('Old subscribers not imported successfully, please click on Import Old Subscribers button to import them again');
                        Mage::getModel('core/session')->addError($message);
                    }
                    else {
                        $message = $this->__('Your setting has been successfully saved');
                       Mage::getModel('adminhtml/session')->addSuccess($message);
                    }
                    
                } 
                else if (isset($apiKeyStatus['error'])) {
                    $message = $this->__('You have entered wrong api key');
                    Mage::getModel('core/session')->addError($message);
                }
            }
        }
        catch (Exception $exception) {
            Mage::getModel('adminhtml/session')->addError($exception->getMessage());
        }
        $this->_redirect('*/*');
    }

    public function sendmailAction()
    {
        $requestParameter = $this->getRequest()->getPost();
        $sendinModule = Mage::getModel('sendinblue/sendinblue');
        try {
            if (empty($requestParameter)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
            else if (!empty($requestParameter['SmtpSubmit'])) {
                $sendinSwitch = Mage::getModel('core/config');
                $getKey = $sendinModule->getApiKey();
                $apiKeyStatus  = $sendinModule->checkApikey($getKey);
                if (empty($apiKeyStatus['error'])) { 
                    $smtpResponse = $sendinModule->trackingSmtp(); // get tracking code                   
                    if (isset($smtpResponse['data']['relay_data']['status']) && $smtpResponse['data']['relay_data']['status'] == 'enabled') {  
                        $sendinSwitch->saveConfig('sendinblue/smtp/authentication', 'crammd5', 'default', 0);
                        $sendinSwitch->saveConfig('sendinblue/smtp/username', $smtpResponse['data']['relay_data']['data']['username'], 'default', 0);
                        $sendinSwitch->saveConfig('sendinblue/smtp/password', $smtpResponse['data']['relay_data']['data']['password'], 'default', 0);
                        $sendinSwitch->saveConfig('sendinblue/smtp/host', $smtpResponse['data']['relay_data']['data']['relay'], 'default', 0);
                        $sendinSwitch->saveConfig('sendinblue/smtp/port', $smtpResponse['data']['relay_data']['data']['port'], 'default', 0);
                        $sendinSwitch->saveConfig('sendinblue/smtp/ssl', 'null', 'default', 0);
                        $sendinSwitch->saveConfig('sendinblue/smtp/option', 'smtp', 'default', 0);
                        if ($requestParameter['email']) {
                            $responceSendTestMail =  $sendinModule->sendTestMail($requestParameter['email']);
                            $responceSendTestData = json_decode($responceSendTestMail, true);   

                            if ($responceSendTestData['result'] != true) {
                                $message = $this->__('Mail not sent').' '.$this->__(trim($responceSendTestData['error']));
                                Mage::getModel('adminhtml/session')->addError($message);
                            }
                            else {
                                $message = $this->__('Mail sent!');                                 
                                Mage::getModel('core/session')->addSuccess($message);   
                            }
                        } 
                        else {
                            $message = $this->__('Mail not sent');
                            Mage::getModel('adminhtml/session')->addError($message);
                        }
                    } 
                    else {
                        $sendinSwitch->saveConfig('sendinblue/smtp/status', 0);
                        $message = $this->__('Your SMTP account is not activated and therefore you can not use SendinBlue SMTP. For more informations, Please contact our support to: contact@sendinblue.com');
                        Mage::getModel('adminhtml/session')->addError($message);                        
                    }
                } 
                elseif (isset($apiKeyStatus['error'])) {
                    $message = $this->__('You have entered wrong api key');
                    Mage::getModel('core/session')->addError($message);
                }
            }
        }
        catch (Exception $exception) {
            Mage::getModel('adminhtml/session')->addError($exception->getMessage());
        }
        $this->_redirect('*/*');
    }

    public function sendorderAction()
    {
        $requestParameter = $this->getRequest()->getPost();
        try {
            if (empty($requestParameter)){
                Mage::throwException($this->__('Invalid form data.'));
            }
            $sendinSwitch = Mage::getModel('core/config');
            if (isset($requestParameter['sender_order_save'])) {
                $senderOrder = isset($requestParameter['sender_order']) ? $requestParameter['sender_order'] : NULL;
                $senderOrderMessage = $requestParameter['sender_order_message'];

                if ($senderOrder == '') {
                    $message = $this->__('Please fill the message field');
                    Mage::getModel('adminhtml/session')->addError($message);
                }
                else if ($senderOrderMessage == '') {
                    $message = $this->__('Please fill the message field');
                    Mage::getModel('adminhtml/session')->addError($message);
                }
                else {
                    $sendinSwitch->saveConfig('sendinblue/Sendin_Sender_Order', $senderOrder);
                    $sendinSwitch->saveConfig('sendinblue/Sendin_Sender_Order_Message', $senderOrderMessage);
                    $message = $this->__('Your setting has been successfully saved');
                    Mage::getModel('adminhtml/session')->addSuccess($message);
                }           
            }
        }
        catch (Exception $exception) {
            Mage::getModel('adminhtml/session')->addError($exception->getMessage());
        }
        $this->_redirect('*/*');
    }

    public function sendshipmentAction()
    {
        $requestParameter = $this->getRequest()->getPost();
        try {
            if (empty($requestParameter)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
            $sendinSwitch = Mage::getModel('core/config');
            if (isset($requestParameter['sender_shipment_save'])) {
                $senderShipment = isset($requestParameter['sender_shipment']) ? $requestParameter['sender_shipment'] : NULL;
                $senderShipmentMessage = $requestParameter['sender_shipment_message'];
                
                if ($senderShipment == '') {
                    $message = $this->__('Please fill the sender field');
                    Mage::getModel('adminhtml/session')->addError($message);
                }
                else if ($senderShipmentMessage == '') {
                    $message = $this->__('Please fill the message field');
                    Mage::getModel('adminhtml/session')->addError($message);
                }
                else {
                    $sendinSwitch->saveConfig('sendinblue/Sendin_Sender_Shipment', $senderShipment);
                    $sendinSwitch->saveConfig('sendinblue/Sendin_Sender_Shipment_Message', $senderShipmentMessage);
                    $message = $this->__('Your setting has been successfully saved');
                    Mage::getModel('adminhtml/session')->addSuccess($message);
                }           
            }
        }
        catch (Exception $exception) {
            Mage::getModel('adminhtml/session')->addError($exception->getMessage());
        }
        $this->_redirect('*/*');
    }
    
    /**
     * This method is called when the user test Shipment  Sms and hits the submit button.
     */
     
    public function sendordertestAction()
    {
        $sendinModule = Mage::getModel('sendinblue/sendinblue');
        $requestParameter = $this->getRequest()->getPost();
        try {
            if (empty($requestParameter)) {
                Mage::throwException($this->__('Invalid form data.'));
            }    
            $sendinSwitch = Mage::getModel('core/config');
                
            if (isset($requestParameter['sender_order_submit'])) {
                $smsData = array();
                $smsData['to'] = $requestParameter['sender_order_number'];
                $smsData['from'] = $sendinModule->getSendSmsOrderSubject();
                $smsData['text'] = $sendinModule->getSendSmsmOrderMessage();

                $sendSmsResponce = $sendinModule->sendSmsApi($smsData);
                if (isset($sendSmsResponce['status']) && $sendSmsResponce['status'] == 'OK') {
                    $message = $this->__('Message has been sent successfully');
                    Mage::getModel('adminhtml/session')->addSuccess($message);
                }
                else {
                    $message = $this->__('Message has not been sent successfully');
                    Mage::getModel('adminhtml/session')->addError($message);
                }           
            }
        }
        catch (Exception $exception) {
            Mage::getModel('adminhtml/session')->addError($exception->getMessage());
        }
        $this->_redirect('*/*');
    } 

    /**
    * This method is called when the user test Shipment  Sms and hits the submit button.
    */
     
    public function sendshipmenttestAction()
    {
        $sendinModule = Mage::getModel('sendinblue/sendinblue');
        $requestParameter = $this->getRequest()->getPost();
        try {
            if (empty($requestParameter)) {
                Mage::throwException($this->__('Invalid form data.'));
            }    
            $sendinSwitch = Mage::getModel('core/config');

            if (isset($requestParameter['sender_shipment_submit'])) {
                $smsData = array();
                $smsData['to'] = $requestParameter['sender_shipment_number'];
                $smsData['from'] = $sendinModule->getSendSmsShipingSubject();
                $smsData['text'] = $sendinModule->getSendSmsShipingMessage();

                $sendSmsResponce = $sendinModule->sendSmsApi($smsData);

                if (isset($sendSmsResponce['status']) && $sendSmsResponce['status'] == 'OK') {
                    $message = $this->__('Message has been sent successfully');
                    Mage::getModel('adminhtml/session')->addSuccess($message);
                }
                else {
                    $message = $this->__('Message has not been sent successfully');
                    Mage::getModel('adminhtml/session')->addError($message);
                }
            }
        }
        catch (Exception $exception) {
            Mage::getModel('adminhtml/session')->addError($exception->getMessage());
        }
        $this->_redirect('*/*');
    }
    
    public function sendnotifysmsAction()
    {
        $requestParameter = $this->getRequest()->getPost();
        try {
            if (empty($requestParameter)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
            $sendinSwitch = Mage::getModel('core/config');

            if (isset($requestParameter['notify_sms_mail'])) {
                $sendinSwitch->saveConfig('sendinblue/Sendin_Notify_Value', $requestParameter['sendin_notify_value']);
                $sendinSwitch->saveConfig('sendinblue/Sendin_Notify_Email', $requestParameter['sendin_notify_email']);
                $sendinSwitch->saveConfig('sendinblue/Sendin_Notify_Cron_Executed', 0, 'default', 0);
                $message = $this->__('Your setting has been successfully saved');
                Mage::getModel('adminhtml/session')->addSuccess($message);
            }
        }
        catch (Exception $exception) {
            Mage::getModel('adminhtml/session')->addError($exception->getMessage());
        }
        $this->_redirect('*/*');
    }

    public function sendcampaignAction()
    {
        $requestParameter = $this->getRequest()->getPost();
        $sendinModule = Mage::getModel('sendinblue/sendinblue');
        $apiDetails['api_key'] = $sendinModule->getApiKey();
        $psmailinObj = Mage::getModel('sendinblue/psmailin',$apiDetails);
        
        try {
            if (empty($requestParameter)) {
                Mage::throwException($this->__('Invalid form data.'));
            }
            $sendinSwitch = Mage::getModel('core/config');

            if (isset($requestParameter['sender_campaign_save']) && $requestParameter['Sendin_Sms_Choice'] == 1) {
                $smsData = array();
                $smsData['to'] = $requestParameter['singlechoice'];
                $smsData['from'] = $requestParameter['sender_campaign'];
                $smsData['text'] = $requestParameter['sender_campaign_message'];
                
                $sendSmsResponce = $sendinModule->sendSmsApi($smsData);
                if (isset($sendSmsResponce['status']) && $sendSmsResponce['status'] == 'OK') {
                    $message = $this->__('Message has been sent successfully');
                    Mage::getModel('adminhtml/session')->addSuccess($message);
                }
                else {
                    $message = $this->__('Message has not been sent successfully');
                    Mage::getModel('adminhtml/session')->addError($message);
                }           
            }
            else if(isset($requestParameter['sender_campaign_save']) && $requestParameter['Sendin_Sms_Choice'] == 0) {
                $smsCredit = $sendinModule->getSmsCredit();
                $collection = Mage::getModel('customer/customer')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->joinAttribute('shipping_telephone', 'customer_address/telephone', 'default_shipping', null, 'left')
                ->joinAttribute('shipping_country_code', 'customer_address/country_id', 'default_shipping', null, 'left');
                
                $results = array();
                foreach ($collection as $customer) {
                    $results[] = $customer->toArray();
                }

                foreach ($results as $i => $result) {
                    if(!empty($result['shipping_telephone']) && !empty($result['shipping_country_code'])) {
                        $countryCode = $sendinModule->getCountryCode($result['shipping_country_code']);
                        $number = $sendinModule->checkMobileNumber($result['shipping_telephone'],$countryCode);                 
                        $firstname = !empty($result['firstname']) ? $result['firstname'] : '';
                        $lastname = !empty($result['lastname']) ? $result['lastname'] : '';
                        $messageBody = !empty($requestParameter['sender_campaign_message'])?$requestParameter['sender_campaign_message']:'';
                        $fname = str_replace('{first_name}', $firstname, $messageBody);
                        $messageBody = str_replace('{last_name}', $lastname."\r\n", $fname);
                        $smsData = array();
                        $smsData['to'] = $number;
                        $smsData['from'] = !empty($requestParameter['sender_campaign'])?$requestParameter['sender_campaign']:'';
                        $smsData['text'] = $messageBody;                        
                        $sendinModule->sendSmsApi($smsData);                                
                    }
                }

                if ($smsCredit >= 1) {
                    $message = $this->__('Message has been sent successfully');
                    Mage::getModel('adminhtml/session')->addSuccess($message);
                }
                else {
                    $message = $this->__('Message has not been sent successfully');
                    Mage::getModel('adminhtml/session')->addError($message);
                }
            }
            else if(isset($requestParameter['sender_campaign_save']) && $requestParameter['Sendin_Sms_Choice'] == 2) {  
                $smsCredit = $sendinModule->getSmsCredit();
                $scheduleMonth = $requestParameter['sib_datetimepicker'];
                $scheduleHour = $requestParameter['hour'];
                $scheduleMinute = $requestParameter['minute'];
                if ($scheduleHour < 10) {
                    $scheduleHour = '0'.$scheduleHour;
                }
                if ($scheduleMinute < 10) {
                    $scheduleMinute = '0'.$scheduleMinute;
                }
                $scheduleTime = $scheduleMonth.' '.$scheduleHour.':'.$scheduleMinute.':00';
                $currentTime = date('Y-m-d H:i:s', time() + 300);
                $currentTimeExact = strtotime($currentTime);
                $scheduleTimeExact = strtotime($scheduleTime);

                if ($scheduleTime != '' || $scheduleTimeExact >= $currentTimeExact)
                {
                    $campaignName = 'SMS_'.date('Ymd');
                    $key = $sendinModule->getApiKey();
                    if ($key == '') {
                        return false;
                    }
                    $ListId = Mage::getStoreConfig('sendinblue/list');
                    $isoCode = $this->context->language->iso_code;
                    $allemail = $sendinModule->smsCampaignList();

                    $userData['attributes'] = $allemail;
                    $userData['listid'] = $ListId;
                    $psmailinObj->addMultipleUser($userData);

                    $messageBody = $requestParameter['sender_campaign_message'];
                    $langaugeValue = $sendinModule->getApiConfigValue();
                    if (isset($langaugeValue['data']['language']) && $langaugeValue['data']['language'] == 'fr') {   
                        $firstname = '{NOM}';
                        $lastname = '{PRENOM}';
                    }
                    else {
                        $firstname = '{NAME}';
                        $lastname = '{SURNAME}';
                    }

                    $fname = str_replace('{first_name}', $firstname, $messageBody);
                    $messageBody = str_replace('{last_name}', $lastname."\r\n", $fname);                
                    $smsCampaignData = array();                                      
                    $smsCampaignData['name'] = $campaignName; // mandatory
                    $smsCampaignData['sender'] = $requestParameter['sender_campaign'];
                    $smsCampaignData['content'] = $messageBody;
                    $smsCampaignData['bat_sent'] = '';
                    $smsCampaignData['listid'] = array($ListId); // mandatory if SMS campaign is scheduled
                    $smsCampaignData['exclude_list'] = '';
                    $smsCampaignData['scheduled_date'] = $scheduleTime;
                    $campaignDataRespose = $psmailinObj->createSmsCampaign($smsCampaignData);

                    if ($smsCredit >= 1) {
                        $message = $this->__('Message has been sent successfully');
                        Mage::getModel('adminhtml/session')->addSuccess($message);
                    }
                    else {
                        $message = $this->__('Message has not been sent successfully');
                        Mage::getModel('adminhtml/session')->addError($message);
                    }
                }
                else {
                    $message = $this->__('Scheduled date may not be prior to the current date');
                    Mage::getModel('adminhtml/session')->addError($message);
                }
            }
        }
        catch (Exception $exception)
        {
            Mage::getModel('adminhtml/session')->addError($exception->getMessage());
        }
        $this->_redirect('*/*');
    }
}
