<?php

$installer = $this;

$installer->startSetup();

$installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'attribute_set', array(
    'group' => 'General information',
    'type' => 'int',
    'backend' => '',
    'frontend' => '',
    'label' => 'Attribute Set',
    'input' => 'select',
    'class' => '',
    'source' => 'classifieds/category_attribute_source_attributeset',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible' => true,
    'required' => true,
    'user_defined' => true,
    'default' => '',
    'searchable' => false,
    'filterable' => false,
    'comparable' => false,
    'visible_on_front' => false,
    'used_in_product_listing' => true,
    'unique' => false,
    'is_configurable' => false
));

//remove "system" (is_user_defined) from some product attributes
$attributeCodes = array(
    'msrp',
    'msrp_display_actual_price_type',
    'msrp_enabled',
    'news_from_date',
    'news_to_date',
    'recurring_profile',
    'is_recurring',
    'country_of_manufacture',
    'small_image',
    'thumbnail',
    'gift_message_available',
    'enable_googlecheckout',
    'is_imported',
    'tier_price',
    'special_price',
    'special_from_date',
    'special_to_date',
    'custom_design_from',
    'custom_design_to',
    'meta_title',
    'meta_keyword',
    'meta_description',
    'weight',
    'group_price',
    'tax_class_id',
    'price_view'
);

foreach ($attributeCodes as $attributeCode) {
    if (!$this->getAttributeId(Mage_Catalog_Model_Product::ENTITY, $attributeCode)) {
        continue;
    }
    $installer->updateAttribute(
            Mage_Catalog_Model_Product::ENTITY, $attributeCode, 'is_user_defined', 1
    );
}

$defaultTypeId = Mage::getModel('catalog/product')->getResource()->getTypeId();
$defaultAttributeSetId = Mage::getModel('eav/entity_type')->load($defaultTypeId)->getDefaultAttributeSetId();

/* @var $model Mage_Eav_Model_Entity_Attribute_Set */
$model = Mage::getModel('eav/entity_attribute_set')
        ->setEntityTypeId($defaultTypeId);

$model->setAttributeSetName('Classifieds_base'); //check if Lite named attribute set exist already?
$model->save();

$groups = Mage::getModel('eav/entity_attribute_group')
        ->getResourceCollection()
        ->setAttributeSetFilter($defaultAttributeSetId)
        ->load();

$newGroups = array();
foreach ($groups as $group) {
    $newGroup = clone $group;
    $newGroup->setId(null)
            ->setAttributeSetId($model->getId())
            ->setDefaultId($group->getDefaultId());

    $groupAttributesCollection = Mage::getModel('eav/entity_attribute')
            ->getResourceCollection()
            ->setAttributeGroupFilter($group->getId())
            ->load();

    $newAttributes = array();
    foreach ($groupAttributesCollection as $attribute) {

        if ($attribute->getIsUserDefined() &&
                !in_array(
                        $attribute->getAttributeCode(), array('cost',
                    'small_image',
                    'thumbnail'
                        )
        )) {
            continue;
        }

        $newAttribute = Mage::getModel('eav/entity_attribute')
                ->setId($attribute->getId())
                ->setAttributeSetId($model->getId())
                ->setEntityTypeId($model->getEntityTypeId())
                ->setSortOrder($attribute->getSortOrder());
        $newAttributes[] = $newAttribute;
    }
    if (!empty($newAttributes)) {
        $newGroup->setAttributes($newAttributes);
        $newGroups[] = $newGroup;
    }
}

$model->setGroups($newGroups);

$model->save();

$frontent_visible = array
    (
    'name',
    'short_description',
    'description',
    'price',
    'image',
);

foreach ($frontent_visible as $attributeCode) {
    if (!$this->getAttributeId(Mage_Catalog_Model_Product::ENTITY, $attributeCode)) {
        continue;
    }
    $installer->updateAttribute(
            Mage_Catalog_Model_Product::ENTITY, $attributeCode, 'is_visible_on_front', 1
    );
    $installer->updateAttribute(
            Mage_Catalog_Model_Product::ENTITY, $attributeCode, 'is_wysiwyg_enabled', 0
    );
    $installer->updateAttribute(
            Mage_Catalog_Model_Product::ENTITY, $attributeCode, 'is_html_allowed_on_front', 0
    );
}


$installer->endSetup();

