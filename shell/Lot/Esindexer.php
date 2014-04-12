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
require_once dirname(__FILE__) . '/abstract.php';

class Mage_Shell_Lot_Esindexer extends Mage_Shell_Abstract {

    public function run()
    {
        if (isset($this->_args['test'])) {
            echo __METHOD__;
        }
        else {
            echo $this->usageHelp();            
        }
    }

    public function usageHelp()
    {
        return <<<USAGE

Usage:  php esindexer.php -- [tool] [options]
USAGE;
    }
}

require_once dirname(__FILE__) . '/../../app/Mage.php';

Mage::app();

$shell = new Mage_Shell_Lot_Esindexer();
$shell->run();