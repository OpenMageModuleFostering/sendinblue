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
	protected static $fields = array();
	public function adminSubcriberDelete($observer)
	{
		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		if (isset($params['subscriber']) && count($params['subscriber'] > 0))
		{
			$customer_email = array();
			$nbSubscriber = Mage::getModel('newsletter/subscriber');
			foreach ($params['subscriber'] as $costomer_id)
			{
				$costomer_data = $nbSubscriber->load($costomer_id)->toArray();
				$customer_email[] = empty($costomer_data['subscriber_email'])?array():$costomer_data['subscriber_email'];
			}
			$customer_emails = implode('|', $customer_email);
			$responce = Mage::getModel('sendinblue/sendinblue')->emailDelete($customer_emails);
		}
		if ($responce->result)
			Mage::getModel('core/session')->addSuccess('Total of '.$responce->result->unsubEmailsCounts.' record(s) were Unsubscribed');
		return $this;
	}

	public function adminCustomerDelete($observer)
	{
		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		if (isset($params['customer']) && count($params['customer'] > 0))
		{
			$customer_email = array();
			$customerObj = Mage::getModel('customer/customer');
			foreach ($params['customer'] as $costomer_id)
			{
				$costomer_data = $customerObj->load($costomer_id)->toArray();
				$customer_email[] = empty($costomer_data['email'])?array():$costomer_data['email'];
			}
			$customer_emails = implode('|', $customer_email);
			$responce = Mage::getModel('sendinblue/sendinblue')->emailDelete($customer_emails);
		}
		if ($responce->result)
			Mage::getModel('core/session')->addSuccess('Total of '.$responce->result->unsubEmailsCounts.' record(s) were Unsubscribed');
		return $this;
	}
	public function adminCustomerSubscribe($observer)
	{
		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		if (isset($params['customer']) && count($params['customer'] > 0))
		{
			$customer_email = array();
			$customerObj = Mage::getModel('customer/customer');
			foreach ($params['customer'] as $costomer_id)
			{
				$costomer_data = $customerObj->load($costomer_id)->toArray();
				$customer_email[] = empty($costomer_data['email'])?array():$costomer_data['email'];
			}
			$customer_emails = implode('|', $customer_email);
			$responce = Mage::getModel('sendinblue/sendinblue')->addEmailList($customer_emails);
		}
		if ($responce->result)
			Mage::getModel('core/session')->addSuccess('Total of '.$responce->result->infoUpdatedCount.' record(s) were subscribed');
		return $this;
	}
	public function subscribeObserver($observer)
	{
		$extra = array();
		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		$extra = Mage::getModel('newsletter/subscriber')->loadByEmail($params['email'])->getData();
		$sendinModule = Mage::getModel('sendinblue/sendinblue');
		$attributesName = $sendinModule->allAttributesName();

		if ($params['email'] != '')
		$newsletter_status = 0;

		$client = 0;
		$resp = $sendinModule->merge_my_array($attributesName, $extra);
		$resp['CLIENT'] = $client;
		$responce = $sendinModule->emailAdd($params['email'], $resp, $newsletter_status);
		return $this;
	}
	public function updateNewObserver($observer)
	{
		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		$cus_session = Mage::getSingleton('customer/session')->getCustomer();
		$sendinModule = Mage::getModel('sendinblue/sendinblue');
		$attributesName = $sendinModule->allAttributesName();

		$customerSessionEmail = $cus_session->getEmail();
		if (empty($customerSessionEmail)) {
			$customer = $observer->getCustomer();
			$customerData = $customer->getData();
		}
		else
		$customerData = $cus_session->getData();

		$user_lang = isset($customerData['created_in'])? $customerData['created_in'] :'';
		$email = $customerData['email'];
		$cid = isset($customerData['entity_id'])?$customerData['entity_id']:'';		
		$collectionAddress = Mage::getModel('customer/address')->getCollection()->addAttributeToSelect('telephone')->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->addAttributeToSelect('company')->addAttributeToSelect('street')->addAttributeToSelect('postcode')->addAttributeToSelect('region')->addAttributeToSelect('country_id')->addAttributeToSelect('city')->addAttributeToFilter('parent_id',$cid);

		$telephone = '';
		$customer_addr = array();
		$customer_addr_data = array();
		foreach ($collectionAddress as $customerPhno) {
			$customer_addr = $customerPhno->getData();
			if (!empty($customer_addr['telephone']))
			{
				if(!empty($customer_addr['country_id']))
				{
					$country_code = $sendinModule->getCountryCode($customer_addr['country_id']);
					$customer_addr['telephone'] = $sendinModule->checkMobileNumber($customer_addr['telephone'], $country_code);
				}
			}
		}
		$customer_addr_data = array_merge($customer_addr, $customerData);
		if (!empty($customerData['firstname']) && !empty($customerData['lastname']))
			$client = 1;
		else
			$client = 0;

		$is_subscribed = !empty($customer_addr_data['is_subscribed'])?$customer_addr_data['is_subscribed']:$params['is_subscribed'];

		if (!empty($customerData['firstname']) || !empty($customer_addr['telephone']) || !empty($email))
		{
			$costomer_data = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
			$nlStatus = $costomer_data->getStatus();
			$resp = $sendinModule->merge_my_array($attributesName, $customer_addr_data);
			$resp['CLIENT'] = $client;
			if (isset($is_subscribed) && $is_subscribed == 1 && empty($nlStatus))
			{
				$responce = $sendinModule->emailAdd($email, $resp, $is_subscribed);
				$sendinModule->sendWsTemplateMail($email);
			}
			elseif (!empty($nlStatus))
			{
				$responce = $sendinModule->emailAdd($email, $resp);
			}	
		}
		
		if (isset($is_subscribed) && !empty($is_subscribed) && $is_subscribed === 0) {

			$responce = $sendinModule->emailDelete($email);
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
				$codeResource = Mage::getSingleton('core/resource');
				$tableCountry = $codeResource->getTableName('sendinblue_country_codes');
				$sql = 'SELECT `country_prefix` FROM '.$tableCountry.' WHERE iso_code = "'.$countryid.'" ';
				$connection = $codeResource->getConnection('core_read');
				$data = $connection->fetchRow($sql);
				$mobile = '';
				$country_prefix = $data['country_prefix'];
				$sendinblueModule = Mage::getModel('sendinblue/sendinblue');
				if(isset($country_prefix) && !empty($country_prefix))
				$mobile = $sendinblueModule->checkMobileNumber($mobile_sms,$country_prefix);
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
				$msgbody = $sendinblueModule->getSendSmsShipingMessage();
				$fname = str_replace('{first_name}', $firstname, $msgbody);
				$lname = str_replace('{last_name}', $lastname."\r\n", $fname);
				$procuct_price = str_replace('{order_price}', $total_pay, $lname);
				$order_date = str_replace('{order_date}', $ord_date."\r\n", $procuct_price);
				$msgbody = str_replace('{order_reference}', $ref_num, $order_date);

				$arr = array();
				$arr['to'] = $mobile;
				$arr['from'] = $sendinblueModule->getSendSmsShipingSubject();
				$arr['text'] = $msgbody;
				$sendinblueModule->sendSmsApi($arr);
			}
		}
	}

	public function subscribedToNewsletter($observer)
    {
		$data = $observer->subscriber;
		$params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;

		if (empty($params['firstname']) && empty($params['lastname']))
		{
			$sibObj = Mage::getModel('sendinblue/sendinblue');
			$subscriber_email = $data->subscriber_email;

			if($data->subscriber_status == 3)
				$sibObj->emailDelete($subscriber_email);
			elseif ($data->subscriber_status == 1 && !empty($subscriber_email))
			{
				$sibObj->emailSubscribe($data->subscriber_email);
				if( !isset($params['newsletter'])) {	
					$sibObj->sendWsTemplateMail($data->subscriber_email);
				}
			}
		}
	}

}
