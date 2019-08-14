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
		$params = (Mage::app()->getRequest()->getParams())? Mage::app()->getRequest()->getParams() : array();
		if (isset($params['subscriber']) && count($params['subscriber'] > 0))
		{
			$customer_email = array();
			foreach ($params['subscriber'] as $costomer_id)
			{
				$costomer_data = Mage::getModel('newsletter/subscriber')->load($costomer_id)->toArray();
				$customer_email[] = $costomer_data['subscriber_email'];
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
		$params = (Mage::app()->getRequest()->getParams())? Mage::app()->getRequest()->getParams() : array();
		if (isset($params['customer']) && count($params['customer'] > 0))
		{
			$customer_email = array();
			foreach ($params['customer'] as $costomer_id)
			{
				$costomer_data = Mage::getModel('customer/customer')->load($costomer_id)->toArray();
				$customer_email[] = $costomer_data['email'];
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
		$params = (Mage::app()->getRequest()->getParams())? Mage::app()->getRequest()->getParams() : array();
		if (isset($params['customer']) && count($params['customer'] > 0))
		{
			$customer_email = array();
			foreach ($params['customer'] as $costomer_id)
			{
				$costomer_data = Mage::getModel('customer/customer')->load($costomer_id)->toArray();
				$customer_email[] = $costomer_data['email'];
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
		$params = (Mage::app()->getRequest()->getParams())? Mage::app()->getRequest()->getParams() : array();
		$client = 0;
		$extra = ''.'|'.''.'|'.$client.'|'.'';		
		$responce = Mage::getModel('sendinblue/sendinblue')->emailAdd($params['email'], $extra);
		return $this;
	}
	public function updateNewObserver($observer)
	{
		$extra = null;
		$params = (Mage::app()->getRequest()->getParams())? Mage::app()->getRequest()->getParams() : array();

		$cus_session = Mage::getSingleton('customer/session')->getCustomer();
		$customer = ($cus_session->getEmail())? $cus_session : $observer->getCustomer();
		$cus_data = $cus_session->getData();
		 $email = (isset($params['email']))? $params['email'] : $customer->getEmail();
		$cid = $customer->getEntityid();
		$fname = $customer->getFirstname();
		$fname = empty($fname)?'':$fname;
		$lname = $customer->getLastname();
		$lname = empty($lname)?'':$lname;
		
		$collectionAddress = Mage::getModel('customer/address')->getCollection()->addAttributeToSelect('telephone')->addAttributeToSelect('firstname')->addAttributeToSelect('lastname')->addAttributeToFilter('parent_id',$cid);
		$telephone = '';
		foreach ($collectionAddress as $customerPhno) {
			$telephone = $customerPhno->getData('telephone');
			$firstname = $customerPhno->getData('firstname');
			$lastname = $customerPhno->getData('lastname');
			
		}
		$telephone = empty($telephone)?'':$telephone;
		$firstname = empty($firstname)?'':$firstname;
		$lastname = empty($lastname)?'':$lastname;
		
		if ($fname != '' || $lname != '')
			$client = 1;
		else
			$client = 0;

		$is_subscribed = (isset($params['is_subscribed']))? $params['is_subscribed'] : '';
		
		$is_subscribedupdate = (isset($params['is_subscribed']))? 1 : 0;
		 if ( isset($params['is_subscribed']) == '' ) {
			$costomer_data = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
			$nlStatus = $costomer_data->getStatus();
			$is_subscribedupdate = 1;
			if ($nlStatus == '') {
				$is_subscribedupdate = 0;
			}
		 } 
		if ($fname != '' || $lname != '' || $telephone != '' || $email != '')
		{
			$extra = $fname.'|'.$lname.'|'.$client.'|'.$telephone;
                       if (isset($is_subscribed) && $is_subscribed == 1)
                       $responce = Mage::getModel('sendinblue/sendinblue')->emailAdd($email, $extra);
                     
			if (isset($is_subscribedupdate) && $is_subscribedupdate == 1) {
				$responce = Mage::getModel('sendinblue/sendinblue')->emailAdd($email, $extra);
			}
		}
		
		if (isset($is_subscribed) && $is_subscribed != '' && $is_subscribed === 0) {
			Mage::getSingleton('core/session')->addSuccess($is_subscribed);
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
			$order_id = $history_array['items']['0']['order_id'];
			$shippingaddrid = $history_array['items']['0']['shipping_address_id'];
			$_order = Mage::getModel('sales/order')->load($order_id);
			$_shippingAddress = $_order->getShippingAddress();
			$locale = Mage::app()->getLocale()->getLocaleCode();
			$mobile = $_shippingAddress->getTelephone();
			$countryid = $_shippingAddress->getCountryId();
			$sql = 'SELECT * FROM sendinblue_country_codes WHERE iso_code = "'.$countryid.'" ';
			$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
			$data = $connection->fetchRow($sql);
						
			$mobile = Mage::getModel('sendinblue/sendinblue')->checkMobileNumber($mobile,$data['country_prefix']);
			$firstname = $_shippingAddress->getFirstname();
			$lastname = $_shippingAddress->getLastname();
			$ref_num = $_order->getIncrementId();
			$orderprice = $_order->getGrandTotal();
			$courrencycode = $_order->getBaseCurrencyCode();
			$orderdate = $_order->getCreatedAt();
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
	public function ordersucess($observer)
	{
		$get_Enable_Status = Mage::getModel('sendinblue/sendinblue')->getEnableStatus();		
		$get_order_status = Mage::getModel('sendinblue/sendinblue')->getOrderSmsStatus();		
		$get_User_lists = Mage::getModel('sendinblue/sendinblue')->getUserlists();
		$get_Tracking_Status = Mage::getModel('sendinblue/sendinblue')->getTrackingStatus();		
		$value = Mage::getModel('sendinblue/sendinblue')->TrackingSmtp();
		
		$orders = Mage::getModel('sales/order')->getCollection();		
		$order = $orders->getLastItem();

		$order_Data = $order->getPayment()->getData();
	
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$orderaddress = Mage::getModel('sales/order')->loadByIncrementId($order->increment_id);
		
		//for order sms send 	
		if($get_Enable_Status && $get_order_status)
		{  			
			$locale = Mage::app()->getLocale()->getLocaleCode();					
			$mobile = $orderaddress->getBillingAddress()->getTelephone();
			$countryid = $orderaddress->getBillingAddress()->getCountryId();
			$sql = 'SELECT * FROM sendinblue_country_codes WHERE iso_code = "'.$countryid.'" ';
			$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
			$data = $connection->fetchRow($sql);
						
			$mobile = Mage::getModel('sendinblue/sendinblue')->checkMobileNumber($mobile,$data['country_prefix']);
			$email = $orderaddress->getBillingAddress()->getEmail();
			$firstname = $orderaddress->getBillingAddress()->getFirstname();
			$lastname = $orderaddress->getBillingAddress()->getLastname();
			$ref_num = $order->getIncrementId();
			$orderprice = $order->getGrandTotal();
			$currencycode = $order->getBaseCurrencyCode();
			$orderdate = $order->getCreatedAt();
			if ($locale == 'fr_FR')
			$ord_date = date('d/m/Y', strtotime($orderdate));
			else
			$ord_date = date('m/d/Y', strtotime($orderdate));
			$total_pay = $orderprice.' '.$currencycode;
			$msgbody = Mage::getModel('sendinblue/sendinblue')->getSendSmsmOrderMessage();
					$fname = str_replace('{first_name}', $firstname, $msgbody);
					$lname = str_replace('{last_name}', $lastname."\r\n", $fname);
					$procuct_price = str_replace('{order_price}', $total_pay, $lname);
					$order_date = str_replace('{order_date}', $ord_date."\r\n", $procuct_price);
					$msgbody = str_replace('{order_reference}', $ref_num, $order_date);
			
			$arr = array();
			$arr['to'] = $mobile;
			$arr['from'] = Mage::getModel('sendinblue/sendinblue')->getSendSmsOrderSubject();
			$arr['text'] = $msgbody;
			$responce = Mage::getModel('sendinblue/sendinblue')->sendSmsApi($arr);			
		}
	
	}
	public function subscribedToNewsletter($observer)
    {
	  $data = $observer->subscriber;	 
	 if($data->subscriber_status == 3)
	  Mage::getModel('sendinblue/sendinblue')->emailDelete($data->subscriber_email);
	 else if ($data->subscriber_status == 1)
	  Mage::getModel('sendinblue/sendinblue')->emailSubscribe($data->subscriber_email);
	  		 
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
