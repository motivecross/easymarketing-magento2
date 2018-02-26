<?php

#######
# Motive X
# Sylter Str. 15, 90425 Nürnberg, Germany
# Telefon: +49 (0)911/49 522 566
# Mail: info@motive.de
# Internet: www.motive-x.com
#######

namespace Motive\Easymarketing\Controller\Api;

use Motive\Easymarketing\Helper\Data;

class Categories extends \Magento\Framework\App\Action\Action
{
    protected $_helper;

    protected $_categoryHelper;

    protected $_categoryRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Data $helper,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository
    ) {
        $this->_helper = $helper;
        $this->_categoryHelper = $categoryHelper;
        $this->_categoryRepository = $categoryRepository;
        return parent::__construct($context);
    }

    public function execute() {

        $this->_helper->log('Categories Endpoint START');

        try {

            $this->_helper->apiStart();

            $params = $this->_helper->getAllMandatoryParams(array('id'));

            if(!is_numeric($params['id']) || $params['id'] <= 0) {
                $this->_helper->sendErrorAndExit('Keine gültige ID');
            }

            try {
                $category = $this->_categoryRepository->get($params['id']);
            } catch(\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->_helper->sendErrorAndExit('Keine Kategorie mit dieser ID');
            }

            $children = $category->getChildren();
            if(empty($children)) {
                $children = array();
            } else {
                $children = explode(',', $children);
            }

            $resultArray = array('id' => $category->getId(),
                'name' => $category->getName(),
                'url' => $this->_categoryHelper->getCategoryUrl($category),
                'children' => $children
            );

            $this->_helper->sendResponse($resultArray);
            
        } catch(\Exception $exception) {
            $errorMessage = $exception->getFile() . " - " . $exception->getLine() . ": " . $exception->getMessage() . "\n". $exception->getTraceAsString();
            $this->_helper->error($errorMessage);
            throw new \Exception($errorMessage);
        }

        $this->_helper->log('Categories Endpoint END');
    }
}

