<?php

namespace Motive\Easymarketing\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Motive\Easymarketing\Helper\Data;

class Attributes extends Field
{
    protected $_helper;

    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element) {
        $html = '<table id="' . $element->getId() . '_table" class="ui_select_table" cellspacing="0" border="0" style="border: 1px solid #000;">';
        $html .= '<tbody><tr><td width="50%" style="padding: 0;">';

        $selected = explode(',', $element->getValue());

        $attributes = $this->_helper->getAllAttributes();

        $html .= '<ul id="' . $element->getId() . '_source" class="ui_select source sortable" style="list-style-type: none; height: 200px; overflow-x: hidden; overflow-y: scroll; margin-bottom: 0;">';
        if($attributes) {
            foreach($attributes as $attribute) {
                if(in_array($attribute['value'], $selected)) continue;
                $html .= '<li data-code="' . $attribute['value'] . '" style="border: 1px solid #bbb; background-color: #FFB6C1; padding: 3px; cursor: pointer; margin: 2px; font-size: 12px;">' . $attribute['label'] . '</li>';
            }
        }

        $html .= '</ul></td>';

        $html .= '<td width="50%" style="padding: 0;"><ul id="' . $element->getId() . '_selected" class="ui_select selected sortable" style="list-style-type: none; height: 200px; overflow-x: hidden; overflow-y: scroll; margin-bottom: 0;">';

        $selectedIterator = 0;
        $defaultValue = "";
        foreach($selected as $value) {
            if($selectedIterator == 0) {
                $defaultValue = $value;
                $selectedIterator++;
                continue;
            }
            if(!empty($value)) {
                $attribute = $this->_helper->getAttributeByCode($value);

                $html .= '<li data-code="' . $attribute->getAttributeCode() . '" style="border: 1px solid #bbb; background-color: #90EE90; padding: 3px; cursor: pointer; margin: 2px; font-size: 12px;">' . $attribute->getStoreLabel() . ' (' . $attribute->getAttributeCode() . ')' . '</li>';
            }
        }

        $html .= '</ul></td></tr></tbody></table>';
        $html .= '<br><div><span style="display: inline-block; width: 30%;">Standardwert: </span><input id="' . $element->getId() . '_default" type="text" style="width:70%;" value="' . $defaultValue . '"></span>';
        $html .= '<div style="display:none;">' . $element->getElementHtml() . '</div>';
        $html .= '<script type="text/javascript">
                require(["jquery", "jquery/ui"], function(jQuery){
                    jQuery(document).ready( function() {
                        jQuery("#' . $element->getId() . '_source, #' . $element->getId() . '_selected").sortable({
                            connectWith: "#' . $element->getId() . '_table .sortable",
                            stop: function(event, ui) {
                                var values = [];
                                values.push(jQuery("#' . $element->getId() . '_default").val());
                                jQuery("#' . $element->getId() . '_selected").find("li").each(function(index, element) {
                                    values.push(jQuery(element).data("code"));
                                });
                                jQuery("#' . $element->getId() . '").val(values.join(","));                                    
                            }
                        }).disableSelection();
                    
                        jQuery("#' . $element->getId() . '_default").change(function() {
                            var values = [];
                            values.push(jQuery(this).val());
                            jQuery("#' . $element->getId() . '_selected").find("li").each(function(index, element) {
                                values.push(jQuery(element).data("code"));
                            });
                            jQuery("#' . $element->getId() . '").val(values.join(","));    
                        });
                    });
                });
                </script>';

        return $html;
    }
}