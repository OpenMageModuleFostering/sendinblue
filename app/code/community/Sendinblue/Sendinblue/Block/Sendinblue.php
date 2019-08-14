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
		$get_Enable_Status = $sendinblueData->getEnableStatus();	
		$get_Tracking_Status = $sendinblueData->getTrackingStatus();
		$get_order_status = $sendinblueData->getOrderSmsStatus();
		$get_User_lists = $sendinblueData->getUserlists();
		$value = $sendinblueData->TrackingSmtp();
		$attributesName = $sendinblueData->allAttributesName();
		$resp = array();

		$lastOrderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$order = Mage::getModel('sales/order')->loadByIncrementId($lastOrderId);
		$dataDisplay = $order->getBillingAddress()->getData();
		$orderData = $order->getData();
		$custData = $customer->getData();
		$locale = Mage::app()->getLocale()->getLocaleCode();

		if($get_Enable_Status && $get_order_status)
		{
			if (!empty($dataDisplay['telephone']) && !empty($dataDisplay['country_id']))
			{
				$country_code = $sendinblueData->getCountryCode($dataDisplay['country_id']);					
				$dataDisplay['telephone'] = $sendinblueData->checkMobileNumber($dataDisplay['telephone'],$country_code);
			}
			$ref_num = $orderData['increment_id'];
			$orderprice = $orderData['grand_total'];
			$currencycode = $orderData['base_currency_code'];
			$orderdate = $orderData['created_at'];
			if ($locale == 'fr_FR')
			$ord_date = date('d/m/Y', strtotime($orderdate));
			else
			$ord_date = date('m/d/Y', strtotime($orderdate));
			$total_pay = $orderprice.' '.$currencycode;
			$msgbody = $sendinblueData->getSendSmsmOrderMessage();
			$fname = str_replace('{first_name}', $dataDisplay['firstname'], $msgbody);
			$lname = str_replace('{last_name}', $dataDisplay['lastname']."\r\n", $fname);
			$procuct_price = str_replace('{order_price}', $total_pay, $lname);
			$order_date = str_replace('{order_date}', $ord_date."\r\n", $procuct_price);
			$msgbody = str_replace('{order_reference}', $ref_num, $order_date);

			$arr = array();
			$arr['to'] = $mobile;
			$arr['from'] = $sendinblueData->getSendSmsOrderSubject();
			$arr['text'] = $msgbody;
			$responce = $sendinblueData->sendSmsApi($arr);
		}
		$allData = array_merge($dataDisplay, $custData);
		$resp = $sendinblueData->merge_my_array($attributesName, $allData);
		if (!empty($custData['firstname'])|| !empty($custData['firstname']))
			$client = 1;
		else
			$client = 0;
		$resp['CLIENT'] = $client;
		$email = $custData['email'];	// for email address
		$costomer_data = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
        $nlStatus = $costomer_data->getStatus();
        $user_lang =$custData['created_in'];

        if ($nlStatus == 1)
        $sendinblueData->emailAdd($email, $resp, $nlStatus);

		if ($get_Enable_Status == 1 && $get_Tracking_Status == 1 && $nlStatus == 1)
		{
			$value_config = $sendinblueData->getApiConfigValue();
			if ($value_config->date_format == 'dd-mm-yyyy')
			$date = date('d-m-Y', strtotime($orderData['created_at']));
			else
			$date = date('m-d-Y', strtotime($orderData['created_at']));

			$html = '';			
			$html .= '<script type="text/javascript">
						/**Code for NB tracking*/
						function loadScript(url,callback){var script=document.createElement("script");script.type="text/javascript";if(script.readyState){script.onreadystatechange=function(){
						if(script.readyState=="loaded"||script.readyState=="complete"){script.onreadystatechange=null;callback(url)}}}else{
						script.onload=function(){callback(url)}}script.src=url;if(document.body){document.body.appendChild(script)}else{
						document.head.appendChild(script)}}
						var nbJsURL = (("https:" == document.location.protocol) ? "https://my-tracking-orders.googlecode.com/files" : "http://my-tracking-orders.googlecode.com/files");
						var nbBaseURL = "http://tracking.mailin.fr/";
						loadScript(nbJsURL+"/nbv2.js",
						function(){
						/*You can put your custom variables here as shown in example.*/
						try {
						var nbTracker = nb.getTracker(nbBaseURL , "'.$value->result->tracking_data->site_id.'");
						var list = ["'.$get_User_lists.'"];
						var attributes = ["EMAIL","PRENOM","NOM","ORDER_ID","ORDER_DATE","ORDER_PRICE"];
						var values = ["'.$email.'","'.$custData['firstname'].'","'.$custData['lastname'].'","'.$ref_num.'","'.$date.'","'.$orderprice.'"];
						nbTracker.setListData(list);
						nbTracker.setTrackingData(attributes,values);
						nbTracker.trackPageView();
						} catch( err ) {}
						});
						
						</script>';						
			echo $html;
		}                
    }
}
