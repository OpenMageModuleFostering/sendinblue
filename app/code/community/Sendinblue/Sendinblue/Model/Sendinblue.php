<?php
/**
* @author Sendinblue plateform <contact@sendinblue.com>
* @copyright  2013-2014 Sendinblue
* URL:  https:www.sendinblue.com
* Do not edit or add to this file if you wish to upgrade Sendinblue Magento plugin to newer
* versions in the future. If you wish to customize Sendinblue magento plugin for your
* needs then we can't provide a technical support.
**/
class Sendinblue_Sendinblue_Model_Sendinblue extends Mage_Core_Model_Abstract
{
	var $api_url;
	var $api_key;
	var $smtp_status;
	var $error_message;
	var $error_code;
	var $lists_ids;
	var $module_enable;
	var $st;
	public function _construct()
	{
		parent::_construct();
		$this->_init('sendinblue/sendinblue');
		$this->MIAPI();
	}
	/**
	* functions used for set module config
	*/
	public function MIAPI()
	{
		$scope = ($this->getScope()) ? $this->getScope() : Mage::app()->getStore()->getStoreId();		
		$this->module_enable = $this->getEnableStatus($scope);		
        $this->api_url = 'http://ws.mailin.fr/';
		$this->api_key = $this->getApiKey();
		$value_language = $this->getApiConfigValue();
		if(isset($value_language->language)) {
			$this->user_language = $value_language->language;
		}

		if (!$this->lists_ids) {
			$this->lists_ids = str_replace(',', '|', $this->getUserlists($scope));
		}

		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		if (isset($params['sendin_apikey']) && $params['sendin_apikey'] != '')
			$this->CreateFolderCaseTwo();
	}
    
	public function checkMobileNumber($number, $call_prefix)
	{
            $number = preg_replace('/\s+/', '', $number);
            $charone = substr($number, 0, 1);
            $chartwo = substr($number, 0, 2);

            if (preg_match('/^'.$call_prefix.'/', $number))
                    return '00'.$number;

            elseif ($charone == '0' && $chartwo != '00')
            {
                if (preg_match('/^0'.$call_prefix.'/', $number))
                        return '00'.substr($number, 1);
                else
                return '00'.$call_prefix.substr($number, 1);
            }
            elseif ($chartwo == '00')
            {
            if (preg_match('/^00'.$call_prefix.'/', $number))
				return $number;
            else
            return '00'.$call_prefix.substr($number, 2);
            }
            elseif ($charone == '+')
            {
            if (preg_match('/^\+'.$call_prefix.'/', $number))
				return '00'.substr($number, 1);
            else
            return '00'.$call_prefix.substr($number, 1);
            }
            elseif ($charone != '0')
			return '00'.$call_prefix.$number;
	}
    /**
     * functions used for getting module status
     */
	public function getEnableStatus()
	{
		$status = $this->getGeneralConfig('enabled', Mage::app()->getStore()->getStoreId());
		if (!$status)
			return false;
		return $status;
	}
	/**
	* functions used for send order sms module status
	*/
	public function getOrderSmsStatus()
	{
		$status = $this->getGeneralConfig('sms/order', Mage::app()->getStore()->getStoreId());
		if (!$status)
		return false;
		return $status;
	}
	/**
	* functions used for getting notify sms status
	*/
	public function getNotifySmsStatus()
	{
		$status = $this->getGeneralConfig('sms/credit', Mage::app()->getStore()->getStoreId());
		if (!$status)
			return false;
		return $status;
	}
	/**
	* functions used for getting Notify value limit 
	*/
	public function getNotifyValueStatus()
	{
		$status = $this->getGeneralConfig('Sendin_Notify_Value', Mage::app()->getStore()->getStoreId());
		if (!$status)
		return false;
		return $status;
	}
	/**
	* functions used for getting Notify email limit 
	*/
	public function getNotifyEmailStatus()
	{
		$status = $this->getGeneralConfig('Sendin_Notify_Email', Mage::app()->getStore()->getStoreId());
		if (!$status)
			return false;
		return $status;
	}
	/**
	* functions used for getting Notify email limit 
	*/
	public function getNotifyCronStatus()
	{
		$status = $this->getGeneralConfig('Sendin_Notify_Cron_Executed', Mage::app()->getStore()->getStoreId());
		if (!$status)
		return false;
		return $status;
	}
	/**
	* functions used for getting shiping sms status
	*/
	public function getShipingSmsStatus()
	{
		$status = $this->getGeneralConfig('sms/shiping', Mage::app()->getStore()->getStoreId());
		if (!$status)
			return false;
		return $status;
	}

	/**
	* functions used for getting campaign sms status
	*/
	public function getCampaignStatus()
	{
		$status = $this->getGeneralConfig('sms/campaign', Mage::app()->getStore()->getStoreId());
		if (!$status)
			return false;
		return $status;
	}

	/**
	* functions used for getting send sms order subject
	*/
	public function getSendSmsOrderSubject()
	{
		$status = $this->getGeneralConfig('Sendin_Sender_Order', Mage::app()->getStore()->getStoreId());
		if (!$status)
			return false;
		return $status;
	}

	/**
	* functions used for getting order sms message
	*/
	public function getSendSmsmOrderMessage()
	{
		$status = $this->getGeneralConfig('Sendin_Sender_Order_Message', Mage::app()->getStore()->getStoreId());
		if (!$status)
			return false;
		return $status;
	}

	/**
	*functions used for getting send sms shiping subject
	*/
	public function getSendSmsShipingSubject()
	{
		$status = $this->getGeneralConfig('Sendin_Sender_Shipment', Mage::app()->getStore()->getStoreId());
		if (!$status)
			return false;
		return $status;
	}

	/**
	*functions used for getting shiping sms message
	*/
	public function getSendSmsShipingMessage()
	{
		$status = $this->getGeneralConfig('Sendin_Sender_Shipment_Message', Mage::app()->getStore()->getStoreId());
		if (!$status)
			return false;
		return $status;
	}

	/**
	* functions used for get api key
	*/
	public function getApiKey()
	{
		$apikey = $this->getGeneralConfig('api', Mage::app()->getStore()->getStoreId());
		if (!$apikey)
			return false;
		return $apikey;
	}

	/**
	* functions used for get SMPT password
	*/
	public function getSmtpPassword()
	{
		$smtpPassword = $this->getSendinSmtpStatus('password', Mage::app()->getStore()->getStoreId());
		if (!$smtpPassword)
			return false;
		return $smtpPassword;
	}

	/**
	* functions used for get user name
	*/
	public function getUserName()
	{
		$userName = $this->getSendinSmtpStatus('username', Mage::app()->getStore()->getStoreId());
		if (!$userName)
			return false;
		return $userName;
	}

	/**
	* functions used for getting smtp status
	*/
	public function getSmtpStatus()
	{
		$status = $this->getSendinSmtpStatus('status', Mage::app()->getStore()->getStoreId());
		if (!$status)
			return false;
		return $status;
	}

	/**
	* functions used for getting tracking status
	*/
	public function getTrackingStatus()
	{
                $status = $this->getSendinTrackingCodeStatus('code', Mage::app()->getStore()->getStoreId());
		if (!$status) {
			return false;
		}
		return $status;
	}

    /**
	* functions used for getting tracking status
	*/
	public function getTrackingHistoryStatus()
	{
		$status = $this->getSendinTrackingHistoryStatus('history', Mage::app()->getStore()->getStoreId());
		if (!$status) {
			return false;
		}
		return $status;
	}

	/**
	* functions used for getting userlists
	*/
	public function getUserlists()
	{
		$userlist = $this->getGeneralConfig('list', Mage::app()->getStore()->getStoreId());
		if (!$userlist)
			return false;
		return $userlist;
	}

	/**
	* functions used for getting importOldSubscribers status
	*/
	public function getImportOldSubsStatus()
	{
		$importStatus = $this->getGeneralConfig('importOldUserStatus', Mage::app()->getStore()->getStoreId());
		if (!$importStatus)
			return false;
		return $importStatus;
	}

	/**
	* functions used for get templateid
	*/
	public function getTemplateId()
	{
		$TemplateId = $this->getGeneralConfig('SendinTemplateId', Mage::app()->getStore()->getStoreId());
		if (!$TemplateId)
			return false;
		return $TemplateId;
	}
	/**
	* functions used for get value final email recive.
	*/
	public function getFinalTemplate()
	{
		$FinalTemplate = $this->getGeneralConfig('SendinTemplateFinal', Mage::app()->getStore()->getStoreId());
		if (!$FinalTemplate)
			return false;
		return $FinalTemplate;
	}
	/**
	* functions used for get value subscribe type like doubleoptin and simple.
	*/
	public function getSubscribeConfirmType()
	{
		$SubscribeConfirmType = $this->getGeneralConfig('SendinSubscribeConfirmType', Mage::app()->getStore()->getStoreId());
		if (!$SubscribeConfirmType)
			return false;
		return $SubscribeConfirmType;
	}
	/**
	* functions used for get value for redirect url
	*/
	public function getOptinRedirectUrlCheck()
	{
		$OptinRedirectUrlCheck = $this->getGeneralConfig('SendinOptinRedirectUrlCheck', Mage::app()->getStore()->getStoreId());
		if (!$OptinRedirectUrlCheck)
			return false;
		return $OptinRedirectUrlCheck;
	}
	/**
	* functions used for get double optin redirect url after click email link.
	*/
	public function getSendinDoubleoptinRedirectUrl()
	{
		$SendinDoubleoptinRedirectUr = $this->getGeneralConfig('SendinDoubleoptinRedirectUrl', Mage::app()->getStore()->getStoreId());
		if (!$SendinDoubleoptinRedirectUr)
			return false;
		return $SendinDoubleoptinRedirectUr;
	}
	/**
	* functions used for get final confirmation email for double optin functionality.
	*/
	public function getSendinFinalConfirmEmail()
	{
		$SendinFinalConfirmEmail = $this->getGeneralConfig('SendinFinalConfirmEmail', Mage::app()->getStore()->getStoreId());
		if (!$SendinFinalConfirmEmail)
			return false;
		return $SendinFinalConfirmEmail;
	}

    /**
	* functions used for get doubleoptin id geting by sendinblue.
	*/
	public function getSendinOptinListId()
	{
		$SendinOptinListId = $this->getGeneralConfig('SendinOptinListId', Mage::app()->getStore()->getStoreId());
		if (!$SendinOptinListId)
			return false;
		return $SendinOptinListId;
	}

	/**
	* functions used for getting general config
	*/
	public function getGeneralConfig($field, $store = null)
	{
		return Mage::getStoreConfig('sendinblue/'.$field, $store);
	}

    /**
     * functions used for get sendinsmtp status
     */
	public function getSendinSmtpStatus($field, $store = null)
	{
		return Mage::getStoreConfig('sendinblue/smtp/'.$field, $store);
	}

	 /**
     * functions used for get sendinblue email status
     */
    public function getSyncronizeStatus()
	{
		return $this->getGeneralConfig('syncronize', Mage::app()->getStore()->getStoreId());
	}
	/**
	* functions used for get sendin tracking status
	*/
	public function getSendinTrackingCodeStatus($field, $store = null)
	{
		return Mage::getStoreConfig('sendinblue/tracking/'.$field, $store);
	}

    /**
	* functions used for get sendin tracking history status
	*/
	public function getSendinTrackingHistoryStatus($field, $store = null)
	{
		return Mage::getStoreConfig('sendinblue/improt/'.$field, $store);
	}

	/**
	* functions used for module functionality
	*/
	public function getLists()
	{
		return $this->lists();
	}

    /**
     * functions used for email adds
     */
	public function emailAdd($email, $extra, $is_subscribed = '', $list_id = '')
	{
		$attributesName = $this->allAttributesName();
		if ($this->module_enable == 1 && $this->getSyncronizeStatus())
		{
			$apikey = $this->api_key;
			if (!$apikey)
				return false;

			$Sendin_Confirm_Type = Mage::getStoreConfig('sendinblue/SendinSubscribeConfirmType');
			if (empty($list_id)) {
	            if (isset($Sendin_Confirm_Type) && $Sendin_Confirm_Type === 'doubleoptin') {
	                $list_id = Mage::getStoreConfig('sendinblue/SendinOptinListId');
	            } else {
	                $list_id = $this->lists_ids;
	            }
        	}
			$params = array();
			$params['email'] = $email;
			$params['id'] = '';
			if ($is_subscribed != '')
			$params['blacklisted'] = 0;

			if ($extra != null)
			{
				$key_value = array_keys($attributesName);
				$attrName = implode('|', $key_value);				
				$params['attributes_name'] = $attrName;
				$attrValue = implode('|', $extra);
				$params['attributes_value'] = $attrValue;
			} else
			{
				$params['attributes_value'] = $email;
			}
			$params['listid'] = $list_id;

			return $this->callServer('USERCREADITM', $params);
		} else
			return false;
		}

	 /**
	 * functions subscribeuser
	 */
	public function emailSubscribe($email)
	{
		if ($this->module_enable == 1 && $this->getSyncronizeStatus())
		{
			$apikey = $this->api_key;
			$timezone = Mage::app()->getStore()->getConfig('general/locale/timezone');
			$timez = str_replace('Calcutta', 'Kolkata', $timezone);
			$tm = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));

			if (!$apikey)
				return false;
			$data = array();
			$data['key'] = $apikey;
			$data['webaction'] = 'UPDATE-USER-SUBSCRIPTION-STATUS';
			$data['timezone'] = $timez;
			$data['user_status'] = $email.', '.'1'.', '.$tm;
			return $this->curlRequest($data);
		} else
			return false;
		}

	/**
	* functions used for sync data
	*/
	public function syncData()
	{ 
	if ($this->module_enable == 1 && $this->getSyncronizeStatus())
		{
		$apikey = $this->api_key;
			if (!$apikey)
				return false;
			$params = array();
			$params['listids'] = str_replace(',', '|', $this->lists_ids);
			$response = $this->callServer('DISPLAYLISTDATABLACK', $params);
			$result_arr = $response->result;
			$collection = Mage::getResourceModel('newsletter/subscriber_collection')->showStoreInfo()->showCustomerInfo()->toArray();
			$subscriber_data = $collection['items'];
			$emails = array();			
			$subscriber_data_email = array();
			foreach($subscriber_data as $s)
			{
				$subscriber_data_email[$s['subscriber_email']] = $s;
            }

			if (count($result_arr) > 0)
			{
				$newsletterSub = Mage::getModel('newsletter/subscriber');
				foreach ($result_arr as $key => $value)
				{
					foreach ($value as $user_data)
					{
						 if(isset($subscriber_data_email[$user_data->email]))
						 {
							// on a trouvé le subscriber magento
							$data = $subscriber_data_email[$user_data->email];
							$temp_sub_status = ($data['subscriber_status'] == 3) ? 1 : 0;
							if ($temp_sub_status != $user_data->blacklisted)
							{
								$emails[] = $data['subscriber_email'];
								$subscribe_data['subscriber_id'] = $data['subscriber_id'];
								$subscribe_data['subscriber_status'] = ($user_data->blacklisted == 1)?3:1;
								$costomer_data = $newsletterSub->loadByEmail($data['subscriber_email']);
								$costomer_data->setStatus($subscribe_data['subscriber_status']);
								$costomer_data->setIsStatusChanged(true);
								$costomer_data->save();
							}
						}
					}
				}
			}
			if (count($emails) > 0)
				Mage::getModel('core/session')->addSuccess(count($emails).Mage::helper('sendinblue')->__(' Total of record(s) have been updated'));
			else
				Mage::getModel('core/session')->addSuccess(count($emails).Mage::helper('sendinblue')->__(' Total of record(s) have been updated'));
			return true;
		}
		else
			return false;
	}
    /**
     * This method is used for add email list
     */
    public function addEmailList($email, $list_id = '')
    {
        if ($this->module_enable == 1 && $this->getSyncronizeStatus())
        {
            $apikey = $this->api_key;
            if (!$apikey)
                return false;

            $Sendin_Confirm_Type = Mage::getStoreConfig('sendinblue/SendinSubscribeConfirmType');
			if (empty($list_id)) {
	            if (isset($Sendin_Confirm_Type) && $Sendin_Confirm_Type === 'doubleoptin') {
	                $list_id = Mage::getStoreConfig('sendinblue/SendinOptinListId');
	            } else {
	                $list_id = $this->lists_ids;
	            }
        	}
            $params = array();
            $params['email'] = $email;
            $params['id'] = '';
            $params['blacklisted'] = 0;
            $params['attributes_name']  = '';
            $params['attributes_value'] = '';
            $params['listid'] = $list_id;
            return $this->callServer('USERCREADITM', $params);
        } else
            return false;
    }
    /**
     * This method is used used for email unsubscribe
     */
    public function emailDelete($email)
    {
        if ($this->module_enable == 1 && $this->getSyncronizeStatus())
        {
            $apikey = $this->api_key;
            if (!$apikey)
                return false;
            $params = array();
            $params['email']  = $email;
            $params['listid'] = $this->lists_ids;
            return $this->callServer('UNSUBAPI', $params);
        } else
            return false;
    }

	/**
	* This method is used used for check api status
	*/
	public function checkApikey($api_key)
	{
		$params['key'] = $api_key;
		$response = $this->callServer('DISPLAYLISTDATA', $params);
		if (isset($response->errorMsg) && !empty($response->errorMsg))
			return $lists['error'] = $response->errorMsg;
	}

	/**
	* functions used for smtp details and order tracking
	*/
	public function SmtpDetailsWithTracking()
	{
		$params['key'] = $this->api_key; 
		$response = $this->callServer('TRACKINGDATA', $params);
		if (isset($response->errorMsg) && !empty($response->errorMsg))
		{
			$tracking['error'] = $response->errorMsg;
			return $tracking;
		}
		return $response;
	}

    /**
     * Fetches all the list of the user from the Sendinblue platform.
     */
    public function lists($filters = array())
    {
        $params   = array();
        $response = $this->callServer('DISPLAYLISTDATA', $params);
        if (isset($response->errorMsg) && !empty($response->errorMsg))
        {
            $this->error_message = $response->errorMsg;
            $lists['error'] = $response->errorMsg;
        } else
        {
            $i = 0;
            $lists = array();
            foreach ($response->result as $list_data)
            {
                $lists[$i]['id'] = $list_data->id;
                $lists[$i]['name'] = $list_data->name;
                $i++;
            }
        }
        return $lists;
    }

    /**
     * Fetches the list status of the user from the Sendinblue platform.
     */
    public function getUserListStats()
    {
        if ($this->module_enable == 1)
        {
            $params = array();
            $params['list'] = 'ALL';
            return $this->callServer('DISPLAYLISTDATA', $params);
        } else
            return Mage::getModel('core/session')->addError('Sendinblue not enabled');
    }

    /**
     * Fetches all folders and all list within each folder of the user's Sendinblue 
     * account and displays them to the user. 
     */
    public function checkFolderListDoubleoptin()
    {
        $params = array();
        $s_array = array();
        $list_response = $this->callServer('DISPLAY-FOLDERS-LISTS', $params);
        $list_response = json_encode($list_response);
        $res = json_decode($list_response, true);
        if (isset($res) && !empty($res))
        {
            foreach ($res as $value)
            {
                if (strtolower($value['name']) == 'form')
                {
		            if (!empty($value['lists']))
		            {
		                foreach ($value['lists'] as $key => $val)
		                {
		                    if ($val['name'] == 'Temp - DOUBLE OPTIN')
		                        $s_array['optin_id'] = $key;
		                }
		            }
                }
            }
            if (count($s_array) > 0) {
                $return = $s_array;
            } else {
                $return = false;
            }
        }
        return $return;
    }
    /**
     * Create temporary doubleoptin list if not exist in Sendinblue.
     */
    public function createListIdDoubleoptin()
    {
    	$data = array();
    	$data['foldername'] = 'FORM';
		$res = $this->callServer('ADDFOLDER', $data);
		$folder_id = $res->folder_id;
		if (!empty($folder_id)) {
		    $params = array();
		    $params['listname'] = 'Temp - DOUBLE OPTIN';
		    $params['list_parent'] = $folder_id;
		    $list_response = $this->callServer('NEWLIST', $params);
		    $list_id = $list_response->result;
		}
		return $list_id;
    }

    /**
     * Fetches all folders and all list within each folder of the user's Sendinblue 
     * account and displays them to the user. 
     */
    public function checkFolderList()
    {
        $params = array();
        $array = array();
        $list_response = $this->callServer('DISPLAY-FOLDERS-LISTS', $params);
        $list_response = json_encode($list_response);
        $res = json_decode($list_response, true);
        if (isset($res) && !empty($res))
        {
            foreach ($res as $key => $value)
            {
                if (strtolower($value['name']) == 'magento')
                {
                    $array[] = $key;
                    $array[] = $value['name'];
                }
                if (!empty($value['lists']))
                {
                    foreach ($value['lists'] as $val)
                    {
                        if (strtolower($val['name']) == 'magento')
                            $array[] = $val['name'];
                    }
                }
            }
        }
        return $array;
    }
    /**
     *  folder create in Sendinblue after removing from Sendinblue
     */
    public function createFolderCaseTwo()
    {
        $apikey = $this->api_key;
         if($apikey == '')
			return false;
        $response = $this->checkApikey($apikey); // check api key is valid or not
        if ($this->module_enable != 1 && $apikey == '' && $response['error'] != '' && $this->getSyncronizeStatus())
				return false;
            $result = $this->checkFolderList();
            $list_name = 'magento';
            $param = array();
            $data  = array();
            $folder_id = $result[0];
            $exist_list = $result[2];
            if (empty($result[1]))
            {
                $params = array();
                $params['foldername'] = 'magento';
                $res = $this->callServer('ADDFOLDER', $params);
                $folder_id = $res->folder_id;
                $params = array();
                $params['listname'] = $list_name;
                $params['list_parent'] = $folder_id; //folder id
                $list_response = $this->callServer('NEWLIST', $params);
                $this->sendAllMailIDToSendin($list_response);
            } elseif (empty($exist_list))
            {
                $params = array();
                $params['listname'] = $list_name;
                $params['list_parent'] = $folder_id; //folder id
                $list_response = $this->callServer('NEWLIST', $params);
                $this->sendAllMailIDToSendin($list_response);
            }
    }

    /**
     *  folder create in Sendinblue after installing
     */
    public function createFolderName($api_key)
    {
        $this->api_key = $api_key;		
        $this->createAttributesName();
		$result = $this->checkFolderList();
        if (empty($result[1]))
        {
            $params = array();
            $params['foldername'] = 'magento';
            $res = $this->callServer('ADDFOLDER', $params);
            $folder_id = $res->folder_id;
            $exist_list = '';
        } else
        {
            $folder_id  = $result[0];
            $exist_list = $result[2];
        }
        $this->createNewList($folder_id, $exist_list);
        $this->partnerMagento();
    }

    /**
     * Method is used to add the partner's name in Sendinblue.
     * In this case its "MAGENTO".
     */
	public function partnerMagento()
	{
		$params = array();
		$params['partner'] = 'MAGENTO';
		$this->callServer('MAILIN-PARTNER', $params);
	}

    /**
     * Creates a list by the name "magento" on user's Sendinblue account.
     */
   public function createNewList($response, $exist_list)
   {
        if ($exist_list != '')
        {
            $date     = date('dmY');
            $list_name = 'magento_'.$date;
        }
        else
		$list_name = 'magento';
        $params = array();
        $params['listname'] = $list_name;
        $params['list_parent'] = $response;
        $list_response = $this->callServer('NEWLIST', $params);
        $this->sendAllMailIDToSendin($list_response);
        $this->createAttributesName();
    }

    /**
     * Fetch attributes and their values
     * on Sendinblue platform. This is necessary for the Prestashop to add subscriber's details.
     */
   public function allAttributesName()
   {
		if ($this->user_language == 'fr')
			$params = array('PRENOM'=>'firstname', 'NOM'=>'lastname', 'MAGENTO_LANG'=>'created_in','CLIENT'=>'client','SMS'=>'telephone','COMPANY'=>'company','CITY'=>'city','COUNTRY_ID'=>'country_id','POSTCODE'=>'postcode','STREET'=>'street','REGION'=>'region','STORE_ID'=>'store_id');
		else
			$params = array('NAME'=>'firstname', 'SURNAME'=>'lastname', 'MAGENTO_LANG'=>'created_in','CLIENT'=>'client','SMS'=>'telephone','COMPANY'=>'company','CITY'=>'city','COUNTRY_ID'=>'country_id','POSTCODE'=>'postcode','STREET'=>'street','REGION'=>'region','STORE_ID'=>'store_id');
		return $params;
    }

	/**
	* Fetch attributes name and type
	* on Sendinblue platform. This is necessary for the Prestashop to add subscriber's details.
	*/
   public function allAttributesType()
   {
		if ($this->user_language == 'fr')
			$params = array('PRENOM'=>'text', 'NOM'=>'text', 'MAGENTO_LANG'=>'text','CLIENT'=>'number','SMS'=>'text','COMPANY'=>'text','CITY'=>'text','COUNTRY_ID'=>'text','POSTCODE'=>'number','STREET'=>'text','REGION'=>'text','STORE_ID'=>'number');
		else
			$params = array('NAME'=>'text', 'SURNAME'=>'text', 'MAGENTO_LANG'=>'text','CLIENT'=>'number','SMS'=>'text','COMPANY'=>'text','CITY'=>'text','COUNTRY_ID'=>'text','POSTCODE'=>'number','STREET'=>'text','REGION'=>'text','STORE_ID'=>'number');
		return $params;
    }

	/**
	* Fetch all Transactional Attributes 
	* on Sendinblue platform. This is necessary for the Prestashop to add subscriber's details.
	*/
   public function allTransactionalAttributes()
   {
		$params = array('ORDER_ID'=>'id', 'ORDER_DATE'=>'date', 'ORDER_PRICE'=>'number');
		return $params;
    }

	/**
	* Create Normal, Transactional, Calculated and Global attributes and their values
	* on Sendinblue platform. This is necessary for the Prestashop to add subscriber's details.
	*/
   public function createAttributesName()
   {
        $params = array();
        $attrData = array();
        $transData = array();
        $attributesName = $this->allAttributesName();
        $attributesType = $this->allAttributesType();
        $tranArray = $this->allTransactionalAttributes();
        $keyValue = array_keys($attributesName);
        foreach ($keyValue as $value)
        {
			$attr = $attributesType[$value];
			$attrData[] = $value.','.$attr;			
		}
		$normalAttributes = implode('|', $attrData);
		$tranKey = array_keys($tranArray);
		$params['normal_attributes'] = $normalAttributes;
		foreach ($tranKey as $value)
        {
			$attr = $tranArray[$value];
			$transData[] = $value.','.$attr;			
		}
		$transAttributes = implode('|', $transData);
        $params['transactional_attributes'] = $transAttributes;
		$this->callServer('ATTRIBUTES_CREATION', $params);
    }
	/**
     * Method is used to send all the subscribers from magento to
     * Sendinblue for adding / updating purpose.
     */
    public function sendAllMailIDToSendin($list)
    {
        $sendin_switch = new Mage_Core_Model_Config();
        $allemail = $this->getcustomers();
        if ($allemail > 0)
        {
			$params = array();
			$params['webaction'] = 'IMPORTUSERS';
			$params['key'] = $this->api_key;
			$params['url'] = Mage::getBaseUrl('media').'sendinblue_csv/ImportSubUsersToSendinblue.csv';
			$params['listids'] = $list->result;
			$params['notify_url'] = Mage::getBaseUrl().'sendinblue/ajax/emptySubsUserToSendinblue';
			$responce_data = $this->curlRequestAsyc($params);
			$res_value = json_decode($responce_data);
			$sendin_switch->saveConfig('sendinblue/importOldUserStatus', 0, 'default', 0);
			if (empty($res_value->process_id))
			{
				$sendin_switch->saveConfig('sendinblue/importOldUserStatus', 1);
			}						
		}
		$sendin_switch->saveConfig('sendinblue/list', $list->result, 'default', 0);
    }

	/**
	* Send SMS from Sendin.
	*/
	public function sendSmsApi($array)
	{
		$params = array();
		$params['key'] = $this->api_key;
		$params['to'] = $array['to'];
		$params['from'] = $array['from'];
		$params['text'] = $array['text'];
		return $this->callServer('SENDSMS', $params);
	}
	
    public function sendOrder($mobile)
    {      
		$sendin_switch = new Mage_Core_Model_Config();
		if (isset($mobile))
		{
			$arr = array();
			$arr['to'] = $mobile;
			$arr['from'] = $this->getSendSmsOrderSubject();
			$arr['text'] = $this->getSendSmsmOrderMessage();

			return $result = $this->sendSmsApi($arr);			
		}
    }

    public function notifySmsEmail()
    {
		$sendin_switch = new Mage_Core_Model_Config();

		if($this->getSmsCredit() < $this->getNotifyValueStatus() && $this->module_enable == 1 && $this->getNotifySmsStatus() == 1)
		{
			if($this->getNotifyCronStatus() == 0)
			{ 
				$sendin_switch->saveConfig('sendinblue/Sendin_Notify_Cron_Executed', 1, 'default', 0);	
				$locale = Mage::app()->getLocale()->getLocaleCode();
				$email_template_variables = array();
				if ($locale == 'fr_FR')
				{
					$email_template_variables['text0'] = ' [SendinBlue] Alerte: Vos crédits SMS seront bientôt épuisés';
					$sender_name = 'SendinBlue';
					$sender_email = 'contact@sendinblue.com';
				}
				else
				{   
					$email_template_variables['text0'] = '[SendinBlue] Alert: You do not have enough credits SMS';
					$sender_name = 'SendinBlue';
					$sender_email = 'contact@sendinblue.com';
				}
				$email = $this->getNotifyEmailStatus();
				
				$email_template = Mage::getModel('core/email_template')->loadDefault('notification_template');
				$temp = $email_template->template_text;
				$web_site = Mage::app()->getWebsite()->getName();
				$credit = $this->getSmsCredit();
				preg_match_all('#{(.*)}#', $temp, $match);
				
				$temp_params = array(
				'{site_name}'=>$web_site,
				'{present_credit}'=>$credit					
				);
				foreach($match[0] as $var=>$value){ 
					$temp = preg_replace('#'.$value.'#',$temp_params[$value],$temp);
				}
				$email_template->template_text = $temp;
				$email_template->getProcessedTemplate($email_template_variables);
				$email_template->setSenderName($sender_name);
				$email_template->setSenderEmail($sender_email);
				$email_template->setTemplateSubject($email_template_variables['text0']);
				$email_template->send($email, '', $email_template_variables);
			}			
		}
		else
		{
			$sendin_switch->saveConfig('sendinblue/Sendin_Notify_Cron_Executed', 0, 'default', 0);
		}
		
		Mage::getModel('core/session')->addSuccess(Mage::helper('sendinblue')->__('Notification mail has been sent'));
	}

	/**
     * show  SMS  credit from Sendinblue.
     */
	public function getSmsCredit()
	{
		$params = array();
		$params['key'] = $this->api_key;
		$result = $this->callServer('USER-CURRENT-PLAN', $params);
		
		foreach($result as $val)
		{
			if(is_object($val)){
				if($val->plan_type=='SMS')
				{
					return $val->credits;
				}
			}
		}
	}

    /**
     * Method is used to send test email to the user.
     */
    public function sendTestMail($email)
    {
        $locale = Mage::app()->getLocale()->getLocaleCode();
        $email_template_variables = array();
        if ($locale == 'fr_FR')
        {
            $email_template_variables['text0'] = '[SendinBlue SMTP] e-mail de test';
            $sender_name = 'SendinBlue';
            $sender_email = 'contact@sendinblue.com';
        }
        else
        {   
            $email_template_variables['text0'] = '[SendinBlue SMTP] test email';
            $sender_name = 'SendinBlue';
            $sender_email = 'contact@sendinblue.com';
        }
		try {
        $email_template = Mage::getModel('core/email_template')->loadDefault('custom_template');
		$email_template->getProcessedTemplate($email_template_variables);
        $email_template->setSenderName($sender_name);
        $email_template->setSenderEmail($sender_email);
        $email_template->setTemplateSubject($email_template_variables['text0']);
		return $email_template->send($email, '', $email_template_variables);
		}
		catch(Exception $e) {
			
		}
    }

     /**
     *  This method is used to compare key and value 
     * return all value in array whose present in array key
     */
    public function merge_my_array($one, $two, $email = "")
    {
		$resArr = $email ? array('EMAIL'=> $email) : array();
		if (count($one) > 0) {
			foreach($one as $k => $v) {
				$resArr[$k] = isset($two[$v])?str_replace(';',',', $two[$v]):'';
			}
		}
		return $resArr;
	}

    /**
     *  This method is used to fetch all users from the default customer table to list
     * them in the Sendinblue magento module.
     */
    public function getcustomers()
    {
        $data = array();
        $customer_addr_data = array();
        $attributesName = $this->allAttributesName();
        $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('email')->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->addAttributeToSelect('created_in');
		$customerAddressCollection = Mage::getModel('customer/address');
        foreach ($collection as $customer)
        {
            $start_detail = array();
            $resp = array();
            $respone = array();

            $respone = $customer->getData();
            $email  = $respone['email'];
            $cid = $respone['entity_id'];
            $user_lang = $respone['created_in'];

            $collectionAddress = $customerAddressCollection->getCollection()->addAttributeToSelect('telephone')->addAttributeToSelect('country_id')->addAttributeToSelect('company')->addAttributeToSelect('street')->addAttributeToSelect('postcode')->addAttributeToSelect('region')->addAttributeToSelect('city')->addAttributeToFilter('parent_id',(int)$cid);
            $telephone = '';
            $customer_addr = array();
            foreach ($collectionAddress as $customerPhno) {
				$customer_addr = $customerPhno->getData();
				$customer_addr['client'] = $cid>0?1:0;
            }
            $customer_addr_data[$email] = array_merge($respone, $customer_addr);
        }
        $newsletterArr = array();
        $newsletter = Mage::getResourceModel('newsletter/subscriber_collection')->addFieldToFilter('subscriber_status', array('eq' => 1))->load();
		$cnt = 0;
        foreach ( $newsletter->getItems() as $subscriber)
        {
			$subsdata = $subscriber->getData();
            $subscriber_email = $subsdata['subscriber_email'];
            
			if ( !empty($customer_addr_data[$subscriber_email]) ) {
			$customer_addr_data[$subscriber_email]['email'] = $subscriber_email;
			$resp[$cnt] = $this->merge_my_array($attributesName, $customer_addr_data[$subscriber_email], $subscriber_email);
			}
			else
			{
				$newsletterArr['client'] = $subsdata['customer_id']>0?1:0;
				$resp[$cnt] = $this->merge_my_array($attributesName, $newsletterArr, $subscriber_email);
				$resp[$cnt]['STORE_ID'] = $subsdata['store_id'];
			}
                $cnt++;
                
	}

     if (!is_dir(Mage::getBaseDir('media').'/sendinblue_csv'))
        mkdir(Mage::getBaseDir('media').'/sendinblue_csv', 0777, true);

		$handle = fopen(Mage::getBaseDir('media').'/sendinblue_csv/ImportSubUsersToSendinblue.csv', 'w+');
		$key_value = array_keys($attributesName);
		array_splice($key_value, 0, 0, 'EMAIL');
		
		fwrite($handle, implode(';', $key_value)."\n");

		foreach ($resp as $newsdata)
		{
			if(!empty($newsdata['COUNTRY_ID']) && !empty($newsdata['SMS']))
			{
				$country_id = $this->getCountryCode($newsdata['COUNTRY_ID']);
				$newsdata['SMS'] = $this->checkMobileNumber($newsdata['SMS'], $country_id);
			}
			$key_value = $newsdata;
			fwrite($handle, str_replace("\n", "",implode(';', $key_value))."\n");
		}
		fclose($handle);
		$total_value = count($resp);
		return $total_value;
    }

    /**
     *  This method is used to fetch all users from the default newsletter table to list
     * them in the Sendinblue magento module.
     */
	public function getNewsletterSubscribe($start, $per_page)
	{
		$customer_addr_data = array();
        $attributesName = $this->allAttributesName();
        $attributesName['email'] = 'email';
        $attributesName['customer_id'] = 'entity_id';
        
        $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('email');
		$customerAddressCollection = Mage::getModel('customer/address');
        foreach ($collection as $customer)
        {
            $start_detail = array();
            $respone = array();
            $respone = $customer->getData();
            $email  = $respone['email'];
            $cid = $respone['entity_id'];
            $user_lang = $respone['created_in'];

            $collectionAddress = $customerAddressCollection->getCollection()->addAttributeToSelect('telephone')->addAttributeToSelect('country_id')->addAttributeToFilter('parent_id',(int)$cid);
            $telephone = '';
            $customer_addr = array();
            foreach ($collectionAddress as $customerPhno) {
				$customer_addr = $customerPhno->getData();
				if (!empty($customer_addr['telephone']) && !empty($customer_addr['country_id']))
				{
					$country_code = $this->getCountryCode($customer_addr['country_id']);
					$customer_addr['telephone'] = $this->checkMobileNumber($customer_addr['telephone'], $country_code);
				}
				$customer_addr['client'] = $cid>0?1:0;
            }
            $customer_addr_data[$email] = array_merge($respone, $customer_addr);
        }

        $newsletterArr = array();
        $newsletter = Mage::getResourceModel('newsletter/subscriber_collection')->load();
		$cnt = 0;
		$resp = array();
        foreach ( $newsletter->getItems() as $subscriber)
        {
			$subsdata = $subscriber->getData();
            $subscriber_email = $subsdata['subscriber_email'];
            $subscriber_status = $subsdata['subscriber_status'];
			if ( !empty($customer_addr_data[$subscriber_email]) ) {
				$resp[$cnt] = $this->merge_my_array($attributesName, $customer_addr_data[$subscriber_email]);
				unset($customer_addr_data[$subscriber_email]);
				$resp[$cnt]['subscriber_status'] = $subscriber_status;
				$resp[$cnt]['email'] = $subscriber_email;
			}
			else
			{
				$newsletterArr['client'] = $subsdata['customer_id']>0?1:0;
				$resp[$cnt] = $this->merge_my_array($attributesName, $newsletterArr);
				$resp[$cnt]['subscriber_status'] = $subscriber_status;
				$resp[$cnt]['email'] = $subscriber_email;
			}
                $cnt++;
		}
		if (count($customer_addr_data) > 0) {
			foreach ($customer_addr_data as $email => $cVal) {
				$resp[$cnt] = $this->merge_my_array($attributesName, $cVal);
				$resp[$cnt]['subscriber_status'] = 3;
				$resp[$cnt]['email'] = $email;

				$cnt++;
			}
		}
		return array_slice($resp, $start, $per_page, true);		
    }

    /**
     *  This method is used to fetch total count unsubscribe users from the default newsletter table to list
     * them in the Sendinblue magento module.
     */
	public function getNewsletterUnSubscribeCount()
	{
		$coreResource = Mage::getSingleton('core/resource');
		$tableCustomer = $coreResource->getTableName('customer/entity');
		$tableNewsletter = $coreResource->getTableName('newsletter/subscriber');
		$sql = 'SELECT count(*) as totalcoutn FROM '.$tableCustomer.' CE 
		LEFT JOIN '.$tableNewsletter.' NS
		ON CE.entity_id=NS.customer_id WHERE subscriber_status != 1 or subscriber_status is null';
		$unsubs_count1 = $coreResource->getConnection('core_read')->fetchRow($sql);

		$sql = 'SELECT count(*) as totalcoutn FROM '.$tableNewsletter.' WHERE customer_id = 0 AND subscriber_status = 3';
		$unsubs_count2 = $coreResource->getConnection('core_read')->fetchRow($sql);
		return ($unsubs_count1['totalcoutn'] + $unsubs_count2['totalcoutn']);            
	}

	/**
     *  This method is used to fetch total count subscribe users from the default newsletter table to list
     * them in the Sendinblue magento module.
    */
	public function getNewsletterSubscribeCount()
	{
		$coreResource = Mage::getSingleton('core/resource');
		$tableNewsletter = $coreResource->getTableName('newsletter/subscriber');
		$sql = 'SELECT count(*) as totalvalue from '.$tableNewsletter.' where subscriber_status = 1';
        $data = $coreResource->getConnection('core_read')->fetchRow($sql);
		return $data['totalvalue'];
	}

    /**
     * This method is used to check the subscriber's newsletter subscription status in Sendinblue
     */
    public function checkUserSendinStatus($result)
    { 
        $userstatus = array();
        foreach ($result as $subscriber)
            $userstatus[] = $subscriber['email'];
        $email = implode(',', $userstatus);
        $params = array();
        $params['key']   = $this->api_key;
        $params['email'] = $email;
        $response = $this->callServer('USERS-STATUS-BLACKLIST', $params);
        $response = json_encode($response);
        return json_decode($response, true);
    }

    /**
     * Fetches the SMTP and order tracking details
     */
    public function TrackingSmtp()
    {
        $params = array();
        $params['key'] = $this->api_key;
        return $this->callServer('TRACKINGDATA', $params);
    }

    /**
     * CURL function to send request to the Sendinblue API server
     */
   public function callServer($method, $params)
   {
        $host = $this->api_url;
        $params['key'] = (isset($params['key']) && !empty($params['key'])) ? $params['key'] : $this->api_key;
        $params['webaction'] = $method;
        $this->error_message = '';
        $this->error_code = '';
        $response = $this->curlRequest($params);
        return json_decode($response);
   }

    /**
     * CURL function to send request to the Sendinblue API server
     */
	public function curlRequest($data)
	{
		$url = $this->api_url; // WS URL
		$ch    = curl_init();
		$ndata = '';
        $data['source'] = 'MagentoNew';
		if (is_array($data))
		{
			foreach ($data as $key => $value)
				$ndata .= $key.'='.urlencode($value).'&';
		}
		else
		$ndata = $data;
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Expect:', 'sib-plugin:magento-1.2.8'
		));	
		
		$ndata = trim($ndata,'&');		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $ndata);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data2 = curl_exec($ch);
		curl_close($ch);
		return $data2;
	}
     /**
     * CURL function to send request to the Sendinblue API server
     */
	public function curlRequestAsyc($data)
	{
		$url = $this->api_url; // WS URL
		$ch = curl_init();
		$ndata = '';
        $data['source'] = 'MagentoNew';
		if (is_array($data))
		{
			foreach ($data as $key => $value)
				$ndata .= $key.'='.urlencode($value).'&';
		}
		else
		$ndata = $data;
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Expect:', 'sib-plugin:magento-1.2.8'
		));	
		
		$ndata = trim($ndata,'&');		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $ndata);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data2 = curl_exec($ch);
		curl_close($ch);
		return $data2;
	}
	
	public function removeOldEntry()
	{
		$sendin_switch = new Mage_Core_Model_Config();
		$sendin_switch->saveConfig('sendinblue/smtp/status', '');
		$sendin_switch->saveConfig('sendinblue/smtp/authentication', '');
		$sendin_switch->saveConfig('sendinblue/smtp/username', '');
		$sendin_switch->saveConfig('sendinblue/smtp/password', '');
		$sendin_switch->saveConfig('sendinblue/smtp/host', '');
		$sendin_switch->saveConfig('sendinblue/smtp/port', '');
		$sendin_switch->saveConfig('sendinblue/smtp/ssl', '');
		$sendin_switch->saveConfig('sendinblue/smtp/option', '');
		$sendin_switch->saveConfig('sendinblue/tracking/code', '');
	}
	protected function _uninstallResourceDb($version)
    {
        Mage::dispatchEvent('module_uninstall', array('resource' => $this->_resourceName));        
        $this->_modifyResourceDb(self::TYPE_DB_UNINSTALL, $version, '');
        return $this;
    }

    /**
     *  This method is used to fetch all subscribe users from the default customer table to list
     * them in the Sendinblue magento module.
     */
    public function smsCampaignList()
    {
        $customer_addr_data = array();
        $attributesName = $this->allAttributesName();
        $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('email')->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->addAttributeToSelect('created_in');
		$customerAddressCollection = Mage::getModel('customer/address');
        foreach ($collection as $customer)
        {
            $start_detail = array();
            $resp = array();
            $respone = array();

            $respone = $customer->getData();
            $email  = $respone['email'];
            $cid = $respone['entity_id'];
            $user_lang = $respone['created_in'];

            $collectionAddress = $customerAddressCollection->getCollection()->addAttributeToSelect('telephone')->addAttributeToSelect('country_id')->addAttributeToSelect('company')->addAttributeToSelect('street')->addAttributeToSelect('postcode')->addAttributeToSelect('region')->addAttributeToSelect('city')->addAttributeToFilter('parent_id',(int)$cid);
            $telephone = '';
            $customer_addr = array();
            foreach ($collectionAddress as $customerPhno) {
				$customer_addr = $customerPhno->getData();
				if (!empty($customer_addr['telephone']) && !empty($customer_addr['country_id']))
				{
					$country_code = $this->getCountryCode($customer_addr['country_id']);
					$customer_addr['telephone'] = $this->checkMobileNumber($customer_addr['telephone'], $country_code);	  
				}
				$customer_addr['client'] = $cid>0?1:0;
            }
            $customer_addr_data[$email] = array_merge($respone, $customer_addr);
        }
        $newsletterArr = array();
        $newsletter = Mage::getResourceModel('newsletter/subscriber_collection')->addFieldToFilter('subscriber_status', array('eq' => 1))->load();
		$cnt = 0;
        foreach ( $newsletter->getItems() as $subscriber)
        {
			$subsdata = $subscriber->getData();
            $subscriber_email = $subsdata['subscriber_email'];
            $subscriber_status = $subsdata['subscriber_status'];
			if ( !empty($customer_addr_data[$subscriber_email]) ) {
				$customer_addr_data[$subscriber_email]['email'] = $subscriber_email;
				$resp[$cnt] = $this->merge_my_array($attributesName, $customer_addr_data[$subscriber_email]);
				$resp[$cnt]['EMAIL'] = $subscriber_email;
				$resp[$cnt]['subscriber_status'] = $subscriber_status;
			}
			else
			{
				$newsletterArr['client'] = $subsdata['customer_id']>0?1:0;
				$resp[$cnt] = $this->merge_my_array($attributesName, $newsletterArr);
				$resp[$cnt]['EMAIL'] = $subscriber_email;
				$resp[$cnt]['subscriber_status'] = $subscriber_status;
				$resp[$cnt]['STORE_ID'] = $subsdata['store_id'];
			}
                $cnt++;
	}
		$i = 0;
		$data = array();
		foreach($resp as $result) 
		{					
			if(!empty($result['SMS']))
			{ 
				$data[$i]= $result; 
			}
			  $i++;
		}
		return json_encode($data);
    }
    /**
    * API config value from SendinBlue.
    */
    public function getApiConfigValue()
    {
		$data = array();
		$data['key'] = $this->api_key;
		$data['webaction'] = 'PLUGIN-CONFIG';
		$value_config = $this->curlRequest($data);
		$result = json_decode($value_config);
		return $result;
    }

	/**
	* Send template email by sendinblue for newsletter subscriber user  .
	*/
	public function sendWsTemplateMail($to, $templateid = false)
	{
		$mail_url = "http://mysmtp.mailin.fr/ws/template/"; //Curl url

		$Sendin_Confirm_Type = $this->getSubscribeConfirmType();
		 if (empty($Sendin_Confirm_Type) || $Sendin_Confirm_Type == 'nocon') {
            return false;
        }
        if (!$templateid) {
            if ($Sendin_Confirm_Type == 'simplemail') {
                $temp_id_value = $this->getTemplateId();
                $templateid = !empty($temp_id_value) ? $temp_id_value : '';// should be the campaign id of template created on mailin. Please remember this template should be active than only it will be sent, otherwise it will return error.
            }

            if ($Sendin_Confirm_Type == 'doubleoptin') {
                $path_resp = '';
                $email_user = base64_encode($to);
                $path_resp = Mage::getBaseUrl().'sendinblue/ajax/mailResponce?value='.base64_encode($to);
                return $this->defaultDoubleoptinTemp($to, $path_resp);
            }
        }
		$smtpPassword = $this->getSmtpPassword();
		$user = $this->getUserName();
		//$to = str_replace('+', '%2B', $to);
		$post_data['to'] = $to;
		$post_data['key'] = $smtpPassword;
		$post_data['user'] = $user;
		$post_data['templateid'] = $templateid;
		$post_data = http_build_query($post_data);
		$ch = curl_init();

		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_URL, $mail_url);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		$return_data = curl_exec ($ch);
		curl_close ($ch);

		$res = json_decode($return_data, true);
		return $res;
	}
	/**
    * send double optin template and manage.
    */
    public function defaultDoubleoptinTemp($subscriber_email, $doubleoptin_url)
    {
        $locale = Mage::app()->getLocale()->getLocaleCode();
        $email_template_variables = array();
        if ($locale == 'fr_FR')
        {
            $email_template_variables['text0'] = 'Confirmez votre inscription';
            $sender_name = 'SendinBlue';
            $sender_email = 'contact@sendinblue.com';
        }
        else
        {   
            $email_template_variables['text0'] = 'Please confirm your subscription';
            $sender_name = 'SendinBlue';
            $sender_email = 'contact@sendinblue.com';
        }
		try {
				$email_template = Mage::getModel('core/email_template')->loadDefault('doubleoptin_template');
				$temp = $email_template->template_text;
				$web_site = Mage::app()->getWebsite()->getName();
				preg_match_all('#{(.*)}#', $temp, $match);

				$temp_params = array(
				'{double_optin}'=>$doubleoptin_url,
				'{site_name}'=>	$web_site			
				);
				foreach($match[0] as $var=>$value){ 
				$temp = preg_replace('#'.$value.'#',$temp_params[$value],$temp);
				}

				$email_template->template_text = $temp;
				$email_template->getProcessedTemplate($email_template_variables);
				$email_template->setSenderName($sender_name);
				$email_template->setSenderEmail($sender_email);
				$email_template->setTemplateSubject($email_template_variables['text0']);
				return $email_template->send($subscriber_email, '', $email_template_variables);

		}
		catch(Exception $e) {
			
		}
			
    }

	/**
	* Get all temlpate list id by sendinblue.
	*/
	public function templateDisplay()
	{
		$data = array();
		$data['key'] = $this->api_key;
		$data['webaction'] = 'CAMPAIGNDETAIL';
		$data['show'] = 'ALL';
		$data['messageType'] = 'template';
		return json_decode($this->curlRequest($data));
	}

	/**
	* Get getCountryCode from sendinblue_country table,
	*/
	public function getCountryCode($countryid)
	{
		$tableCountry = Mage::getSingleton('core/resource')->getTableName('sendinblue_country_codes');
		$sql = 'SELECT country_prefix  FROM '.$tableCountry.' WHERE iso_code = "'.$countryid.'"';
		$country_id = Mage::getSingleton('core/resource') ->getConnection('core_read')->fetchRow($sql);
		$country_prefix = $country_id['country_prefix'];
		return $country_prefix;
	}
}
