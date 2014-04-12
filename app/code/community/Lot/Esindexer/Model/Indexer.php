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
class Lot_Esindexer_Model_Indexer extends Mage_Index_Model_Indexer_Abstract
{
    protected $_matchedEntities = array(
            'esindexer_entity' => array(
                Mage_Index_Model_Event::TYPE_SAVE
            )
        );

    // var to protect multiple runs
    protected $_registered = false;
    protected $_processed = false;
    protected $_categoryId = 0;
    protected $_productIds = array();

    /**
     * not sure why this is required.
     * _registerEvent is only called if this function is included.
     *
     * @param Mage_Index_Model_Event $event
     * @return bool
     */
    public function matchEvent(Mage_Index_Model_Event $event)
    {
        return Mage::getModel('catalog/category_indexer_product')->matchEvent($event);
    }


    public function getName(){
        return Mage::helper('esindexer')->__('ElasticSearch');
    }

    public function getDescription(){
        return Mage::helper('esindexer')->__('Index product attributes and category counts');
    }

    protected function _registerEvent(Mage_Index_Model_Event $event){
        // if event was already registered once, then no need to register again.
        if($this->_registered) return $this;

        $entity = $event->getEntity();
        switch ($entity) {
            case Mage_Catalog_Model_Product::ENTITY:
               $this->_registerProductEvent($event);
                break;

            case Mage_Catalog_Model_Category::ENTITY:
                $this->_registerCategoryEvent($event);
                break;

            case Mage_Catalog_Model_Convert_Adapter_Product::ENTITY:
                $event->addNewData('esindexer_indexer_reindex_all', true);
                break;

            case Mage_Core_Model_Store::ENTITY:
            case Mage_Core_Model_Store_Group::ENTITY:
                $process = $event->getProcess();
                $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
                break;
        }
        $this->_registered = true;
        return $this;
    }

    /**
     * Register event data during product save process
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerProductEvent(Mage_Index_Model_Event $event)
    {
        $eventType = $event->getType();
        
        if ($eventType == Mage_Index_Model_Event::TYPE_SAVE || $eventType == Mage_Index_Model_Event::TYPE_MASS_ACTION) {
            $process = $event->getProcess();
            $this->_productIds = $event->getDataObject()->getData('product_ids');
            $this->flagIndexRequired($this->_productIds, 'products');

            $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }
    }

    /**
     * Register event data during category save process
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerCategoryEvent(Mage_Index_Model_Event $event)
    {
        $category = $event->getDataObject();
        /**
         * Check if product categories data was changed
         * Check if category has another affected category ids (category move result)
         */
        
        if ($category->getIsChangedProductList() || $category->getAffectedCategoryIds()) {
            $process = $event->getProcess();
            $this->_categoryId = $event->getDataObject()->getData('entity_id');
            $this->flagIndexRequired($this->_categoryId, 'categories');

            $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }
    }
    protected function _processEvent(Mage_Index_Model_Event $event){
        // process index event
        if(!$this->_processed){
            $this->_processed = true;
        }
    }

    public function flagIndexRequired($ids=array(), $type='products'){
        $ids = (array)$ids;
        $collection = Mage::getModel('esindexer/products')->getCollection();
        $filter = array();
        if (count($ids)) {
            foreach($ids as $id){
                $filter[] = array('like' => "%,{$id},%");
            }
            $collection->addFieldToFilter($type, $filter);
        }
        $collection->setDataToAll('flag', 1);
        $collection->save();
    }

    public function reindexAll(){
        // reindex all data which are flagged 1 | initFilteredProductsCount
        $collection = Mage::getModel('esindexer/products')->getCollection()->addFieldToFilter('flag', 1);
        foreach($collection as $v){
            try{
                Mage::getModel('esindexer/products')->initFilteredProductsCount('manufacturer', $v->getData('attr_id'), $v->getData('esindexer_id'));
            } catch (Exception $e) {
                Mage::log(__METHOD__ . ': ' . $e->getMessage());
                return;
            }
        }
    }
}