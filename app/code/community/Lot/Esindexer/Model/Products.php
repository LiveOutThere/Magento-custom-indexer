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
class Lot_Esindexer_Model_Products extends Mage_Core_Model_Abstract
{
    /**
     * @var array
     */
    private $_filteredProducts = array();

    /**
     * @var int
     */
    private $_optionId = 0;

    /**
     * Initialize resource
     */
    protected function _construct()
    {
        $this->_init('esindexer/products');
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();
    }

    protected function _afterSave()
    {
        parent::_afterSave();
    }

    /**
     * @param string $filter
     * @param int $optionId
     * @return int
     */
    public function getOptionId($filter = 'manufacturer', $optionId = 0)
    {
        if (empty($optionId) && empty($this->_optionId)){
            $optionId = $this->getAttributeOptionId($filter);
            $this->_optionId = $optionId;
        } else if(!empty($optionId)){
            return $optionId;
        }
        return $this->_optionId;
    }

    //get the id of a option of a attribute  based on category name
    /**
     * @param $attribute
     * @return string
     * @todo:: optimize code | copied from chung's data helper
     */
    public function getAttributeOptionId($attribute)
    {
        $currentCategory=Mage::getModel('catalog/layer')->getCurrentCategory()->getName();
        $optionId   = '';
        $attribute  = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attribute);
        if ($attribute->usesSource()){
            foreach ( $attribute->getSource()->getAllOptions(true) as $option)
            {
                if ($currentCategory==$option['label'])
                {
                    $optionId=$option['value'];
                    break;
                }
            }
        }
        return $optionId;
    }

    /**
     * @param int $category_id
     * @param int $optionId
     * @param string $filter
     * @return int
     */
    public function getFilteredProductsCount($category_id = 0, $optionId = 0, $filter = 'manufacturer'){
        if ($optionId == -1 && $filter == null) {
            $this->initProductsCount();
        }
        else {
            // get all product count for all categories that applies to this $filter
            // and save it to database for quicker generation next time.
            $this->initFilteredProductsCount($filter, $optionId);

            // get option id
            $optionId = $this->getOptionId($filter, $optionId);
        }

        // return product count for selected category
        if(isset($this->_filteredProducts[$optionId][$category_id])){
            return $this->_filteredProducts[$optionId][$category_id];
        }

        return 0;
    }

    /**
     * @param $product
     * @return array
     */
    public function getProductCategories($product){
        $cats = array();
        $categories = $product->getData('category_ids');
        if(!empty($categories)){
            return $categories;
        }

        $categories = $product->getCategoryCollection()->addAttributeToSelect('category_ids');
        foreach($categories as $v){
            $cats[] = $v->getData('entity_id');
        }

        return $cats;
    }

    /**
     * @param string $filter
     * @param int $optionId
     * @param int $id
     *
     * TODO: extend $filter to accept an array of attribute values instead of a single value (then we could save counts into the index for "Arc`teryx Men's Jackets" and not just "Men's Jackets" or "Arc`teryx Jackets")
     */
    public function initFilteredProductsCount($filter = 'manufacturer', $optionId = 0, $id = 0){
        // if $optionId or $id is not supplied, get $optionId
        if (empty($optionId) && empty($id)){
            $optionId = $this->getOptionId($filter);
        }

        // if we have already fetched all products related to this manufacturer
        // or, if it's not update
        // then no need to re-query
        if(isset($this->_filteredProducts[$optionId]) && !$id){
            return;
        }

        // get data collection
        $collection = Mage::getModel('esindexer/products')->getCollection();

        // if $id is supplied, that means it's an update
        if($id){
            $collection->addFieldToFilter('esindexer_id', $id);
        }
        // else it's a new record
        else {
            $collection->addFieldToFilter('attr_id', $optionId);
        }

        // @todo for multi-store support, add new column to db and add this filter
        // $collection->addFieldToFilter('store_id', $storeId)

        // load data from db
        $products = $collection->load();

        // get data from collection
        $products = $products->getData();

        // if we find data from db and it's not an update
        // then no further processing is required
        $isEnabled = Mage::getStoreConfig('lot_esindexer/general/esindexer');

        if(!empty($products) && !$id && $isEnabled ){
            $optionId = $products[0]['attr_id'];
            $data = unserialize($products[0]['count']);
            $this->_filteredProducts = $data;
            return;
        }

        // find product count for $filter $optionId
        $collection = Mage::getResourceModel('catalog/product_collection')
                            ->addAttributeToSelect($filter)
                            ->addAttributeToFilter($filter,$optionId)
                            ->addAttributeToFilter('type_id','configurable')
                            ->addStoreFilter();

        // ---

        $includedCategories = $includedProducts = array();
        if($collection->count()){
            foreach($collection as $v){
                $cats = $this->getProductCategories($v);
                $includedProducts[] = $v->getId();
                foreach($cats as $cat){
                    $includedCategories[] = $cat;
                    if(isset($this->_filteredProducts[$optionId][$cat])){
                        $this->_filteredProducts[$optionId][$cat]++;
                        continue;
                    }
                    $this->_filteredProducts[$optionId][$cat] = 1;
                }
            }
        }

        if(isset($this->_filteredProducts[$optionId])){
            $model = Mage::getModel('esindexer/products')->load($id);
            try {
                $counts = serialize($this->_filteredProducts);
                $data = array(
                    'attr_id' => $optionId,
                    'count' => $counts,
                    'products' => ','.implode(',', array_unique($includedProducts)).',',
                    'categories' => ','.implode(',', array_unique($includedCategories)).',',
                    'flag' => 0,
                    'store_id' => 0,
                );
                $model->addData($data);
                $model->save();
            } catch (Exception $e) {
                Mage::log(__METHOD__ . ': ' . $e->getMessage());
                return;
            }
        } else {
        }
        return;
    }

    public function initProductsCount($id = 0){
        $optionId = -1;
        $collection = Mage::getModel('esindexer/products')->getCollection();

        // if $id is supplied, that means it's an update
        if($id){
            $collection->addFieldToFilter('esindexer_id', $id);
        }
        // else it's a new record
        else {
            $collection->addFieldToFilter('attr_id', $optionId);
        }

        $products = $collection->load();
        $products = $products->getData();

        $isEnabled = Mage::getStoreConfig('lot_esindexer/general/esindexer');

        if(!empty($products) && !$id && $isEnabled ){
            $optionId = $products[0]['attr_id'];
            $data = unserialize($products[0]['count']);
            $this->_filteredProducts = $data;
            return;
        }

        $collection = Mage::getResourceModel('catalog/product_collection')
                            ->addAttributeToFilter('type_id','configurable')
                            ->addStoreFilter();

        $includedCategories = $includedProducts = array();
        if($collection->count()){
            foreach($collection as $v){
                $cats = $this->getProductCategories($v);
                $includedProducts[] = $v->getId();
                foreach($cats as $cat){
                    $includedCategories[] = $cat;
                    if(isset($this->_filteredProducts[$optionId][$cat])){
                        $this->_filteredProducts[$optionId][$cat]++;
                        continue;
                    }
                    $this->_filteredProducts[$optionId][$cat] = 1;
                }
            }
        }

        if(isset($this->_filteredProducts[$optionId])){
            $model = Mage::getModel('esindexer/products')->load($id);
            try {
                $counts = serialize($this->_filteredProducts);
                $data = array(
                    'attr_id' => $optionId,
                    'count' => $counts,
                    'products' => ','.implode(',', array_unique($includedProducts)).',',
                    'categories' => ','.implode(',', array_unique($includedCategories)).',',
                    'flag' => 0,
                    'store_id' => 0,
                );
                $model->addData($data);
                $model->save();
            } catch (Exception $e) {
                Mage::log(__METHOD__ . ': ' . $e->getMessage());
                return;
            }
        } else {
        }
        return;
    }
}
