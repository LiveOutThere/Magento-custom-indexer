<?php
/**
 * Lot_Esindexer extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Lot
 * @package    Lot_Esindexer
 * @author     Drew Gillson (forked from Damodar Bashyal)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
$this->startSetup()->run("
CREATE TABLE {$this->getTable('esindexer')} (
   `esindexer_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
   `attr_id` int(10) DEFAULT NULL,
   `count` text,
   `categories` text,
   `products` text,
   `store_id` int(11) NOT NULL DEFAULT '1',
   `flag` int(1) NOT NULL DEFAULT '0',
   `update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (`tindexer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
")->endSetup();