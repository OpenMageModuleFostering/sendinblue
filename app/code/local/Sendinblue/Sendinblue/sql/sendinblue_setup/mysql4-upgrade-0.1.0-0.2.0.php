<?php
/**
* @author Sendinblue plateform <contact@sendinblue.com>
* @copyright  2013-2014 Sendinblue
* URL:  https:www.sendinblue.com
* Do not edit or add to this file if you wish to upgrade Sendinblue Magento plugin to newer
* versions in the future. If you wish to customize Sendinblue magento plugin for your
* needs then we can't provide a technical support.
**/

 //echo 'Testing our upgrade script (upgrade-0.1.0-0.2.0.php) and halting execution to avoid updating the system version number <br />';

$resource = Mage::getSingleton('core/resource');
$writeConnection = $resource->getConnection('core_write');
$sql = "SELECT * FROM `core_config_data` WHERE `path` LIKE 'sendinblue/%'";
echo $datanum = count($writeConnection->fetchAll($sql));
if ($datanum > 0 )
{
$query = "DELETE FROM `core_config_data` WHERE `path` LIKE 'sendinblue/%'";    
$writeConnection->query($query);
}
?>
