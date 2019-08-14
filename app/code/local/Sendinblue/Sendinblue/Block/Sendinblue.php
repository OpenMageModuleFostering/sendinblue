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
		
		$get_Enable_Status = Mage::getModel('sendinblue/sendinblue')->getEnableStatus();
		
		$get_Tracking_Status = Mage::getModel('sendinblue/sendinblue')->getTrackingStatus();
		
		$get_order_status = Mage::getModel('sendinblue/sendinblue')->getOrderSmsStatus();
		
		$get_User_lists = Mage::getModel('sendinblue/sendinblue')->getUserlists();
		
		
		$value = Mage::getModel('sendinblue/sendinblue')->TrackingSmtp();
		
		$orders = Mage::getModel('sales/order')->getCollection();		
		
		$order = $orders->getLastItem();
		$order_Data = $order->getPayment()->getData();					
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$orderaddress = Mage::getModel('sales/order')->loadByIncrementId($order->increment_id);
		
		$mobile = $customer->getPrimaryBillingAddress()->getTelephone();
		$email = $customer->getEmail();// for email address
		$firstname = $customer->getFirstname();//  For first name
		$lastname= $customer->getLastname();// For last name
                $costomer_data = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
                $nlStatus = $costomer_data->getStatus(); 
	if ($get_Enable_Status == 1 && $get_Tracking_Status == 1 && $nlStatus == 1)
	{

		$value_config = Mage::getModel('sendinblue/sendinblue')->getApiConfigValue();
                if ($value_config->date_format == 'dd-mm-yyyy')
			$date = date('d-m-Y', strtotime($order->created_at));
			else
			$date = date('m-d-Y', strtotime($order->created_at));

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
					var values = ["'.$email.'","'.$firstname.'","'.$lastname.'","'.$order->increment_id.'","'.$date.'","'.$order_Data[amount_ordered].'"];
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
