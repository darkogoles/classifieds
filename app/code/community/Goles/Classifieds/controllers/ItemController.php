<?php

class Goles_Classifieds_ItemController extends Mage_Core_Controller_Front_Action {

    public function addAction() {
        
        
        $this->loadLayout();
        $this->renderLayout();
    }

    public function editAction() {
        //TODO: add if ajax only
        $postData = $this->getRequest()->getPost();

        if (!isset($postData['parent_id'])) {
            return;
        }

        if ((int) $postData['parent_id'] == false) {
            return;
        }

        $block = $this->getLayout()
                ->createBlock('classifieds/form_element_fieldset')
                ->getCategoryHtml($postData['parent_id']);
        //->getAttributesFieldset($postData['parent_id']);

        echo $block->toHtml();
    }

    public function saveAction() {

        $postData = $this->getRequest()->getPost();
        $productData = $this->getRequest()->getPost('product');

        if (!isset($postData['cid'])) {
            return;
        }

        $cid = $postData['cid'];

        $category = Mage::getModel('catalog/category')->load($cid);
        
        $cat_path = $category->getPath();
        
        $cat_ids = explode('/', $cat_path);
        
        $attribute_set_id = $category->getAttributeSet();

        $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getStoreId());

        if ($attribute_set_id) {
            $product->setAttributeSetId($attribute_set_id);
        }

        $product->setTypeId('simple');

        $wasLockedMedia = false;
        if ($product->isLockedAttribute('media')) {
            $product->unlockAttribute('media');
            $wasLockedMedia = true;
        }

        $user_id = Mage::getSingleton('customer/session')->getCustomer()->getId();
        if (!$user_id) {
            $user_id = 0;
        }

        $productData['sku'] = Mage::app()->getStore()->getId() . '-' . time() . '-' . $user_id;

        $productData['status'] = 1;

        $productData['visibility'] = 4;

        $productData['price'] = isset($productData['price']) ? $productData['price'] : 0;

        $productData['url_key'] = $this->_getUrlKey($productData['name'], $productData['sku']);

        $product->addData($productData);

        $product->setCategoryIds($cat_ids);

        if ($wasLockedMedia) {
            $product->lockAttribute('media');
        }

        if (Mage::app()->isSingleStoreMode()) {
            $product->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        }

        try {
            $product->save();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    private function _getUrlKey($product_name, $product_sku) {

        $cleansedstring = preg_replace("[^A-Za-z0-9-]", "-", $product_name);
        $cleansedstring.= '-' . $product_sku;

        return $cleansedstring;
    }

    /**
     * Validate product
     *
     */
    public function validateAction() {
        $response = new Varien_Object();
        $response->setError(false);

        try {
            $productData = $this->getRequest()->getPost('product');

            if ($productData && !isset($productData['stock_data']['use_config_manage_stock'])) {
                $productData['stock_data']['use_config_manage_stock'] = 0;
            }
            /* @var $product Mage_Catalog_Model_Product */
            $product = Mage::getModel('catalog/product');
            $product->setData('_edit_mode', true);
            if ($storeId = $this->getRequest()->getParam('store')) {
                $product->setStoreId($storeId);
            }
            if ($setId = $this->getRequest()->getParam('set')) {
                $product->setAttributeSetId($setId);
            }
            if ($typeId = $this->getRequest()->getParam('type')) {
                $product->setTypeId($typeId);
            }
            if ($productId = $this->getRequest()->getParam('id')) {
                $product->load($productId);
            }

            $dateFields = array();
            $attributes = $product->getAttributes();
            foreach ($attributes as $attrKey => $attribute) {
                if ($attribute->getBackend()->getType() == 'datetime') {
                    if (array_key_exists($attrKey, $productData) && $productData[$attrKey] != '') {
                        $dateFields[] = $attrKey;
                    }
                }
            }
            $productData = $this->_filterDates($productData, $dateFields);

            $product->addData($productData);
            $product->validate();
            /**
             * @todo implement full validation process with errors returning which are ignoring now
             */
//            if (is_array($errors = $product->validate())) {
//                foreach ($errors as $code => $error) {
//                    if ($error === true) {
//                        Mage::throwException(Mage::helper('catalog')->__('Attribute "%s" is invalid.', $product->getResource()->getAttribute($code)->getFrontend()->getLabel()));
//                    }
//                    else {
//                        Mage::throwException($error);
//                    }
//                }
//            }
        } catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
            $response->setError(true);
            $response->setAttribute($e->getAttributeCode());
            $response->setMessage($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $response->setError(true);
            $response->setMessage($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_initLayoutMessages('adminhtml/session');
            $response->setError(true);
            $response->setMessage($this->getLayout()->getMessagesBlock()->getGroupedHtml());
        }

        $this->getResponse()->setBody($response->toJson());
    }

}

