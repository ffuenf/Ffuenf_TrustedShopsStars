<?php
/*
 * for reference see
 * http://www.webguys.de/magento/eav-attribute-setup/
 * http://www.webguys.de/magento/adventskalender/turchen-23-installscripte-in-magento/
 */
$installer = $this;
$conn = $installer->getConnection();

$installer->startSetup();

$conn->query($conn->select()->from('core_config_data')->where('path LIKE ?', 'ffuenf_trustedshopsstars/%')->deleteFromSelect('core_config_data'));
$conn->query($conn->select()->from('core_config_data')->where('path = ?', 'advanced/modules_disable_output/Ffuenf_TrustedShopsStars')->deleteFromSelect('core_config_data'));
$conn->query($conn->select()->from('core_resource')->where('code = ?', 'ffuenf_trustedshopsstars_setup')->deleteFromSelect('core_resource'));

$installer->endSetup();
