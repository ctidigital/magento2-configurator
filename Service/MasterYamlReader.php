<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Service;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\ComponentListInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Api\MasterYamlReaderInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

/**
 * Reads and validates the master YAML file (app/etc/master.yaml).
 */
class MasterYamlReader implements MasterYamlReaderInterface
{
    public function __construct(
        private readonly ComponentListInterface $componentList,
        private readonly LoggerInterface $log
    ) {
    }

    /**
     * @inheritDoc
     */
    public function read(): array
    {
        $masterPath = BP . '/app/etc/master.yaml';

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (!file_exists($masterPath)) {
            throw new ComponentException(
                "Master YAML does not exist. Please create one in $masterPath"
            );
        }

        $this->log->logComment("Found Master YAML");

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $yamlContents = file_get_contents($masterPath);
        $yaml         = new Parser();
        $master       = $yaml->parse($yamlContents);

        $this->validate($master);

        return $master;
    }

    /**
     * Validate the structure and contents of the parsed master YAML array.
     *
     * @throws ComponentException
     */
    private function validate(array $master): void
    {
        try {
            foreach ($master as $componentAlias => $componentConfig) {
                if (!isset($componentConfig['enabled'])) {
                    throw new ComponentException(
                        sprintf(
                            'It appears %s does not have a "enabled" node. This is required.',
                            $componentAlias
                        )
                    );
                }

                if (!$this->hasSource($componentConfig)) {
                    throw new ComponentException(
                        sprintf(
                            'It appears there are no data sources for the %s component.',
                            $componentAlias
                        )
                    );
                }

                if (!$this->isValidComponent($componentAlias)) {
                    throw new ComponentException(
                        sprintf(
                            '%s not a valid component. Please verify using bin/magento component:list.',
                            $componentAlias
                        )
                    );
                }
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * Return true when a component config block has at least one source defined
     * (either globally or under any environment node).
     *
     * @param  array<string, mixed> $componentConfig
     */
    private function hasSource(array $componentConfig): bool
    {
        if (isset($componentConfig['sources']) &&
            is_array($componentConfig['sources']) &&
            count($componentConfig['sources']) > 0
        ) {
            return true;
        }

        if (isset($componentConfig['env']) === true) {
            foreach ($componentConfig['env'] as $envData) {
                if (isset($envData['sources']) &&
                    is_array($envData['sources']) &&
                    count($envData['sources']) > 0
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return true when $componentName resolves to a registered ComponentInterface.
     */
    private function isValidComponent(string $componentName): bool
    {
        if ($this->log->getLogLevel() > OutputInterface::VERBOSITY_NORMAL) {
            $this->log->logQuestion(
                sprintf("Does the %s component exist?", $componentName)
            );
        }

        return $this->componentList->getComponent($componentName) instanceof ComponentInterface;
    }
}
