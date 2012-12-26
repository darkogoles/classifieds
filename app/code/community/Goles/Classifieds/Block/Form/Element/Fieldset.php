<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Fieldset
 *
 * @author Darko
 */
class Goles_Classifieds_Block_Form_Element_Fieldset extends Mage_Core_Block_Template
{

    protected function _construct()
    {

        parent::_construct();

        $this->setTemplate('classifieds/form/element/fieldset.phtml');
    }

    public function getCategoryHtml($parent_id = 0)
    {

        if ($this->_categoryHasChilds($parent_id)) {
            return $this->getCategoryListFieldset($parent_id);
        }
        return $this->getAttributesFieldset($parent_id);
    }

    private function _categoryHasChilds($parent_id)
    {

        $count = Mage::getModel('catalog/category')->getCollection()->addFieldToFilter('parent_id', $parent_id)->count();
        if ($count > 0) {
            return true;
        }
        return false;
    }

    private function _getOptions($parent_id)
    {

        $cat_collection = Mage::getModel('catalog/category')
                ->getCategories($parent_id, 1, false, true, true);

        $categories = array();
        foreach ($cat_collection as $cat) {
            $categories[] = $cat->toArray(array('entity_id', 'name'));
        }

        return $categories;
    }

    public function getCategoryListFieldset($parent_id = 0)
    {

        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('base_fieldset', array('class' => 'fieldset well'));

        $cat_options = $this->_getOptions($parent_id);

        $parent_cat_path = Mage::getModel('catalog/category')
                ->load($parent_id)
                ->getPath();

        $fieldset_id = 'fs_' . str_replace('/', '_', $parent_cat_path);
        $fieldset->setId($fieldset_id);

        if (!empty($cat_options)) {
            // Zend_Debug::dump($cat_options);exit;  
            $options = array();
            $options[0] = '';

            foreach ($cat_options as $cat) {
                $options[$cat['entity_id']] = $cat['name'];
            }

            $additional_class = Mage::getStoreConfig('classifieds_design/elements/form_element_class');

            $select = $fieldset->addField('category_' . $parent_id, 'select', array(
                'name' => 'category',
                'label' => Mage::helper('classifieds')->__('Category: '),
                'id' => 'category',
                'class' => 'input-select ' . $additional_class,
                'options' => $options,
                'onchange' => 'javascript:onCategoryChange(\'' . $fieldset_id . '\', \'category_' . $parent_id . '\')'
                    ));
            //$this->setText($fieldset->toHtml());
            $this->setType('category_select');
            $this->setFieldset($fieldset);
        }

        return $this;
    }

    public function getAttributesFieldset($cat_id)
    {

        $category = Mage::getModel('catalog/category')->load($cat_id);

        if ((!$category) || (!$category->getId())) {
            return '';
        }

        $attribute_set_id = $category->getAttributeSet();

        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addVisibleFilter()
                ->setAttributeSetFilter($attribute_set_id)
                ->checkConfigurableProducts()
                ->addFieldToFilter('is_visible_on_front', 1)
                ->load();

        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('base_fieldset' . $cat_id, array('class' => 'fieldset'));

        $parent_cat_path = Mage::getModel('catalog/category')
                ->load($cat_id)
                ->getPath();

        $fieldset_id = 'fs_' . str_replace('/', '_', $parent_cat_path);
        $fieldset->setId($fieldset_id);

        $fieldset->setAttributeSetId($attribute_set_id);

        $usedAttributeGroupIds = array();

        $this->_addElementTypes($fieldset);

        foreach ($attributes as $attribute) {
            /* @var $attribute Mage_Eav_Model_Entity_Attribute */
            if (!$attribute || ($attribute->hasIsVisible() && !$attribute->getIsVisible())) {
                continue;
            }
            if (($inputType = $attribute->getFrontend()->getInputType())
                    /* && !in_array($attribute->getAttributeCode(), $exclude) */
                    && ('media_image' != $inputType)
                    && ('price' != $inputType)
                    && ('weight' != $inputType)
                    && ('gallery' != $inputType)
            ) {

                $fieldType = $inputType;
                $rendererClass = $attribute->getFrontend()->getInputRendererClass();
                if (!empty($rendererClass)) {
                    $fieldType = $inputType . '_' . $attribute->getAttributeCode();
                    $fieldset->addType($fieldType, $rendererClass);
                }

                $element = $fieldset->addField($attribute->getAttributeCode(), $fieldType, array(
                            'name' => 'product[' . $attribute->getAttributeCode() . ']',
                            'label' => $attribute->getFrontendLabel(),
                            'class' => $attribute->getFrontend()->getClass(),
                            'required' => $attribute->getIsRequired(),
                            'note' => $attribute->getNote(),
                                )
                        )
                        ->setEntityAttribute($attribute);

                if ($groupId = $attribute->getAttributeGroupId()) {
                    $usedAttributeGroupIds[] = $groupId;
                    $element->setAttributeGroupId($groupId);
                }

                $additional_class = Mage::getStoreConfig('classifieds_design/elements/form_element_class');
                $element->addClass($additional_class);

                if ($inputType == 'select') {
                    $element->setValues($attribute->getSource()->getAllOptions(true, true));
                } else if ($inputType == 'multiselect') {
                    $element->setValues($attribute->getSource()->getAllOptions(false, true));
                    $element->setCanBeEmpty(true);
                } else if ($inputType == 'date') {
                    $element->setImage($this->getSkinUrl('images/grid-cal.gif'));
                    $element->setFormat(Mage::app()->getLocale()->getDateFormatWithLongYear());
                } else if ($inputType == 'multiline') {
                    $element->setLineCount($attribute->getMultilineCount());
                }
//                } else if ($inputType == 'text') {
//                    $element->addClass('input-xxlarge');
//                }
                if (!$element->getAttributeGroupId()) {
                    $element->setAttributeGroupId(0);
                }
            }
        }

        $fieldset->setUsedAttributeGroupIds(array_unique($usedAttributeGroupIds));

        $fieldset->addField('submit', 'submit', array(
            'required' => true,
            'value' => 'Save',
            'tabindex' => 1,
            'name' => 'submit',
            'class' => 'btn btn-primary'
        ));

        $fieldset->addField('cid', 'hidden', array(
            'required' => true,
            'value' => $cat_id,
            'name' => 'cid',
        ));

        //$this->setText($fieldset->toHtml());
        $this->setType('attributes_fieldset');
        $this->setFieldset($fieldset);
        return $this;
    }

    protected function _addElementTypes(Varien_Data_Form_Abstract $baseElement)
    {
        $types = $this->_getAdditionalElementTypes();
        foreach ($types as $code => $className) {
            $baseElement->addType($code, $className);
        }
    }

    protected function _getAdditionalElementTypes()
    {
        $result = array(
            'price' => Mage::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_price'),
            'weight' => Mage::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_weight'),
            'gallery' => Mage::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_gallery'),
            'image' => Mage::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_image'),
            'boolean' => Mage::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_boolean'),
            'textarea' => Mage::getConfig()->getBlockClassName('adminhtml/catalog_helper_form_wysiwyg')
        );

        $response = new Varien_Object();
        $response->setTypes(array());

        foreach ($response->getTypes() as $typeName => $typeClass) {
            $result[$typeName] = $typeClass;
        }

        return $result;
    }

    public function getAttributeGroupTitle($groupName)
    {
        if ($groupName) {
            $tmp = explode('::', $groupName);
            if (is_array($tmp) && count($tmp) == 2) {
                $groupName = $tmp[0];
            }
        }
        
        return $groupName;
    }

    public function getFieldsetElementsRowsColumns($fieldset, $groupId, $groupName = false)
    {

        $colNum = 3;
        $forceColumns = false;

        if ($groupName) {
            $tmp = explode('::', $groupName);
            if (is_array($tmp) && count($tmp) == 2) {
                $colNum = (int) $tmp[1];
                $forceColumns = true;
            }
        }

        $elements = $fieldset->getSortedElements();

        $singleColElements = array();
        $multiColElements = array();
        $otherElements = array();
        $buttonElements = array();

        foreach ($elements as $element) {

            $elGroupId = $element->getAttributeGroupId();
            if ($elGroupId != $groupId) {
                continue;
            }

            if (true === $forceColumns) {
                switch ($element->getType()) {
                    //singleCol
                    case 'editor':
                    case 'gallery':
                    case 'image':
                        $singleColElements[] = $element;
                        break;
                    //multiCol
                    case 'text':
                    case 'checkbox':
                    case 'checkboxes':
                    case 'date':
                    case 'file':
                    case 'label':
                    case 'imagefile':
                    case 'radio':
                    case 'select':
                    case 'time':
                    case 'radios':
                    case 'textarea':
                        $multiColElements[] = $element;
                        break;
                    //other
                    case 'hidden':
                        $otherElements[] = $element;
                        break;
                    //button
                    case 'button':
                    case 'submit':
                        $buttonElements[] = $element;
                        break;
                    default:
                        $singleColElements[] = $element;
                        break;
                }
            } else {
                switch ($element->getType()) {
                    //singleCol
                    case 'editor':
                    case 'gallery':
                    case 'image':
                    case 'textarea':
                        $singleColElements[] = $element;
                        break;
                    //multiCol
                    case 'text':
                    case 'checkbox':
                    case 'checkboxes':
                    case 'date':
                    case 'file':
                    case 'label':
                    case 'imagefile':
                    case 'radio':
                    case 'select':
                    case 'time':
                    case 'radios':
                        $multiColElements[] = $element;
                        break;
                    //other
                    case 'hidden':
                        $otherElements[] = $element;
                        break;
                    //button
                    case 'button':
                    case 'submit':
                        $buttonElements[] = $element;
                        break;
                    default:
                        $singleColElements[] = $element;
                        break;
                }
            }
        }
        //Handle multi column elements

        $count = count($multiColElements);
//            $col1 = floor(($count + $colNum - 1) / $colNum);
//            $col2 = floor(($count + $colNum - 2) / $colNum);
//            $col3 = floor(($count + $colNum - 3) / $colNum);

        $columns = array();
        for ($i = 1; $i <= $colNum; $i++) {
            $colCount = floor(($count + $colNum - $i) / $colNum);

            if ($colCount > 0) {
                $columns[] = array('items_count' => $colCount);
            }
        }

        $lastIndex = 0;
        for ($i = 0; $i < count($columns); $i++) {
            $numItemsInCol = $columns[$i]['items_count'];
            for ($j = 0; $j < $numItemsInCol; $j++) {
                $columns[$i]['element'][$j] = $multiColElements[$lastIndex];
                $lastIndex++;
            }
        }

        $rows = array();

        $rows['multicol'] = $columns;
        $rows['singlecol'] = $singleColElements;
        $rows['other'] = $otherElements;
        $rows['button'] = $buttonElements;

        return $rows;
    }

    public function getAttributeSetGroups($fieldset)
    {
        $attributeSetId = $fieldset->getAttributeSetId();

        if (!$attributeSetId) {
            return false;
        }

        $groups = Mage::getModel('eav/entity_attribute_group')
                ->getResourceCollection()
                ->setAttributeSetFilter($attributeSetId)
                ->setSortOrder(Mage_Eav_Model_Resource_Entity_Attribute_Group_Collection::SORT_ORDER_ASC)
                ->load();

        if ($groups->getSize() > 0) {
            return $groups;
        }

        return false;
    }

}
