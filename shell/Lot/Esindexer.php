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

require_once dirname(__FILE__) . '/../abstract.php';

class Mage_Shell_Lot_Esindexer extends Mage_Shell_Abstract {

    private function indexForAttributeCode($attributeCode)
    {
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);
        foreach ( $attribute->getSource()->getAllOptions(true, true) as $option) {
            if ($option['label'] != '') {
                $result = $this->esindexer->getFilteredProductsCount(0, $option['value'], $attributeCode);
            }
        }
    }

    public function run()
    {
        $this->esindexer = Mage::getModel('esindexer/products');
        if (isset($this->_args['create'])) {
            $this->indexForAttributeCode($this->_args['attribute']);
            echo "Successfully created count index for " . $this->_args['attribute'] . " attribute\n";
        }
        else if (isset($this->_args['category-id'])) {
            echo $this->esindexer->getFilteredProductsCount($this->_args['category-id'], $this->_args['option-id'], $this->_args['attribute']) . " products\n";
        }
        else {
            echo $this->usageHelp();            
        }
    }

    public function usageHelp()
    {
        return <<<USAGE

Usage:  php Esindexer.php --create --attribute "attribute_code"                             Create attribute_code index for the first time
                          --category_id 4541 --option-id 3 --attribute "attribute_code"     Get a product count for a combination for a category and option_id

USAGE;
    }
}

require_once dirname(__FILE__) . '/../../app/Mage.php';

Mage::app();

$shell = new Mage_Shell_Lot_Esindexer();
$shell->run();