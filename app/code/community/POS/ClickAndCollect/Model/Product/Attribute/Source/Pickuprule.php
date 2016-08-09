<?php

class POS_ClickAndCollect_Model_Product_Attribute_Source_Pickuprule extends Mage_Eav_Model_Entity_Attribute_Source_Config
{
    const DEFAULT_VALUE = 'No Pickup';

    protected $_configNodePath;

    public function __construct()
    {
        $this->_configNodePath = 'global/shipping/clickandcollect/attribute/pickuprule';
    }

    /**
     * Get a text for option value.
     *
     * @param string|int $value
     *
     * @return string
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions();
        if (sizeof($options) > 0) {
            foreach ($options as $option) {
                if (isset($option['value']) && $option['value'] == $value) {
                    return $option['label'];
                }
            }
        }
        if (isset($options[$value])) {
            return $option[$value];
        }

        return false;
    }

    /**
     * get Pickup Rule option array.
     *
     * @return array
     */
    public function getOptionArray()
    {
        $result = [];
        foreach ($this->getAllOptions() as $option) {
            $result[$option['value']] = $option['label'];
        }

        return $result;
    }
}
