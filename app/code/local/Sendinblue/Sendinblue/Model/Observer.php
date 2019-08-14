<?php
/**
* @author Sendinblue plateform <contact@sendinblue.com>
* @copyright  2013-2014 Sendinblue
* URL:  https:www.sendinblue.com
* Do not edit or add to this file if you wish to upgrade Sendinblue Magento plugin to newer
* versions in the future. If you wish to customize Sendinblue magento plugin for your
* needs then we can't provide a technical support.
**/
class Sendinblue_Sendinblue_Model_Observer
{
	protected static $fields = array ();
	public function adminSubcriberDelete($observer)
	{
		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		if (isset($params['subscriber']) && count($params['subscriber'] > 0))
		{
			$customer_email = array();
			foreach ($params['subscriber'] as $costomer_id)
			{
				$costomer_data = Mage::getModel('newsletter/subscriber')->load($costomer_id)->toArray();
				$customer_email[] = empty($costomer_data['subscriber_email'])?array():$costomer_data['subscriber_email'];
			}
			$customer_emails = implode('|', $customer_email);
			$responce = Mage::getModel('sendinblue/sendinblue')->emailDelete($customer_emails);
		}
		if ($responce->result)
			Mage::getSingleton('core/session')->addSuccess('Total of '.$responce->result->unsubEmailsCounts.' record(s) were Unsubscribed');
		return $this;
	}
	public function adminCustomerDelete($observer)
	{
		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		if (isset($params['customer']) && count($params['customer'] > 0))
		{
			$customer_email = array();
			foreach ($params['customer'] as $costomer_id)
			{
				$costomer_data = Mage::getModel('customer/customer')->load($costomer_id)->toArray();
				$customer_email[] = empty($costomer_data['email'])?array():$costomer_data['email'];
			}
			$customer_emails = implode('|', $customer_email);
			$responce = Mage::getModel('sendinblue/sendinblue')->emailDelete($customer_emails);
		}
		if ($responce->result)
			Mage::getSingleton('core/session')->addSuccess('Total of '.$responce->result->unsubEmailsCounts.' record(s) were Unsubscribed');
		return $this;
	}
	public function adminCustomerSubscribe($observer)
	{
		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		if (isset($params['customer']) && count($params['customer'] > 0))
		{
			$customer_email = array();
			foreach ($params['customer'] as $costomer_id)
			{
				$costomer_data = Mage::getModel('customer/customer')->load($costomer_id)->toArray();
				$customer_email[] = empty($costomer_data['email'])?array():$costomer_data['email'];
			}
			$customer_emails = implode('|', $customer_email);
			$responce = Mage::getModel('sendinblue/sendinblue')->addEmailList($customer_emails);
		}
		if ($responce->result)
			Mage::getSingleton('core/session')->addSuccess('Total of '.$responce->result->infoUpdatedCount.' record(s) were subscribed');
		return $this;
	}
	public function subscribeObserver($observer)
	{
		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		if ($params['email'] != '')
		$newsletter_status = 0;
		
		$client = 0;
		$extra = ''.'|'.''.'|'.''.'|'.$client.'|'.'';		
		$responce = Mage::getModel('sendinblue/sendinblue')->emailAdd($params['email'], $extra, $newsletter_status);
		return $this;
	}
	public function updateNewObserver($observer)
	{
		$extra = null;	
		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		$cus_session = Mage::getSingleton('customer/session')->getCustomer();
		
		$customerSessionEmail = $cus_session->getEmail();
		$customer = $cus_session;
		if (empty($customerSessionEmail)) {
			$customer = $observer->getCustomer();
		}
		
		$cus_data = $cus_session->getData();
		$user_lang = isset($cus_data['created_in'])? $cus_data['created_in'] :'';
		
		$customerEmail = $customer->getEmail();
		$email = isset($params['email'])? $params['email'] : $customerEmail;
		
		$cid = $customer->getEntityid();
		$cid = isset($cid)?$cid:'';
		
		$fname = $customer->getFirstname();
		$fname = empty($fname)?'':$fname;
		$lname = $customer->getLastname();
		$lname = empty($lname)?'':$lname;

			$collectionAddress = Mage::getModel('customer/address')->getCollection()->addAttributeToSelect('telephone')->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->addAttributeToFilter('parent_id',$cid);

		$telephone = '';
		foreach ($collectionAddress as $customerPhno) {
			$phone_sms = $customerPhno->getData('telephone');
			$firstname_value = $customerPhno->getData('firstname');
			$lastname_value = $customerPhno->getData('lastname');
			$telephone = empty($phone_sms)?'':$phone_sms;
			$firstname = empty($firstname_value)?'':$firstname_value;
			$lastname = empty($lastname_value)?'':$lastname_value;
			
		}
		$telephone_no = isset($params['telephone'])?$params['telephone']:'';
		if (!empty($telephone_no) || (isset($params['default_billing']) && $params['default_billing'] == 1))
		{
			$country_idvalue = isset($params['country_id'])?$params['country_id']:'';
			if(!empty($params['country_id']))
			{
            $tableCountry = Mage::getSingleton('core/resource')->getTableName('sendinblue_country_codes');
			$sql = 'SELECT country_prefix  FROM '.$tableCountry.' WHERE iso_code = "'.$country_idvalue.'"';
            $country_id = Mage::getSingleton('core/resource') ->getConnection('core_read')->fetchRow($sql);
			}
			$country_id_code = empty($country_id['country_prefix'])?'':$country_id['country_prefix'];
			$telephone = Mage::getModel('sendinblue/sendinblue')->checkMobileNumber($telephone_no, $country_id_code);
		}
		else
		{	
			$telephone = !empty($telephone)?$telephone:'';
		}
		
		$firstname = empty($firstname)?'':$firstname;
		$lastname = empty($lastname)?'':$lastname;
		
		if (!empty($fname)|| !empty($lname))
			$client = 1;
		else
			$client = 0;
			
		$is_subscribed = isset($params['is_subscribed'])?$params['is_subscribed']:'';
		
		if (!empty($fname) || !empty($lname) || !empty($telephone) || !empty($email))
		{
			$costomer_data = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
			$nlStatus = $costomer_data->getStatus();
			
			$extra = $fname.'|'.$lname.'|'.$user_lang.'|'.$client.'|'.$telephone;
			if (isset($is_subscribed) && $is_subscribed == 1 && empty($nlStatus))
			{
				$responce = Mage::getModel('sendinblue/sendinblue')->emailAdd($email, $extra, $is_subscribed);
				Mage::getModel('sendinblue/sendinblue')->sendWsTemplateMail($email);
			}
			elseif (!empty($nlStatus))
			{
				$responce = Mage::getModel('sendinblue/sendinblue')->emailAdd($email, $extra);
			}	
		}
		
		if (isset($is_subscribed) && !empty($is_subscribed) && $is_subscribed === 0) {

			$responce = Mage::getModel('sendinblue/sendinblue')->emailDelete($email);
		}
		
		return $this;

	}
	public function syncData()
	{
		$responce = Mage::getModel('sendinblue/sendinblue')->syncData();
		return $this;
	}
	public function updateStatus($observer)
	{
		$order = $observer->getEvent()->getOrder();
		
		if ($order->getState() == Mage_Sales_Model_Order::STATE_PROCESSING) 
		{  
			$history = $order->getShipmentsCollection();
			$history_array=$history->toarray();
			if($history_array['totalRecords'] > 0)
			{
				$order_id = isset($history_array['items']['0']['order_id'])?$history_array['items']['0']['order_id']:'';
				$shippingaddrid = isset($history_array['items']['0']['shipping_address_id'])?$history_array['items']['0']['shipping_address_id']:'';
				$_order = Mage::getModel('sales/order')->load($order_id);
				$_shippingAddress = $_order->getShippingAddress();
				$locale = Mage::app()->getLocale()->getLocaleCode();
				$mobile_sms = $_shippingAddress->getTelephone();
				$mobile_sms = !empty($mobile_sms)?$mobile_sms:'';
				$countryid = $_shippingAddress->getCountryId();
				$countryid = !empty($countryid)?$countryid:'';
				$tableCountry = Mage::getSingleton('core/resource')->getTableName('sendinblue_country_codes');
				$sql = 'SELECT * FROM '.$tableCountry.' WHERE iso_code = "'.$countryid.'" ';
				$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
				$data = $connection->fetchRow($sql);
				$mobile = '';
				$country_prefix = $data['country_prefix'];
				if(isset($country_prefix) && !empty($country_prefix))				
				$mobile = Mage::getModel('sendinblue/sendinblue')->checkMobileNumber($mobile_sms,$country_prefix);
				$firstname = $_shippingAddress->getFirstname();
				$firstname = !empty($firstname )?$firstname :'';
				$lastname = $_shippingAddress->getLastname();
				$lastname = !empty($lastname)?$lastname:'';
				$ref_num = $_order->getIncrementId();
				$ref_num = !empty($ref_num)?$ref_num:'';
				$orderprice = $_order->getGrandTotal();
				$orderprice = !empty($orderprice)?$orderprice:'';
				$courrencycode = $_order->getBaseCurrencyCode();
				$courrencycode = !empty($courrencycode)?$courrencycode:'';
				$orderdate = $_order->getCreatedAt();
				$orderdate = !empty($orderdate)?$orderdate:'';
				if ($locale == 'fr_FR')
				$ord_date = date('d/m/Y', strtotime($orderdate));
				else
				$ord_date = date('m/d/Y', strtotime($orderdate));

				$total_pay = $orderprice.' '.$courrencycode;
				$msgbody = Mage::getModel('sendinblue/sendinblue')->getSendSmsShipingMessage();
				$fname = str_replace('{first_name}', $firstname, $msgbody);
				$lname = str_replace('{last_name}', $lastname."\r\n", $fname);
				$procuct_price = str_replace('{order_price}', $total_pay, $lname);
				$order_date = str_replace('{order_date}', $ord_date."\r\n", $procuct_price);
				$msgbody = str_replace('{order_reference}', $ref_num, $order_date);
				
				$arr = array();
				$arr['to'] = $mobile;
				$arr['from'] = Mage::getModel('sendinblue/sendinblue')->getSendSmsShipingSubject();
				$arr['text'] = $msgbody;
				Mage::getModel('sendinblue/sendinblue')->sendSmsApi($arr);
			}
		}
	}

	public function subscribedToNewsletter($observer)
    {
	  $data = $observer->subscriber;
	  $params = Mage::app()->getRequest()->getParams();
	  $params = empty($params)?array():$params;
	  if (!isset($params['firstname']) && !isset($params['lastname']))
	  {
		 if($data->subscriber_status == 3)
		  Mage::getModel('sendinblue/sendinblue')->emailDelete($data->subscriber_email);
		 else if ($data->subscriber_status == 1)
		 {
		  Mage::getModel('sendinblue/sendinblue')->emailSubscribe($data->subscriber_email);
		  Mage::getModel('sendinblue/sendinblue')->sendWsTemplateMail($data->subscriber_email);
		 }
	  }	 	 
	}
	public function disableCache(Varien_Event_Observer $observer)
    {
      $action = $observer->getEvent()->getControllerAction();

      if ($action instanceof Sendinblue_Sendinblue_Adminhtml_MyformController) { // eg. Mage_Catalog_ProductController
        $request = $action->getRequest();
        $cache = Mage::app()->getCacheInstance();
		Mage::getSingleton('core/session')->addSuccess('Done successfully');
        $cache->banUse('full_page'); // Tell Magento to 'ban' the use of FPC for this request
      }
    }

}
