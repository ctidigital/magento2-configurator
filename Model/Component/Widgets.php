<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Framework\ObjectManagerInterface;
use CtiDigital\Configurator\Model\Exception\ComponentException;
use Magento\Widget\Model\ResourceModel\Widget\Instance\Collection as WidgetCollection;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;

class Widgets extends YamlComponentAbstract
{

    protected $alias = 'widgets';
    protected $name = 'Widgets';
    protected $description = 'Component to manage CMS Widgets';
    protected $widgetCollection;
    protected $themeCollection;

    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        WidgetCollection $collection,
        ThemeCollection $themeInterface
    ) {
        parent::__construct($log, $objectManager);
        $this->widgetCollection = $collection;
        $this->themeCollection = $themeInterface;
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

            $widget = $this->getWidgetByInstanceTypeAndTitle($widgetData['instance_type'], $widgetData['title']);

            $canSave = false;
            if (is_null($widget)) {
                $canSave = true;
                $widget = $this->objectManager->create(\Magento\Widget\Model\Widget\Instance::class);
            }

            foreach ($widgetData as $key => $value) {

                // @todo handle stores
                // Comma separated
                if ($key == "stores") {
                    continue;
                }

                // @todo handle parameters
                // serialized data a:3:{s:11:"anchor_text";s:11:"anchor text";s:5:"title";s:12:"anchor title";s:7:"page_id";s:1:"4";}
                if ($key == "parameters") {
                    continue;
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
     */
    public function getWidgetByInstanceTypeAndTitle($widgetInstanceType, $widgetTitle)
    {

        // Filter widget collection
        $widgets = $this->widgetCollection
            ->addFieldToFilter('instance_type', $widgetInstanceType)
            ->addFieldToFilter('title', $widgetTitle);
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
}
