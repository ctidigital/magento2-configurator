<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use Symfony\Component\Yaml\Yaml;

class Config extends ComponentAbstract
{

    protected $alias = 'config';
    protected $name = 'Configuration';
    protected $description = 'The component that sets the store/system configuration values';

    protected function canParseAndProcess()
    {
        $path = BP . '/' . $this->source;
        if (!file_exists($path)) {
            throw new ComponentException(
                sprintf("Could not find file in path %s", $path)
            );
        }
        return true;
    }

    protected function parseData($source = null)
    {

        try {
            if ($source == null) {
                throw new ComponentException(
                    sprintf('The %s component requires to have a file source definition.', $this->alias)
                );
            }

            $parser = new Yaml();
            return $parser->parse(file_get_contents($source));
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * @param array $data
     * @SuppressWarnings(PHPMD)
     */
    protected function processData($data = null)
    {
        try {
            $validScopes = array('global', 'websites', 'stores');
            foreach ($data as $scope => $configurations) {

                if (!in_array($scope, $validScopes)) {
                    throw new ComponentException(sprintf("This is not a valid scope '%s' in your config.", $scope));
                }

                if ($scope == "global") {
                    foreach ($configurations as $configuration) {
                        $this->setGlobalConfig($configuration['path'], $configuration['value']);
                    }
                }

                if ($scope == "websites") {
                    foreach ($configurations as $code => $websiteConfigurations) {
                        foreach ($websiteConfigurations as $configuration) {
                            $this->setWebsiteConfig($configuration['path'], $configuration['value'], $code);
                        }
                    }
                }

                if ($scope == "stores") {
                    foreach ($configurations as $code => $storeConfigurations) {
                        foreach ($storeConfigurations as $configuration) {
                            $this->setStoreConfig($configuration['path'], $configuration['value'], $code);
                        }
                    }
                }
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    private function setGlobalConfig($path, $value, $encrypted = 0)
    {
        $this->log->logComment(sprintf("Global Config: %s = %s", $path, $value));

        if ($encrypted) {
            $this->log->logError("There is no encryption support just yet");
        }
    }

    private function setWebsiteConfig($path, $value, $code, $encrypted = 0)
    {
        $logNest = 1;
        $this->log->logComment(sprintf("Website '%s' Config: %s = %s", $code, $path, $value), $logNest);

        if ($encrypted) {
            $this->log->logError("There is no encryption support just yet");
        }
    }

    private function setStoreConfig($path, $value, $code, $encrypted = 0)
    {
        $logNest = 2;
        $this->log->logComment(sprintf("Store '%s' Config: %s = %s", $code, $path, $value), $logNest);

        if ($encrypted) {
            $this->log->logError("There is no encryption support just yet");
        }
    }
}
