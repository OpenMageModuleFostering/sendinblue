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
        $params = Mage::app()->getRequest()->getParams();
		$params = empty($params)?array():$params;
		if (isset($params['sendin_apikey']) && $params['sendin_apikey'] != '')
			$this->CreateFolderCaseTwo();
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
                                $sendin_switch->saveConfig('sendinblue/improt/history', $post['script']);
				echo $this->__('Your setting has been successfully saved');
        }
        catch (Exception $e)
        {
           echo $this->__($e->getMessage());
        }
    }
	public function emptySubsUserToSendinblueAction()
	{
		$post = $this->getRequest()->getPost();
        try {
            if (empty($post))
               Mage::throwException($this->__('Invalid form data.'));
			if ($post['proc_success'] != '')
            {
				$handle = fopen(Mage::getBaseDir('media').'/sendinblue_csv/ImportSubUsersToSendinblue.csv', 'w+');
				$key_value = array();
				$key_value[] = '';			
				fputcsv($handle, $key_value);
				fclose($handle);
			}
			}
		catch (Exception $e)
        {
           echo $this->__($e->getMessage());
        }
	}
	public function emptyImportOldOrderAction()
    {
		$post = $this->getRequest()->getPost();
        try {
            if (empty($post))
               Mage::throwException($this->__('Invalid form data.'));
			if ($post['proc_success'] != '')
            {
				$handle = fopen(Mage::getBaseDir('media').'/sendinblue_csv/ImportOldOrdersToSendinblue.csv', 'w+');
				$key_value = array();
				$key_value[] = '';			
				fputcsv($handle, $key_value);
				fclose($handle);
			}
			}
		catch (Exception $e)
        {
           echo $this->__($e->getMessage());
        }
	}
    public function orderhistoryAction()
    {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post))
                Mage::throwException($this->__('Invalid form data.'));

            if ($post['history_status'] == 1)
            {
            $value = Mage::getModel('sendinblue/sendinblue')->TrackingSmtp();
			$date_value = Mage::getModel('sendinblue/sendinblue')->getApiConfigValue();
			if (!is_dir(Mage::getBaseDir('media').'/sendinblue_csv'))
				mkdir(Mage::getBaseDir('media').'/sendinblue_csv', 0777, true);

			$handle = fopen(Mage::getBaseDir('media').'/sendinblue_csv/ImportOldOrdersToSendinblue.csv', 'w+');

			fwrite($handle, 'EMAIL,ORDER_ID,ORDER_PRICE,ORDER_DATE'.PHP_EOL);

			
			$collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('email');
			foreach ($collection as $customer)
			{
            $cid = $customer->getData('entity_id');
			$email = $customer->getData('email');
			$total_orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id', $cid); 
			$orderCnt = $total_orders->count();
			if ($orderCnt > 0)
			{
				$data = array();
				$data['key'] = Mage::getModel('sendinblue/sendinblue')->getApiKey();
				$data['webaction'] = 'USERS-STATUS';
				$data['email'] = $email;
				$curl_responce = Mage::getModel('sendinblue/sendinblue')->curlRequest($data);
				$user_status = json_decode($curl_responce);
			}
			if (isset($user_status->result) != '')
			{
			foreach($total_orders as $order_data)
			{
				if ($date_value->date_format == 'dd-mm-yyyy')
					$date = date('d-m-Y', strtotime($order_data['created_at']));
				else
				$date = date('m-d-Y', strtotime($order_data['created_at']));
				$history_data= array();
				$history_data[] = array($order_data['customer_email'],$order_data['increment_id'],$order_data['grand_total'],$date);
				foreach ($history_data as $line)
				fputcsv($handle, $line);
			}
			}
			}

		fclose($handle);

		$get_User_lists = Mage::getModel('sendinblue/sendinblue')->getUserlists();
		$list = str_replace('|', ',', $get_User_lists);
		if (preg_match('/^[0-9,]+$/', $list))
			$list = $list;
		else
			$list = '';

		$import_data = array();
		$import_data['webaction'] = 'IMPORTUSERS';
		$import_data['key'] = Mage::getModel('sendinblue/sendinblue')->getApiKey();
		$import_data['url'] = Mage::getBaseUrl('media').'sendinblue_csv/ImportOldOrdersToSendinblue.csv';
		$import_data['listids'] = $list;
		$import_data['notify_url'] = Mage::getBaseUrl().'sendinblue/ajax/emptyImportOldOrder';
		/**
		* List id should be optional
		*/
		Mage::getModel('sendinblue/sendinblue')->curlRequestAsyc($import_data);


        $sendin_switch = new Mage_Core_Model_Config();
		$sendin_switch->saveConfig('sendinblue/improt/history', 0);
		echo $this->__('Order history has been import successfully');
            }                    
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
                                        $title3 = 'Inscrire le sms';
					$title4 = 'Désinscrire le sms';
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
                                        $title3 = 'Unsubscribe the sms';
					$title4 = 'Subscribe the sms';
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
				$sendin_result = isset($sendin_status['result'])?$sendin_status['result']:'';
				if (count($collection) > 0)
				 {
					 $i = 1;
					 $msg = '';
					foreach ($collection as $subscriber)
					{
						$email = isset($subscriber['email'])?$subscriber['email']:'';
						$phone = isset($subscriber['telephone'])?$subscriber['telephone'] : '';
						$country_id = isset($subscriber['country_id'])?$subscriber['country_id'] : '';
						if($phone != '')
						{
						$tableCountry = Mage::getSingleton('core/resource')->getTableName('sendinblue_country_codes');
						$sql = 'SELECT * FROM '.$tableCountry.' WHERE iso_code = "'.$country_id.'" ';
							$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
							$data = $connection->fetchRow($sql);
						    $phone = Mage::getModel('sendinblue/sendinblue')->checkMobileNumber($phone,$data['country_prefix']);	
						}
						if (isset($subscriber['customer_id']) != 0)
							$client = $yes;
						else
							$client = $no;

							$show_status = '';
							$sms_status = '';
						if(isset($sendin_result[$email])) {
							$email_bl_value = isset($sendin_result[$email]['email_bl'])?$sendin_result[$email]['email_bl']:'';
							if ($email_bl_value === 1 || $sendin_result[$email] == null)
								$show_status = 0;
							if ($email_bl_value === 0)
								 $show_status = 1;

								$sms_bl = isset($sendin_result[$email]['sms_bl'])?$sendin_result[$email]['sms_bl']:'';
								$sms_exist = isset($sendin_result[$email]['sms_exist'])?$sendin_result[$email]['sms_exist']:'';
								$subs_telephone = isset($subscriber['telephone'])?$subscriber['telephone']:'';
							if ($sms_bl === 1 && $sms_exist > 0)
								$sms_status = 0;
							elseif ($sms_bl === 0 && $sms_exist > 0)
								 $sms_status = 1;
							elseif ($sms_exist <= 0 && empty($subs_telephone))
								 $sms_status = 2;
							else if ($sms_exist <= 0 && !empty($subs_telephone))
								$sms_status = 3;
						}
						if ($subscriber['subscriber_status'] != 3)
							$img_magento = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'adminhtml/default/default/sendinblue/images/enabled.gif" >';
						else
							$img_magento = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'adminhtml/default/default/sendinblue/images/disabled.gif" >';
                        
                        $sms_status = $sms_status >= 0?$sms_status:'';

                        if ($sms_status === 1)
							$img_sms = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'adminhtml/default/default/sendinblue/images/enabled.gif" 
							id="ajax_contact_status_'.$i.'" title="'.$title3.'" >';
						else if ($sms_status === 0)
							$img_sms = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'adminhtml/default/default/sendinblue/images/disabled.gif" 
							id="ajax_contact_status_'.$i.'" title="'.$title4.'" >';
                        else if ($sms_status === 2 || $sms_status === '')
                                $img_sms = '';
                        else if ($sms_status === 3)
                                $img_sms = 'Not synchronized';
                            $show_status = !empty($show_status)?$show_status:'0';
						if ($show_status == 1)
							$img_sendin = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'adminhtml/default/default/sendinblue/images/enabled.gif" 
							id="ajax_contact_status_'.$i.'" title="'.$title1.'" >';
						else
							$img_sendin = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'adminhtml/default/default/sendinblue/images/disabled.gif" 
							id="ajax_contact_status_'.$i.'" title="'.$title2.'" >';
					  $msg .= '<tr  class="even pointer"><td class="a-left">'.$email.'</td><td class="a-left">'.$client.'</td><td class="a-left">'.$phone.'</td><td class="a-left">'.$img_magento.'</td>
							<td class="a-left"><a status="'.$show_status.'" email="'.$email.'" class="ajax_contacts_href" href="javascript:void(0)">
                    '.$img_sendin.'</a></td><td class="a-left last"><a status="'.$sms_status.'" email="'.$email.'" class="ajax_sms_subs_href" href="javascript:void(0)">
                    '.$img_sms.'</a></td></tr>';
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
public function ajaxsmssubscribeAction()
{
	$post = $this->getRequest()->getPost();
	try {
	if (empty($post))
		Mage::throwException($this->__('Invalid form data.'));
			$email = $post['email'];
			//$sms = $post['sms'];
			$data = array();
	$data['key'] = Mage::getModel('sendinblue/sendinblue')->getApiKey();
	$data['webaction'] = 'USERUNSUBSCRIBEDSMS';
	$data['email'] = $email;
	Mage::getModel('sendinblue/sendinblue')->curlRequest($data);
			
				}
	catch (Exception $e)
	{
		echo $this->__($e->getMessage());
	}
}       
	public function ajaxupdateAction()
	{
		$post = $this->getRequest()->getPost();
		$tableCustomer = Mage::getSingleton('core/resource')->getTableName('customer/entity');
		$tableNewsletter = Mage::getSingleton('core/resource')->getTableName('newsletter/subscriber');
		try {
		if (empty($post))
			Mage::throwException($this->__('Invalid form data.'));
			$post_email = isset($post['email'])?$post['email']:'';
			$post_newsletter = isset($post['newsletter'])?$post['newsletter']:'';
			$temp_sub_status = ($post_newsletter == 0) ? 1 : 3;
			if (!empty($post_email) && $post_newsletter == 0)
			{
				$locale = Mage::app()->getLocale()->getLocaleCode();
				$responce = Mage::getModel('sendinblue/sendinblue')->emailSubscribe($post_email);
                $responce_data = json_decode($responce);

				$sql = 'SELECT * from '.$tableCustomer.' where email = "'.$post_email.'" ';
				$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
				$custdata = $connection->fetchRow($sql);

              //  if (isset($responce_data->errorMsg) == 'User not exists')
               // {
                 if (isset($custdata['entity_id']) != '')
                    {
                    $collectionAddress = Mage::getModel('customer/address')->getCollection()->addAttributeToSelect('telephone')->addAttributeToSelect('country_id')->addAttributeToFilter('parent_id',(int)$custdata['entity_id']);
                    $telephone = '';
                    foreach ($collectionAddress as $customerPhno) {
						$phn_sms = $customerPhno->getData('telephone');
						$country_id_value = $customerPhno->getData('country_id');
                        $telephone = !empty($phn_sms) ? $phn_sms : '';
                        $country_id = !empty($country_id_value) ? $country_id_value : '';

                    }

                    $customer = Mage::getModel("customer/customer");
                    $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
                    $customer->loadByEmail($post_email); //load customer by email id
                    $customer_name = $customer->getData();
                    $user_lang = isset($customer_name['created_in'])?$customer_name['created_in'] : '';
                   
                   if (!empty($telephone))
                    {
                      $tableCountry = Mage::getSingleton('core/resource')->getTableName('sendinblue_country_codes');
					  $sql = 'SELECT * FROM '.$tableCountry.' WHERE iso_code = "'.$country_id.'" ';
					  $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
					  $data = $connection->fetchRow($sql);
					  $number = Mage::getModel('sendinblue/sendinblue')->checkMobileNumber($telephone,$data['country_prefix']);	  
                    } 
                    $client = 1;
                    $telephone = (isset($number))? $number : '';
                    $firstname = (isset($customer_name['firstname']))?$customer_name['firstname'] : '';
                    $lasttname = (isset($customer_name['lastname']))?$customer_name['lastname'] : '';
                    
                    $extra = $firstname.'|'.$lasttname.'|'.$user_lang.'|'.$client.'|'.$telephone;

                    $responce = Mage::getModel('sendinblue/sendinblue')->emailAdd($post_email, $extra, $post_newsletter);

                    }
                    else
                    {
                    $client = 0;
                    $extra = ''.'|'.''.'|'.''.'|'.$client.'|'.'';		
                    $responce = Mage::getModel('sendinblue/sendinblue')->emailAdd($post_email, $extra, $post_newsletter);   
                    }
             //   }
				$sql = 'SELECT * from '.$tableNewsletter.' where subscriber_email = "'.$post_email.'" ';
				$custdatanews = $connection->fetchRow($sql);
				if ($custdata['entity_id'] !='' && $custdatanews['subscriber_email'] == '' )
				{
							
				$connection->query("insert into ".$tableNewsletter."(store_id, customer_id, subscriber_email, subscriber_status) 
									values('".$custdata['store_id']."','".$custdata['entity_id']."','".$custdata['email']."','1')");
				
				}
				else
				{  
					$costomer_data = Mage::getModel('newsletter/subscriber')->loadByEmail($post_email);
					$costomer_data->setStatus($temp_sub_status);
					$costomer_data->setIsStatusChanged(true);
					$costomer_data->save();
				}

			}
			else{
				$responce = Mage::getModel('sendinblue/sendinblue')->emailDelete($post_email);
				$costomer_data = Mage::getModel('newsletter/subscriber')->loadByEmail($post_email);
				$sql = 'SELECT * from '.$tableCustomer.' where email = "'.$post_email.'" ';
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
	
	public function ajaxordersmsAction($sender='', $message='', $number='')
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
		$adminUserModel = Mage::getModel('admin/user');
                $userCollection = $adminUserModel->getCollection()->load(); 
                $admin_data = $userCollection->getData();
                $firstname = isset($admin_data[0]['firstname'])?$admin_data[0]['firstname']:'';
                $lastname = isset($admin_data[0]['lastname'])?$admin_data[0]['lastname']:'';
                $characters = '1234567890';
		$ref_num = '';
		for ($i = 0; $i < 9; $i++)
			$ref_num .= $characters[rand(0, strlen($characters) - 1)];
                
                $locale = Mage::app()->getLocale()->getLocaleCode();
               if ($locale == 'fr_FR')
			$ord_date = date('d/m/Y');
			else
			$ord_date = date('m/d/Y');
                $orderprice = rand(10, 1000);
                $total_pay = $orderprice.'.00'.' '.Mage::app()->getStore()-> getCurrentCurrencyCode();
                $msgbody = $post['message'];
                $fname = str_replace('{first_name}', $firstname, $msgbody);
                $lname = str_replace('{last_name}', $lastname."\r\n", $fname);
                $procuct_price = str_replace('{order_price}', $total_pay, $lname);
                $order_date = str_replace('{order_date}', $ord_date."\r\n", $procuct_price);
                $msgbody = str_replace('{order_reference}', $ref_num, $order_date);    
                $arr = array();
                $arr['to'] = $number;
                $arr['from'] = isset($post['sender'])?$post['sender']:'';
                $arr['text'] = $msgbody;

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
	
	public function ajaxordershippedAction($sender='', $message='', $number='')
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
                    $adminUserModel = Mage::getModel('admin/user');
                    $userCollection = $adminUserModel->getCollection()->load(); 
                    $admin_data = $userCollection->getData();
                    $firstname = isset($admin_data[0]['firstname'])?$admin_data[0]['firstname']:'';
                    $lastname = isset($admin_data[0]['lastname'])?$admin_data[0]['lastname']:'';
                    $characters = '1234567890';
                    $ref_num = '';
                    for ($i = 0; $i < 9; $i++)
                            $ref_num .= $characters[rand(0, strlen($characters) - 1)];

                    $locale = Mage::app()->getLocale()->getLocaleCode();
                    if ($locale == 'fr_FR')
                             $ord_date = date('d/m/Y');
                             else
                             $ord_date = date('m/d/Y');
                    $orderprice = rand(10, 1000);
                    $total_pay = $orderprice.'.00'.' '.Mage::app()->getStore()-> getCurrentCurrencyCode();
                    $msgbody = $post['message'];
                    $fname = str_replace('{first_name}', $firstname, $msgbody);
                    $lname = str_replace('{last_name}', $lastname."\r\n", $fname);
                    $procuct_price = str_replace('{order_price}', $total_pay, $lname);
                    $order_date = str_replace('{order_date}', $ord_date."\r\n", $procuct_price);
                    $msgbody = str_replace('{order_reference}', $ref_num, $order_date);
                    $arr = array();
                    $arr['to'] = $number;
                    $arr['from'] = !empty($post['sender'])?$post['sender']:'';
                    $arr['text'] = $msgbody;

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
	
	public function ajaxsmscampaignAction($sender='', $message='', $number='')
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
                    $adminUserModel = Mage::getModel('admin/user');
                    $userCollection = $adminUserModel->getCollection()->load(); 
                    $admin_data = $userCollection->getData();

                    $firstname = isset($admin_data[0]['firstname'])?$admin_data[0]['firstname']:'';
                    $lastname = isset($admin_data[0]['lastname'])?$admin_data[0]['lastname']:'';
                    $msgbody = $post['message'];
                    $fname = str_replace('{first_name}', $firstname, $msgbody);
                    $msgbody = str_replace('{last_name}', $lastname."\r\n", $fname);
                    $arr = array();
                    $arr['to'] = $number;
                    $arr['from'] = !empty($post['sender'])?$post['sender']:'';
                    $arr['text'] = $msgbody;			
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
	
}
