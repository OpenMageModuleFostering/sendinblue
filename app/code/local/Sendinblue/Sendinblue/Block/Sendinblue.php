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

		$lastOrderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $customer = Mage::getSingleton('customer/session')->getCustomer();
		$order = Mage::getModel('sales/order')->loadByIncrementId($lastOrderId);

		if($get_Enable_Status && $get_order_status)
		{  			
			$locale = Mage::app()->getLocale()->getLocaleCode();					
			$mobile = $order->getBillingAddress()->getTelephone();
			$countryid = $order->getBillingAddress()->getCountryId();
			$tableCountry = Mage::getSingleton('core/resource')->getTableName('sendinblue_country_codes');
			$sql = 'SELECT * FROM '.$tableCountry.' WHERE iso_code = "'.$countryid.'" ';
			$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
			$data = $connection->fetchRow($sql);
						
			$mobile = $sendinblueData->checkMobileNumber($mobile,$data['country_prefix']);
			$email = $order->getBillingAddress()->getEmail();
			$firstname = $order->getBillingAddress()->getFirstname();
			$lastname = $order->getBillingAddress()->getLastname();
			$ref_num = $order->getIncrementId();
			$orderprice = $order->getGrandTotal();
			$currencycode = $order->getBaseCurrencyCode();
			$orderdate = $order->getCreatedAt();
			if ($locale == 'fr_FR')
			$ord_date = date('d/m/Y', strtotime($orderdate));
			else
			$ord_date = date('m/d/Y', strtotime($orderdate));
			$total_pay = $orderprice.' '.$currencycode;
			$msgbody = $sendinblueData->getSendSmsmOrderMessage();
					$fname = str_replace('{first_name}', $firstname, $msgbody);
					$lname = str_replace('{last_name}', $lastname."\r\n", $fname);
					$procuct_price = str_replace('{order_price}', $total_pay, $lname);
					$order_date = str_replace('{order_date}', $ord_date."\r\n", $procuct_price);
					$msgbody = str_replace('{order_reference}', $ref_num, $order_date);
			
			$arr = array();
			$arr['to'] = $mobile;
			$arr['from'] = $sendinblueData->getSendSmsOrderSubject();
			$arr['text'] = $msgbody;
			$responce = $sendinblueData->sendSmsApi($arr);			
		}

		$email = $customer->getEmail();// for email address
		$firstName = $customer->getFirstname();//  For first name
		$lastName= $customer->getLastname();// For last name
        $costomer_data = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
        $nlStatus = $costomer_data->getStatus();
		if ($get_Enable_Status == 1 && $get_Tracking_Status == 1 && $nlStatus == 1)
		{

			$value_config = $sendinblueData->getApiConfigValue();
					if ($value_config->date_format == 'dd-mm-yyyy')
				$date = date('d-m-Y', strtotime($order->getCreatedAt()));
				else
				$date = date('m-d-Y', strtotime($order->getCreatedAt()));

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
						var values = ["'.$email.'","'.$firstName.'","'.$lastName.'","'.$order->getIncrementId().'","'.$date.'","'.$order->getGrandTotal().'"];
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
