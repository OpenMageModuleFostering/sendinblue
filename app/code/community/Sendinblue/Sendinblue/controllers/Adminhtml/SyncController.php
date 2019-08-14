<?php
/**
* @author Sendinblue plateform <contact@sendinblue.com>
* @copyright  2013-2014 Sendinblue
* URL:  https:www.sendinblue.com
* Do not edit or add to this file if you wish to upgrade Sendinblue Magento plugin to newer
* versions in the future. If you wish to customize Sendinblue magento plugin for your
* needs then we can't provide a technical support.
**/

class Sendinblue_Sendinblue_Adminhtml_SyncController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{   
	    $responce = Mage::getModel('sendinblue/sendinblue')->syncData();
		$msg_disp = $this->__('The CRON has been well executed.');
		Mage::getSingleton('adminhtml/session')->addSuccess($msg_disp);
		$this->_redirect("sendinblue/adminhtml_myform/");
	}
}
