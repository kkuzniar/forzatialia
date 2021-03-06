<?php
/**
 * @version   1.0 12.0.2012
 * @author    Olegnax http://www.olegnax.com <mail@olegnax.com>
 * @copyright Copyright (C) 2010 - 2012 Olegnax
 */

class Olegnax_Celebritysettings_Block_Adminhtml_Restore_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $isElementDisabled = false;
        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('celebritysettings')->__('Restore Parameters')));

        /**
         * Check is single store mode
         */
        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('store_id', 'multiselect', array(
                'name'      => 'stores[]',
                'label'     => Mage::helper('cms')->__('Store View'),
                'title'     => Mage::helper('cms')->__('Store View'),
                'required'  => true,
                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                'disabled'  => $isElementDisabled
            ));
        }
        else {
            $fieldset->addField('store_id', 'multiselect', array(
                'name'      => 'stores[]',
                'label'     => Mage::helper('cms')->__('Store View'),
                'title'     => Mage::helper('cms')->__('Store View'),
                'required'  => true,
                'values'    => array(array('value' => 0, 'label' => 'Default Store View')),
                'disabled'  => $isElementDisabled
            ));
        }

        $fieldset->addField('clear_scope', 'checkbox', array(
                'name'  => 'clear_scope',
                'value' => 1,
                'label' => Mage::helper('celebritysettings')->__('Clear Configuration Scopes'),
                'title' => Mage::helper('celebritysettings')->__('Clear Configuration Scopes'),
                'note' => Mage::helper('celebritysettings')->__('Check if you want to clear theme settings for all scopes ( default, websites, stores ) '),
            )
        );

	    $fieldset->addField('setup_cms', 'checkbox', array(
            'label' => Mage::helper('celebritysettings')->__('Restore Default Cms Pages & Blocks'),
		    'note' => Mage::helper('celebritysettings')->__('ATTENTION: All changes to cms pages/blocks will be lost'),
            'required' => false,
            'name' => 'setup_cms',
            'value' => 1,
        ));

        $form->setAction($this->getUrl('*/*/restore'));
        $form->setMethod('post');
        $form->setUseContainer(true);
        $form->setId('edit_form');

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
