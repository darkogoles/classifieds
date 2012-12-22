<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Attributeset
 *
 * @author Darko
 */
class Goles_Classifieds_Model_Category_Attribute_Source_Attributeset extends Mage_Eav_Model_Entity_Attribute_Source_Abstract {

    public function getAllOptions() {

        $defaultTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();

        $collection = Mage::getModel('eav/entity_attribute_set')->getCollection();
        $collection->addFieldToFilter('entity_type_id', $defaultTypeId);

        $attr_sets = array();
        foreach ($collection as $set) {
            $attr_sets[] = array('label' => $set->getAttributeSetName(), 'value' => $set->getAttributeSetId());
        }

        return $attr_sets;
    }

}
