<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Edit
 *
 * @author Darko
 */
class Goles_Classifieds_Block_Form_Edit extends Mage_Core_Block_Template
{

    public function getSaveUrl()
    {

        $form = new Varien_Data_Form();

        $form->setId('testid');

        return 'getting' . $form->getHtml();
        exit;

        return Mage::getUrl('customer/address/formPost', array('_secure' => true, 'id' => null));
    }

    public function getForm()
    {
        $form = new Varien_Data_Form();
        $form->setId('frm_edit_add');
        $form->setUseContainer(true);
        $form->setAction($this->getUrl('ads/item/save'));
        $form->setMethod('POST');
        //$form->setClass('well');

        $fieldset = $form->addFieldset('base_fieldset', array('class' => 'fieldset'));

//        //$classifiedType = Mage::getModel('catalog/product_attribute');
//        $classifiedType = Mage::getModel('eav/entity_attribute')
//                ->loadByCode(Mage_Catalog_Model_Product::ENTITY, 'classified_type');
//
//        if ($classifiedType && $classifiedType->getId()) {
//            
//            $element = $fieldset->addField('classified_type', 'select', array(
//                'name' => 'product[classified_type]',
//                'label' => Mage::helper('classifieds')->__('Classified type: '),
//                'id' => 'classified_type',
//                'class' => $classifiedType->getFrontend()->getClass(),
//                //'options' => $options,
//                //'onchange' => 'javascript:onCategoryChange(\'' . $fieldset_id . '\', \'category_' . $root_cat_id . '\')'
//            ));
//            
//            $element->setValues($classifiedType->getSource()->getAllOptions(true, true));
//        }

        $categories = $this->getMainCategories();

        $root_cat_id = Mage::app()->getStore()->getRootCategoryId();
        $parent_cat_path = Mage::getModel('catalog/category')
                ->load($root_cat_id)
                ->getPath();

        $fieldset_id = 'fs_' . str_replace('/', '_', $parent_cat_path);
        $fieldset->setId($fieldset_id);

        $options = array();
        $options[0] = '';
        foreach ($categories as $cat) {
            $options[$cat['entity_id']] = $cat['name'];
        }

        $additional_class = Mage::getStoreConfig('classifieds_design/elements/form_element_class');

        $fieldset->addField('category_' . $root_cat_id, 'select', array(
            'name' => 'category_root',
            'label' => Mage::helper('classifieds')->__('Category: '),
            'id' => 'category_root',
            'class' => 'input-select ' . $additional_class,
            'options' => $options,
            'onchange' => 'javascript:onCategoryChange(\'' . $fieldset_id . '\', \'category_' . $root_cat_id . '\')',
        ));

        //return $form->toHtml();
        return $form;
    }

    public function getMainCategories()
    {

        $root_cat_id = Mage::app()->getStore()->getRootCategoryId();

        $cat_collection = Mage::getModel('catalog/category')
                ->getCategories($root_cat_id, 1, false, true, true);

        $categories = array();
        foreach ($cat_collection as $cat) {
            $categories[] = $cat->toArray(array('entity_id', 'name'));
        }

        return $categories;
    }

}

