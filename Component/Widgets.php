<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Widget\Model\ResourceModel\Widget\Instance\Collection as WidgetCollection;
use Magento\Widget\Model\Widget\Instance;
use Magento\Widget\Model\Widget\InstanceFactory as WidgetInstanceFactory;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Magento\Store\Model\StoreFactory;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Magento\Framework\DB\Select;
use Magento\Framework\Serialize\SerializerInterface;

class Widgets implements ComponentInterface
{

    protected string $alias = 'widgets';
    protected string $name = 'Widgets';
    protected string $description = 'Component to manage CMS Widgets';

    public function __construct(
        private readonly WidgetCollection $widgetCollection,
        private readonly WidgetInstanceFactory $widgetFactory,
        private readonly StoreFactory $storeFactory,
        private readonly ThemeCollection $themeCollection,
        private readonly SerializerInterface $serializer,
        private readonly AppState $appState,
        private readonly LoggerInterface $log
    ) {}

    public function execute(mixed $data = null): void
    {
        try {
            foreach ($data as $widgetData) {
                $this->processWidget($widgetData);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    public function processWidget(mixed $widgetData): void
    {
        try {
            $widget = $this->findWidgetByInstanceTypeAndTitle($widgetData['instance_type'], $widgetData['title']);

            $canSave = false;
            if ($widget === null) {
                $canSave = true;
                /**
                 * @var Instance $widget
                 */
                $widget = $this->widgetFactory->create();
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
                    $this->logValue($key, $value, 'logComment', 1);
                    continue;
                }

                $canSave = true;
                $widget->setData($key, $value);
                $this->logValue($key, $value, 'logInfo', 1);
            }

            if ($canSave) {
                // Widget::save() resolves theme_dir which requires a frontend area context.
                $this->appState->emulateAreaCode(AppArea::AREA_FRONTEND, [$widget, 'save']);
                $this->log->logInfo(sprintf("Saved Widget %s", $widget->getTitle()), 1);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * @throws ComponentException
     * @todo get this one to work instead of findWidgetByInstanceTypeAndTitle()
     */
    public function getWidgetByInstanceTypeAndTitle(string $widgetInstanceType, string $widgetTitle): ?\Magento\Framework\DataObject
    {

        // Clear any existing filters applied to the widget collection
        $this->widgetCollection->getSelect()->reset(Select::WHERE);
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

    public function findWidgetByInstanceTypeAndTitle(string $widgetInstanceType, string $widgetTitle): mixed
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

    public function getThemeId(mixed $themeCode): mixed
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
     * @todo better support with parameters that reference IDs of objects
     */
    public function populateWidgetParameters(array $parameters): string
    {
        // Default property return
        return $this->serializer->serialize($parameters);
    }

    public function getCommaSeparatedStoreIds(mixed $stores): string
    {
        $storeIds = [];
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

    private function logValue(string $key, mixed $value, string $method, int $nest): void
    {
        if (!is_array($value)) {
            $this->log->$method(sprintf('Widget %s = %s', $key, $value), $nest);
            return;
        }

        $this->log->$method(sprintf('Widget %s:', $key), $nest);
        foreach ($value as $subKey => $subValue) {
            $this->logValue((string)$subKey, $subValue, $method, $nest + 1);
        }
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
