<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Model\LoggerInterface;
use Magento\Framework\ObjectManagerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Widget\Model\ResourceModel\Widget\Instance\Collection as WidgetCollection;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Store\Model\StoreFactory;

class Widgets extends YamlComponentAbstract
{

    protected $alias = 'widgets';
    protected $name = 'Widgets';
    protected $description = 'Component to manage CMS Widgets';
    protected $widgetCollection;
    protected $themeCollection;
    protected $storeFactory;

    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        WidgetCollection $collection,
        StoreFactory $storeFactory,
        ThemeCollection $themeCollection
    ) {
        parent::__construct($log, $objectManager);
        $this->widgetCollection = $collection;
        $this->themeCollection = $themeCollection;
        $this->storeFactory = $storeFactory;
    }

    protected function processData($data = null)
    {
        try {
            foreach ($data as $widgetData) {
                $this->processWidget($widgetData);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    public function processWidget($widgetData)
    {
        try {
            $this->validateInstanceType($widgetData['instance_type']);

            $widget = $this->findWidgetByInstanceTypeAndTitle($widgetData['instance_type'], $widgetData['title']);

            $canSave = false;
            if (is_null($widget)) {
                $canSave = true;
                $widget = $this->objectManager->create(\Magento\Widget\Model\Widget\Instance::class);
            }

            foreach ($widgetData as $key => $value) {

                // @todo handle stores
                // Comma separated
                if ($key == "stores") {
                    $key = "store_ids";
                    $value = $this->getCommaSeparatedStoreIds($value);
                }

                if ($key == "parameters") {
                    $key = "widget_parameters";
                    $value = $this->populateWidgetParameters($value);
                }

                if ($key == "theme") {
                    $key = "theme_id";
                    $value = $this->getThemeId($value);
                }

                if ($widget->getData($key) == $value) {
                    $this->log->logComment(sprintf("Widget %s = %s", $key, $value), 1);
                    continue;
                }

                $canSave = true;
                $widget->setData($key, $value);
                $this->log->logInfo(sprintf("Widget %s = %s", $key, $value), 1);
            }

            if ($canSave) {
                $widget->save();
                $this->log->logInfo(sprintf("Saved Widget %s", $widget->getTitle()), 1);
            }

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    public function validateInstanceType($instanceType)
    {
        $this->log->logComment(sprintf("Checking if %s is a valid instance", $instanceType));
        $instanceType = '\\' . $instanceType;
        $instance = $this->objectManager->create($instanceType);
        if (!$instance instanceof $instanceType) {
            throw new ComponentException("Instance %s is invalid", $instanceType);
        }
        $this->log->logComment(sprintf("Found instance %s.", $instanceType));
        // @todo validate parameters somehow using the $fields
    }

    /**
     * @param $widgetInstanceType
     * @param $widgetTitle
     * @return \Magento\Framework\DataObject|null
     * @throws ComponentException
     * @todo get this one to work instead of findWidgetByInstanceTypeAndTitle()
     */
    public function getWidgetByInstanceTypeAndTitle($widgetInstanceType, $widgetTitle)
    {

        // Clear any existing filters applied to the widget collection
        $this->widgetCollection->getSelect()->reset(\Zend_Db_Select::WHERE);
        $this->widgetCollection->removeAllItems();

        // Filter widget collection
        $widgets = $this->widgetCollection
            ->addFieldToFilter('instance_type', $widgetInstanceType)
            ->addFieldToFilter('title', $widgetTitle)
            ->load();
        // @todo add store filter


        // If we have more than 1, throw an exception for now. Needs store filter to drill down the widgets further
        // into a single widget.
        if ($widgets->count() > 1) {
            throw new ComponentException('Application Error: Need to figure out how to handle same titled widgets');
        }

        // If there are no widgets, then it is like it doesn't even exist.
        // Return null
        if ($widgets->count() < 1) {
            return null;
        }

        // Return the widget itself since it is a perfect match
        return $widgets->getFirstItem();
    }

    /**
     * @param $widgetInstanceType
     * @param $widgetTitle
     * @return mixed|null
     */
    public function findWidgetByInstanceTypeAndTitle($widgetInstanceType, $widgetTitle)
    {

        // Loop through the widget collection to find any matches.
        foreach ($this->widgetCollection as $widget) {
            if ($widget->getTitle() == $widgetTitle && $widget->getInstanceType() == $widgetInstanceType) {

                // Return the widget if there is a match
                return $widget;
            }
        }

        // If there are no widgets, then it is like it doesn't even exist.
        // Return null
        return null;
    }

    public function getThemeId($themeCode)
    {

        // Filter Theme Collection
        $themes = $this->themeCollection->addFilter('code', $themeCode);

        if ($themes->count() == 0) {
            throw new ComponentException(sprintf('Could not find any themes with the theme code %s', $themeCode));
        }

        $theme = $themes->getFirstItem();

        return $theme->getId();
    }

    /**
     * @param array $parameters
     * @return string
     * @todo better support with parameters that reference IDs of objects
     */
    public function populateWidgetParameters(array $parameters)
    {

        // Default property return
        return serialize($parameters);
    }

    /**
     * @param $stores
     * @return string
     */
    public function getCommaSeparatedStoreIds($stores)
    {
        $storeIds = array();
        foreach ($stores as $code) {
            $storeView = $this->storeFactory->create();
            $storeView->load($code, 'code');
            if (!$storeView->getId()) {
                throw new ComponentException(sprintf('Cannot find store with code %s', $code));
            }
            $storeIds[] = $storeView->getId();
        }
        return implode(',', $storeIds);
    }
}
