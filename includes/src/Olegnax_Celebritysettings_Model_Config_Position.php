<?php
/**
 * @version   1.0 12.0.2012
 * @author    Olegnax http://www.olegnax.com <mail@olegnax.com>
 * @copyright Copyright (C) 2010 - 2012 Olegnax
 */

class Olegnax_Celebritysettings_Model_Config_Position
{

    public function toOptionArray()
    {
        return array(
            array(
	            'value'=>'top-left',
	            'label' => Mage::helper('celebritysettings')->__('Top Left')),
            array(
	            'value'=>'top-right',
	            'label' => Mage::helper('celebritysettings')->__('Top Right')),
            array(
	            'value'=>'bottom-left',
	            'label' => Mage::helper('celebritysettings')->__('Bottom Left')),
            array(
	            'value'=>'bottom-right',
	            'label' => Mage::helper('celebritysettings')->__('Bottom Right')),

        );
    }

}
