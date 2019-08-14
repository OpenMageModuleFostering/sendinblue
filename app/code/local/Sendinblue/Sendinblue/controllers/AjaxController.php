<?php
/**
* @author Sendinblue plateform <contact@sendinblue.com>
* @copyright  2013-2014 Sendinblue
* URL:  https:www.sendinblue.com
* Do not edit or add to this file if you wish to upgrade Sendinblue Magento plugin to newer
* versions in the future. If you wish to customize Sendinblue magento plugin for your
* needs then we can't provide a technical support.
**/

class Sendinblue_Sendinblue_AjaxController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
        Mage::getModel('sendinblue/sendinblue')->createFolderCaseTwo();
    }

    public function campaignAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
             echo  Mage::throwException($this->__('Invalid form data.'));
           		$sendin_switch = new Mage_Core_Model_Config();
				$sendin_switch->saveConfig('sendinblue/sms/campaign', $post['campaignSetting']);
				echo $this->__('Your setting has been successfully saved');
        }
        catch (Exception $e)
        {
           echo $this->__($e->getMessage());
        }
    }
    public function orderAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
           		$sendin_switch = new Mage_Core_Model_Config();
				$sendin_switch->saveConfig('sendinblue/sms/order', $post['orderSetting']);
				echo $this->__('Your setting has been successfully saved');
        }
        catch (Exception $e)
        {
           echo $this->__($e->getMessage());
        }
    }
     public function creditAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
           		$sendin_switch = new Mage_Core_Model_Config();
				$sendin_switch->saveConfig('sendinblue/sms/credit', $post['sms_credit']);
				echo $this->__('Your setting has been successfully saved');
        }
        catch (Exception $e)
        {
           echo $this->__($e->getMessage());
        }
    }
    public function shipingAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
           		$sendin_switch = new Mage_Core_Model_Config();
				$sendin_switch->saveConfig('sendinblue/sms/shiping', $post['shipingSetting']);
				echo $this->__('Your setting has been successfully saved');
        }
        catch (Exception $e)
        {
           echo $this->__($e->getMessage());
        }
    }
    public function codepostAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
           		$sendin_switch = new Mage_Core_Model_Config();
				$sendin_switch->saveConfig('sendinblue/tracking/code', $post['script']);
				echo $this->__('Your setting has been successfully saved');
        }
        catch (Exception $e)
        {
           echo $this->__($e->getMessage());
        }
    }
    public function smtppostAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));
            else
            {
                $sendin_switch = new Mage_Core_Model_Config();
				$get_key   = Mage::getModel('sendinblue/sendinblue')->getApiKey();
                $result    = Mage::getModel('sendinblue/sendinblue')->checkApikey($get_key);
                if (empty($result['error']))
                {
                    $sendin_switch->saveConfig('sendinblue/smtp/status', $post['smtptest']);
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
                        echo $this->__('Your setting has been successfully saved');
                    }else
                    {
                        $sendin_switch->saveConfig('sendinblue/smtp/status', 0);
                        echo $this->__('Your SMTP account is not activated and therefore you can\'t use SendinBlue SMTP. For more informations, please contact our support to: contact@sendinblue.com');
                    }
                }elseif (isset($responce['error']))
                    echo $this->__('You have entered wrong api key');
            }
        }
        catch (Exception $e)
        {
            echo $this->__($e->getMessage());
        }
    }
    public function ajaxcontentAction()
    {
		$post = $this->getRequest()->getPost();
		try {
			if (empty($post))
				Mage::throwException($this->__('Invalid form data.'));
			else
			{
				$locale = Mage::app()->getLocale()->getLocaleCode();
				if ($locale == 'fr_FR')
				{
					$title1 = 'Inscrire le contact';
					$title2 = 'Désinscrire le contact';
					$first = 'Première page';
					$last = 'Dernière page';
					$previous = 'Précédente';
					$next = 'Suivante';
					$yes = 'oui';
					$no = 'non';
				} else
				{
					$title1 = 'Unsubscribe the contact';
					$title2 = 'Subscribe the contact';
					$first = 'First';
					$last = 'Last';
					$previous = 'Previous';
					$next = 'Next';
					$yes = 'yes';
					$no = 'no';
				}
				$page = (int)$post['page'];
				$cur_page = $page;
				$page -= 1;
				$per_page = 20;
				$previous_btn = true;
				$next_btn = true;
				$first_btn = true;
				$last_btn = true;
				$start = $page * $per_page;
				$count = Mage::getModel('sendinblue/sendinblue')->getTotalCount();
				$no_of_paginations = ceil($count / $per_page);
				if ($cur_page >= 7)
				{
				$start_loop = $cur_page - 3;
				if ($no_of_paginations > $cur_page + 3)
					$end_loop = $cur_page + 3;
				else if ($cur_page <= $no_of_paginations && $cur_page > $no_of_paginations - 6)
				{
					$start_loop = $no_of_paginations - 6;
					$end_loop   = $no_of_paginations;
				} else
					$end_loop = $no_of_paginations;
				} else
				{
				$start_loop = 1;
				if ($no_of_paginations > 7)
					$end_loop = 7;
				else
					$end_loop = $no_of_paginations;
				}
				$collection = Mage::getModel('sendinblue/sendinblue')->getNewsletterSubscribe($start, $per_page);
				$sendin_status = Mage::getModel('sendinblue/sendinblue')->checkUserSendinStatus($collection);
				$sendin_result = $sendin_status['result'];
				if (count($collection) != 0)
				 {
					 $i = 1;
					 $msg = '';
					foreach ($collection as $subscriber)
					{
						$email = $subscriber['email'];
						$phone = !empty($subscriber['telephone'])?$subscriber['telephone'] : '';
						$country_id = !empty($subscriber['country_id'])?$subscriber['country_id'] : '';
						if($phone != '')
						{
						$sql = 'SELECT * FROM sendinblue_country_codes WHERE iso_code = "'.$country_id.'" ';
							$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
							$data = $connection->fetchRow($sql);
						    $phone = Mage::getModel('sendinblue/sendinblue')->checkMobileNumber($phone,$data['country_prefix']);	
						}
						if ($subscriber['customer_id'] != 0)
							$client = $yes;
						else
							$client = $no;
						if ($sendin_result[$email] === 1 || $sendin_result[$email] === null)
							$show_status = 0;
						if ($sendin_result[$email] === 0)
							 $show_status = 1;
						if ($subscriber['subscriber_status'] != 3)
							$img_magento = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'adminhtml/default/default/sendinblue/images/enabled.gif" >';
						else
							$img_magento = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'adminhtml/default/default/sendinblue/images/disabled.gif" >';
						if ($show_status)
							$img_sendin = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'adminhtml/default/default/sendinblue/images/enabled.gif" 
							id="ajax_contact_status_'.$i.'" title="'.$title1.'" >';
						else
							$img_sendin = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'adminhtml/default/default/sendinblue/images/disabled.gif" 
							id="ajax_contact_status_'.$i.'" title="'.$title2.'" >';
					  $msg .= '<tr  class="even pointer"><td class="a-left">'.$email.'</td><td class="a-left">'.$client.'</td><td class="a-left">'.$phone.'</td><td class="a-left">'.$img_magento.'</td>
							<td class="a-left last"><a status="'.$show_status.'" email="'.$email.'" class="ajax_contacts_href" href="javascript:void(0)">
                    '.$img_sendin.'</a></td></tr>';
						$i++;
					}
				}
				$msg_paging = '';
				$msg_paging .= '<tr><td colspan="7"><div class="pagination"><ul class="pull-left">';
				if ($first_btn && $cur_page > 1)
					$msg_paging .= '<li p="1" class="active">'.$first.'</li>';
				else if ($first_btn)
					$msg_paging .= '<li p="1" class="inactive">'.$first.'</li>';
				if ($previous_btn && $cur_page > 1)
				{
					$pre = $cur_page - 1;
					$msg_paging .= '<li p="'.$pre.'" class="active">'.$previous.'</li>';
				} else if ($previous_btn)
					$msg_paging .= '<li class="inactive">'.$previous.'</li>';
				for ($i = $start_loop; $i <= $end_loop; $i++)
				{
					if ($cur_page == $i)
						$msg_paging .= '<li p="'.$i.'" style="color:#fff;background-color:#000000;" class="active">'.$i.'</li>';
					else
						$msg_paging .= '<li p="'.$i.'"  class="active">'.$i.'</li>';
				}
				if ($next_btn && $cur_page < $no_of_paginations)
				{
					$nex = $cur_page + 1;
					$msg_paging .= '<li p="'.$nex.'" class="active">'.$next.'</li>';
				} else if ($next_btn)
					$msg_paging .= '<li class="inactive">'.$next.'</li>';
				if ($last_btn && $cur_page < $no_of_paginations)
					 $msg_paging .= '<li p="'.$no_of_paginations.'" class="active">'.$last.'</li>';
				else if ($last_btn)
					$msg_paging .= '<li p="'.$no_of_paginations.'" class="inactive">'.$last.'</li>';
				if ($count != 0)
					echo $msg.$msg_paging.'</td></tr>';
		}
		}catch (Exception $e)
        {
            echo $this->__($e->getMessage());
        }
	}
public function ajaxupdateAction()
	{
		$post = $this->getRequest()->getPost();
		try {
		if (empty($post))
			Mage::throwException($this->__('Invalid form data.'));
			$temp_sub_status = ($post['newsletter'] == 0) ? 1 : 3;
			if (!empty($post['email']) && $post['newsletter'] == 0)
			{
				$responce = Mage::getModel('sendinblue/sendinblue')->emailSubscribe($post['email']);
                                $responce_data = json_decode($responce);
				$sql = 'SELECT * from customer_entity where email = "'.$post['email'].'" ';
				$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
				$custdata = $connection->fetchRow($sql);

                                if ($responce_data->errorMsg == 'User not exists')
                                {
                                 if ($custdata['entity_id'] != '')
                                    {
                                    $collectionAddress = Mage::getModel('customer/address')->getCollection()->addAttributeToSelect('telephone')->addAttributeToSelect('country_id')->addAttributeToFilter('parent_id',(int)$custdata['entity_id']);
                                    $telephone = '';
                                    foreach ($collectionAddress as $customerPhno) {
                                        $telephone = $customerPhno->getData('telephone');
                                        $country_id = $customerPhno->getData('country_id');

                                    }

                                    $customer = Mage::getModel("customer/customer");
                                    $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                                    $customer->loadByEmail($post['email']); //load customer by email id
                                    $customer_name = $customer->getData(); 
                                   
                                   if (!empty($telephone))
                                    {
                                      $sql = 'SELECT * FROM sendinblue_country_codes WHERE iso_code = "'.$country_id.'" ';
							$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
							$data = $connection->fetchRow($sql);
						    $number = Mage::getModel('sendinblue/sendinblue')->checkMobileNumber($telephone,$data['country_prefix']);	  
                                    } 
                                    $client = 1;
                                    $telephone = (isset($number))? $number : '';
                                    $firstname = (isset($customer_name['firstname']))?$customer_name['firstname'] : '';
                                    $lasttname = (isset($customer_name['lastname']))?$customer_name['lastname'] : '';
                                    
                                    $extra = $firstname.'|'.$lasttname.'|'.$client.'|'.$telephone;

                                    $responce = Mage::getModel('sendinblue/sendinblue')->emailAdd($post['email'], $extra);
                                  
                                    }
                                    else
                                    {
                                    $client = 0;
                                    $extra = ''.'|'.''.'|'.$client.'|'.'';		
                                    $responce = Mage::getModel('sendinblue/sendinblue')->emailAdd($post['email'], $extra);   
                                    }
                                }
				$sql = 'SELECT * from newsletter_subscriber where subscriber_email = "'.$post['email'].'" ';
				$custdatanews = $connection->fetchRow($sql);
				if ($custdata['entity_id'] !='' && $custdatanews['subscriber_email'] == '' )
                                {
							
				$connection->query("insert into newsletter_subscriber(store_id, customer_id, subscriber_email, subscriber_status) 
																values('".$custdata['store_id']."','".$custdata['entity_id']."','".$custdata['email']."','1')");
				
				}
				else
				{  
					$costomer_data = Mage::getModel('newsletter/subscriber')->loadByEmail($post['email']);
					$costomer_data->setStatus($temp_sub_status);
					$costomer_data->setIsStatusChanged(true);
					$costomer_data->save();
				}
				
			}
			else{
				$responce = Mage::getModel('sendinblue/sendinblue')->emailDelete($post['email']);
				$costomer_data = Mage::getModel('newsletter/subscriber')->loadByEmail($post['email']);
				$sql = 'SELECT * from customer_entity where email = "'.$post['email'].'" ';
				$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
				$custdata = $connection->fetchRow($sql);
				
				if (!$costomer_data->getStoreId())
				{
					$costomer_data->setSubscriberEmail($custdata['email']);
					$costomer_data->setCustomerId($custdata['entity_id']);
					$costomer_data->setStoreId($custdata['store_id']);
				}
				$costomer_data->setStatus($temp_sub_status);
				$costomer_data->setIsStatusChanged(true);
				$costomer_data->save();
			}
		}
		catch (Exception $e)
		{
			echo $this->__($e->getMessage());
		}
	}
	
	public function ajaxordersmsAction($sender, $message, $number)
	{  
	  $post = $this->getRequest()->getPost();
		try {
		if (empty($post))
			Mage::throwException($this->__('Invalid form data.'));
		    $number = $post['number'];
			$charone = substr($number, 0, 1);
		    $chartwo = substr($number, 0, 2);
		if ($charone == '0' && $chartwo == '00')
                    $number = $number;
		
                if (isset($number))
		{ 		
			$arr = array();
			$arr['to'] = $number;
			$arr['from'] = $post['sender'];
			$arr['text'] = $post['message'];

			$result = Mage::getModel('sendinblue/sendinblue')->sendSmsApi($arr);
			if (isset($result->status) && $result->status == 'OK')
			echo  'OK';
		else
			echo  'KO';	
		}
		
		}
		catch (Exception $e)
		{
			echo $this->__($e->getMessage());
		}        
	}
	
	public function ajaxordershippedAction($sender,$message,$number)
	{
		$post = $this->getRequest()->getPost();
		try {
		if (empty($post))
			Mage::throwException($this->__('Invalid form data.'));
		    $number = $post['number'];
			$charone = substr($number, 0, 1);
		    $chartwo = substr($number, 0, 2);
		
                    if ($charone == '0' && $chartwo == '00')
                        $number = $number;

                    
		if (isset($number))
		{ 		
			$arr = array();
			$arr['to'] = $number;
			$arr['from'] = $post['sender'];
			$arr['text'] = $post['message'];
			
			$result = Mage::getModel('sendinblue/sendinblue')->sendSmsApi($arr);	
			if (isset($result->status) && $result->status == 'OK')
			echo 'OK';
		else
			echo  'KO';exit;
		}
		
		}
		catch (Exception $e)
		{
			echo $this->__($e->getMessage());
		}        
	}
	
	public function ajaxsmscampaignAction($sender,$message,$number)
	{
		$post = $this->getRequest()->getPost();
		try {
		if (empty($post))
			Mage::throwException($this->__('Invalid form data.'));
		    $number = $post['number'];
			$charone = substr($number, 0, 1);
		$chartwo = substr($number, 0, 2);
		if ($charone == '0' && $chartwo == '00')
		    $number = $number;

		if (isset($number))
		{
			$arr = array();
			$arr['to'] = $number;
			$arr['from'] = $post['sender'];
			$arr['text'] = $post['message'];			
			$result = Mage::getModel('sendinblue/sendinblue')->sendSmsApi($arr);			
			$locale = Mage::app()->getLocale()->getLocaleCode();
			if (isset($result->status) && $result->status == 'OK')
			echo  'OK';
		else
			echo  'KO';	
		}
		
		}
		catch (Exception $e)
		{
			echo $this->__($e->getMessage());
		}        
	}
	
}
