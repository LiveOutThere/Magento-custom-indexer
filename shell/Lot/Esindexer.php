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

// TODO: remove hardcoded paths once in production and we aren't using symlinks to deploy
require_once '/home/liveoutt/public_html/dg-dev/shell/abstract.php';

class Mage_Shell_Lot_Esindexer extends Mage_Shell_Abstract {

    public function run()
    {
        if (isset($this->_args['getFilteredProductsCount'])) {
            $esindexer = Mage::getModel('esindexer/products');
            $result = $esindexer->getFilteredProductsCount($this->_args['categoryId'], $this->_args['optionId'], $this->_args['attributeCode']);
            var_dump($result);
        }
        else {
            echo $this->usageHelp();            
        }
    }

    public function usageHelp()
    {
        return <<<USAGE

Usage:  php Esindexer.php -- [tool] [options]
USAGE;
    }
}

require_once '/home/liveoutt/public_html/dg-dev/app/Mage.php';

Mage::app();

$shell = new Mage_Shell_Lot_Esindexer();
$shell->run();