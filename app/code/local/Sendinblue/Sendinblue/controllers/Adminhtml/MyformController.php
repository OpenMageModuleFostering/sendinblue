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
		$params = empty($params)?array():$params;
		if (isset($params['sendin_apikey']) && $params['sendin_apikey'] != '')
			$this->CreateFolderCaseTwo();

		$this->loadLayout();
        $this->renderLayout();
    }
	public function syncronizepostAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
            $sendin_switch = new Mage_Core_Model_Config();
            if (isset($post['syncronizeSubmit']))
            {
                $sendin_switch->saveConfig('sendinblue/syncronize', $post['syncronize']);
				if (!empty($post['template']))
                {
					$sendin_switch->saveConfig('sendinblue/Sendin_Template_Id', $post['template']);
					$message = $this->__('Your setting has been successfully saved');
                }

                if (!empty($post['sendin_list']))
                {
                    $list = implode('|', $post['sendin_list']);
                    $sendin_switch->saveConfig('sendinblue/list', $list);
                    $message = $this->__('Your setting has been successfully saved');
                    Mage::getSingleton('adminhtml/session')->addSuccess($message);
                } else
                {
                    $message = $this->__('Please select a list');
                    Mage::getSingleton('adminhtml/session')->addError($message);
                }
				
            }

        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
    
	public function reimportpostAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
            $sendin_switch = new Mage_Core_Model_Config();
            if (isset($post['importoldSubmit']) && !empty($post['importoldSubmit']))
                {
                    $list = Mage::getModel('sendinblue/sendinblue')->getUserlists();
                    $list_id = str_replace('|', ',', $list);
                    $apikey = Mage::getModel('sendinblue/sendinblue')->getApiKey();
                    $allemail = Mage::getModel('sendinblue/sendinblue')->getcustomers();		
					$params = array();
					$params['webaction'] = 'IMPORTUSERS';
					$params['key'] = $apikey;
					$params['url'] = Mage::getBaseUrl('media').'sendinblue_csv/ImportSubUsersToSendinblue.csv';
					$params['listids'] = $list_id;
					$params['notify_url'] = Mage::getBaseUrl().'sendinblue/ajax/emptySubsUserToSendinblue';
					$responce_data = Mage::getModel('sendinblue/sendinblue')->curlRequestAsyc($params);

					$res_value = json_decode($responce_data);
					$sendin_switch->saveConfig('sendinblue/importOldUserStatus', 0);
						if (empty($res_value->process_id))
						{
							$sendin_switch->saveConfig('sendinblue/importOldUserStatus', 1);
							$message = $this->__('Old subscribers not imported successfully, please click on Import Old Subscribers button to import them again');
							Mage::getSingleton('adminhtml/session')->addError($message);
						}
						else
						{
							$message = $this->__('Your setting has been successfully saved');
							Mage::getSingleton('adminhtml/session')->addSuccess($message);
						}
				}

        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }

    public function apikeypostAction()
    {
		$post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
            elseif (isset($post['sendin_apikey']) && !empty($post['sendin_apikey']))
            {
                $sendin_switch = new Mage_Core_Model_Config();
                $result       = Mage::getModel('sendinblue/sendinblue')->checkApikey(trim($post['sendin_apikey']));
                if (empty($result['error']))
                {
                    $get_key = Mage::getModel('sendinblue/sendinblue')->getApiKey();
                    if ($get_key == '')
                        Mage::getModel('sendinblue/sendinblue')->createFolderName($post['sendin_apikey']);
                    elseif ($get_key != $post['sendin_apikey'])
                        Mage::getModel('sendinblue/sendinblue')->createFolderName($post['sendin_apikey']);
					  					 	           
                    $sendin_switch->saveConfig('sendinblue/api', trim($post['sendin_apikey']));
                    $sendin_switch->saveConfig('sendinblue/enabled', $post['sendin_api_status']);
					$sendin_switch->saveConfig('sendinblue/syncronize', 1);
                    Mage::getModel('sendinblue/sendinblue')->removeOldEntry();
                    if(Mage::getModel('sendinblue/sendinblue')->getImportOldSubsStatus()==1)
                    {
                    $message = $this->__('Old subscribers not imported successfully, please click on Import Old Subscribers button to import them again');
                    Mage::getSingleton('core/session')->addError($message);
				    }
                    else
                    {
                    $message = $this->__('Your setting has been successfully saved');
					Mage::getSingleton('adminhtml/session')->addSuccess($message);
					}
                    
                } else if (isset($result['error']))
                {
                    $message = $this->__('You have entered wrong api key');
                    Mage::getSingleton('core/session')->addError($message);
                }
            }
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
    public function sendmailAction()
    {
        $post = $this->getRequest()->getPost();
       
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
            elseif (isset($post['SmtpSubmit']) && !empty($post['SmtpSubmit']))
            {
                $sendin_switch = new Mage_Core_Model_Config();
                $get_key       = Mage::getModel('sendinblue/sendinblue')->getApiKey();
                $result       = Mage::getModel('sendinblue/sendinblue')->checkApikey($get_key);
                if (empty($result['error']))
                { 
                    $smtp_response = Mage::getModel('sendinblue/sendinblue')->TrackingSmtp(); // get tracking code
                   
                    if ($smtp_response->result->relay_data->status == 'enabled')
                    {  
                        $sendin_switch->saveConfig('sendinblue/smtp/authentication', 'crammd5', 'default', 0);
                        $sendin_switch->saveConfig('sendinblue/smtp/username', $smtp_response->result->relay_data->data->username, 'default', 0);
                        $sendin_switch->saveConfig('sendinblue/smtp/password', $smtp_response->result->relay_data->data->password, 'default', 0);
                        $sendin_switch->saveConfig('sendinblue/smtp/host', $smtp_response->result->relay_data->data->relay, 'default', 0);
                        $sendin_switch->saveConfig('sendinblue/smtp/port', $smtp_response->result->relay_data->data->port, 'default', 0);
                        $sendin_switch->saveConfig('sendinblue/smtp/ssl', 'null', 'default', 0);
                        $sendin_switch->saveConfig('sendinblue/smtp/option', 'smtp', 'default', 0);
                        if ($post['email'])
                        {
                           $data11 =  Mage::getModel('sendinblue/sendinblue')->sendTestMail($post['email']);
						   $resArr = json_decode($data11, true);						 		   
						  	if ($resArr['result'] != true) {
								$message = $this->__('Mail not sent').' '.$this->__(trim($resArr['error']));
								Mage::getSingleton('adminhtml/session')->addError($message);
							}
							else {
								$message = $this->__('Mail sent!');									
								Mage::getSingleton('core/session')->addSuccess($message);
	
							}

                        } else
                        {
                            $message = $this->__('Mail not sent');
                            Mage::getSingleton('adminhtml/session')->addError($message);
                        }
                    }
                    else
                    {
                        $sendin_switch->saveConfig('sendinblue/smtp/status', 0);
                        $message = $this->__('Your SMTP account is not activated and therefore you can not use SendinBlue SMTP. For more informations, Please contact our support to: contact@sendinblue.com');
                        Mage::getSingleton('adminhtml/session')->addError($message);
                        
                    }
                } elseif (isset($responce['error']))
                {
                    $message = $this->__('You have entered wrong api key');
                    Mage::getSingleton('core/session')->addError($message);
                }
            }
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
    public function sendorderAction()
    {
        $post = $this->getRequest()->getPost();
        try 
        {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
				$sendin_switch = new Mage_Core_Model_Config();
            if (isset($post['sender_order_save']))
            {
				$sender_order = $post['sender_order'];
				$sender_order_message = $post['sender_order_message'];
				
				if (isset($sender_order) && $sender_order == '')
				{
					$message = $this->__('Please fill the message field');
                    Mage::getSingleton('adminhtml/session')->addError($message);
				}
				else if ($sender_order_message == '')
				{
					$message = $this->__('Please fill the message field');
                    Mage::getSingleton('adminhtml/session')->addError($message);
				}
				else
				{
					$sendin_switch->saveConfig('sendinblue/Sendin_Sender_Order', $sender_order);
					$sendin_switch->saveConfig('sendinblue/Sendin_Sender_Order_Message', $sender_order_message);
					$message = $this->__('Your setting has been successfully saved');
                    Mage::getSingleton('adminhtml/session')->addSuccess($message);
				}
			
            }
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
     public function sendshipmentAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
                
				$sendin_switch = new Mage_Core_Model_Config();
            if (isset($post['sender_shipment_save']))
            {
				$sender_shipment = $post['sender_shipment'];
				$sender_shipment_message = $post['sender_shipment_message'];
				
				if (isset($sender_shipment) && $sender_shipment == '')
				{
					$message = $this->__('Please fill the message field');
                    Mage::getSingleton('adminhtml/session')->addError($message);
				}
				else if ($sender_shipment_message == '')
				{
					$message = $this->__('Please fill the message field');
                    Mage::getSingleton('adminhtml/session')->addError($message);
				}
				else
				{
					$sendin_switch->saveConfig('sendinblue/Sendin_Sender_Shipment', $sender_shipment);
					$sendin_switch->saveConfig('sendinblue/Sendin_Sender_Shipment_Message', $sender_shipment_message);
					$message = $this->__('Your setting has been successfully saved');
                    Mage::getSingleton('adminhtml/session')->addSuccess($message);
				}
			
            }
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
    
    /**
	 * This method is called when the user test Shipment  Sms and hits the submit button.
	 */
	 
	public function sendordertestAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
                
				$sendin_switch = new Mage_Core_Model_Config();
				
            if (isset($post['sender_order_submit']))
            {
				$arr = array();
				$arr['to'] = $post['sender_order_number'];
				$arr['from'] = Mage::getModel('sendinblue/sendinblue')->getSendSmsOrderSubject();
				$arr['text'] = Mage::getModel('sendinblue/sendinblue')->getSendSmsmOrderMessage();

				$result = Mage::getModel('sendinblue/sendinblue')->sendSmsApi($arr);

				if (isset($result->status) && $result->status == 'OK')
				{
					$message = $this->__('Message has been sent successfully');
                    Mage::getSingleton('adminhtml/session')->addSuccess($message);
				}
				else
				{
					$message = $this->__('Message has not been sent successfully');
                    Mage::getSingleton('adminhtml/session')->addError($message);
				}
			
            }
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
    
     
    /**
	 * This method is called when the user test Shipment  Sms and hits the submit button.
	 */
	 
	public function sendshipmenttestAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
                
				$sendin_switch = new Mage_Core_Model_Config();
				
            if (isset($post['sender_shipment_submit']))
            {
				$arr = array();
				$arr['to'] = $post['sender_shipment_number'];
				$arr['from'] = Mage::getModel('sendinblue/sendinblue')->getSendSmsShipingSubject();
				$arr['text'] = Mage::getModel('sendinblue/sendinblue')->getSendSmsShipingMessage();
				
				$result = Mage::getModel('sendinblue/sendinblue')->sendSmsApi($arr);
				
				if (isset($result->status) && $result->status == 'OK')
				{
					$message = $this->__('Message has been sent successfully');
                    Mage::getSingleton('adminhtml/session')->addSuccess($message);
				}
				else
				{
					$message = $this->__('Message has not been sent successfully');
                    Mage::getSingleton('adminhtml/session')->addError($message);
				}
			
            }
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
    
    public function sendnotifysmsAction()
    {
        $post = $this->getRequest()->getPost();
        try {
				if (empty($post))
					Mage::throwException($this->__('Invalid form data.'));
					
					$sendin_switch = new Mage_Core_Model_Config();
					
				if (isset($post['notify_sms_mail']))
				{
					$sendin_switch->saveConfig('sendinblue/Sendin_Notify_Value', $post['sendin_notify_value']);
					$sendin_switch->saveConfig('sendinblue/Sendin_Notify_Email', $post['sendin_notify_email']);
					$message = $this->__('Your setting has been successfully saved');
					Mage::getSingleton('adminhtml/session')->addSuccess($message);
				}
			}
			catch (Exception $e)
			{
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
			$this->_redirect('*/*');
		
	}
	
	
	public function sendcampaignAction()
    {
        $post = $this->getRequest()->getPost();   
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
                
				$sendin_switch = new Mage_Core_Model_Config();
				
            if (isset($post['sender_campaign_save']) && $post['Sendin_Sms_Choice'] == 1)
            {
				$arr = array();
				$arr['to'] = $post['singlechoice'];
				$arr['from'] = $post['sender_campaign'];
				$arr['text'] = $post['sender_campaign_message'];
				
				$result = Mage::getModel('sendinblue/sendinblue')->sendSmsApi($arr);
				if (isset($result->status) && $result->status == 'OK')
							{
								$message = $this->__('Message has been sent successfully');
								Mage::getSingleton('adminhtml/session')->addSuccess($message);
							}
							else
							{
								$message = $this->__('Message has not been sent successfully');
								Mage::getSingleton('adminhtml/session')->addError($message);
							}
				
			
            }else if(isset($post['sender_campaign_save']) && $post['Sendin_Sms_Choice'] == 0)
            {
				$smscredit = Mage::getModel('sendinblue/sendinblue')->getSmsCredit();
				$collection = Mage::getModel('customer/customer')
				->getCollection()
				->addAttributeToSelect('*')
				->joinAttribute('shipping_telephone', 'customer_address/telephone', 'default_shipping', null, 'left')
				->joinAttribute('shipping_country_code', 'customer_address/country_id', 'default_shipping', null, 'left');
                
				$results = array();
				foreach ($collection as $customer) {
					$results[] = $customer->toArray();
				}
				
				//$count = count($result);				
				foreach ($results as $i => $result)
				{ 
				
					if(isset($result['shipping_telephone']) && !empty($result['shipping_telephone']))
					{
						$tableCountry = Mage::getSingleton('core/resource')->getTableName('sendinblue_country_codes');
						$sql = 'SELECT * FROM '.$tableCountry.' WHERE iso_code = "'.$result['shipping_country_code'].'" ';
						$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
						$data = $connection->fetchRow($sql);						
						$number = Mage::getModel('sendinblue/sendinblue')->checkMobileNumber($result['shipping_telephone'],$data['country_prefix']);					
						$firstname = !empty($result['firstname'])?$result['firstname']:'';
						$lastname = !empty($result['lastname'])?$result['lastname']:'';
						$msgbody = !empty($post['sender_campaign_message'])?$post['sender_campaign_message']:'';
						$fname = str_replace('{first_name}', $firstname, $msgbody);
						$msgbody = str_replace('{last_name}', $lastname."\r\n", $fname);
						$arr = array();
						$arr['to'] = $number;
						$arr['from'] = !empty($post['sender_campaign'])?$post['sender_campaign']:'';
						$arr['text'] = $msgbody;						
						Mage::getModel('sendinblue/sendinblue')->sendSmsApi($arr);				     				
					}
				} 						
						if ($smscredit >= 1)
							{
								$message = $this->__('Message has been sent successfully');
								Mage::getSingleton('adminhtml/session')->addSuccess($message);
							}
							else
							{
								$message = $this->__('Message has not been sent successfully');
								Mage::getSingleton('adminhtml/session')->addError($message);
							}
			
			}
			else if(isset($post['sender_campaign_save']) && $post['Sendin_Sms_Choice'] == 2)
			{	
                            $smscredit = Mage::getModel('sendinblue/sendinblue')->getSmsCredit();
 							 
                            $camp_name = 'SMS_'.date('Ymd');
                            $key = Mage::getModel('sendinblue/sendinblue')->getApiKey();
                            if ($key == '')
                            return false;
                            $param['key'] = $key;
                            $param['listname'] = $camp_name;
                            $param['webaction'] = 'NEWLIST';
                            $param['list_parent'] = '1';
                            //folder id
                            $list_response = Mage::getModel('sendinblue/sendinblue')->curlRequest($param);
                            $res = json_decode($list_response);
                            $list_id = $res->result;
                            // import old user to SendinBlue

                            $iso_code = $this->context->language->iso_code;
                            $allemail = Mage::getModel('sendinblue/sendinblue')->smsCampaignList();

                            $data['webaction'] = 'MULTI-USERCREADIT';
                            $data['key'] = $key;
                            $data['attributes'] = $allemail;
                            $data['listid'] = $list_id;
                            // List id should be optional

                            $data_responce = Mage::getModel('sendinblue/sendinblue')->curlRequest($data);    
                                        
                            $msgbody = $post['sender_campaign_message'];
                            $value_langauge = Mage::getModel('sendinblue/sendinblue')->getApiConfigValue();
                            if ($value_langauge->language == 'fr')
                            {   
                                $firstname = '{NOM}';
                                $lastname = '{PRENOM}';
                            }
                            else
                            {
                                $firstname = '{NAME}';
                                $lastname = '{SURNAME}';
                            }
                            $fname = str_replace('{first_name}', $firstname, $msgbody);
                            $msgbody = str_replace('{last_name}', $lastname."\r\n", $fname);				
                            $arr = array();							
                            $sender_campaign = $post['sender_campaign'];
                            $content = $msgbody;
                            //Mage::getModel('sendinblue/sendinblue')->sendSmsApi($arr);										     
                            $arr['key'] = Mage::getModel('sendinblue/sendinblue')->getApiKey();
                            $arr['webaction'] = 'SMSCAMPCREADIT';
                            $arr['camp_name'] = $camp_name; // mandatory
                            $arr['sender'] = $sender_campaign;
                            $arr['content'] = $content;
                            $arr['bat_sent'] = '';
                            $arr['listids'] = $list_id; // mandatory if SMS campaign is scheduled
                            $arr['exclude_list'] = '';
                            $arr['schedule'] = date('Y-m-d H:i:s', time() + 300);

                            $data_camp = Mage::getModel('sendinblue/sendinblue')->curlRequest($arr);


                            if ($smscredit >= 1)
                            {
                                    $message = $this->__('Message has been sent successfully');
                                    Mage::getSingleton('adminhtml/session')->addSuccess($message);
                            }
                            else
                            {
                                    $message = $this->__('Message has not been sent successfully');
                                    Mage::getSingleton('adminhtml/session')->addError($message);
                            }									    
			}
			
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }
	
}
