<?php
class Goles_Classifieds_Model_Product_Attribute_Source_Classifiedtype extends Mage_Eav_Model_Entity_Attribute_Source_Abstract {
    
    const CLASSIFIED_TYPE_SALE = 0;
    const CLASSIFIED_TYPE_BUYING = 1;
    const CLASSIFIED_TYPE_HIRING = 2;
    const CLASSIFIED_TYPE_RENTAL = 3;
    const CLASSIFIED_TYPE_REPLACEMENT = 4;
    
    
    public function getAllOptions() {

        $result = array(
            array('label'=>'Sale', 'value'=>self::CLASSIFIED_TYPE_SALE),
            array('label'=>'Buying', 'value'=>self::CLASSIFIED_TYPE_BUYING),
            array('label'=>'Hiring', 'value'=>self::CLASSIFIED_TYPE_HIRING),
            array('label'=>'Rental', 'value'=>self::CLASSIFIED_TYPE_RENTAL),
            array('label'=>'Replacement', 'value'=>self::CLASSIFIED_TYPE_REPLACEMENT),
        );
        
        return $result;
    }

}
