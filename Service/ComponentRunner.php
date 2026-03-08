<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Service;

use CtiDigital\Configurator\Api\ComponentListInterface;
use CtiDigital\Configurator\Api\ComponentRunnerInterface;
use CtiDigital\Configurator\Api\FileComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Api\SourceDataParserInterface;
use CtiDigital\Configurator\Exception\ComponentException;

/**
 * Executes a single configurator component against its master-YAML configuration block.
 *
 * Handles:
 *  - Global sources (run for every environment)
 *  - Environment-specific sources (run only for the active environment)
 *  - FileComponentInterface components that receive a raw path instead of parsed data
 *  - Missing-file tolerance when $ignoreMissingFiles is true
 */
class ComponentRunner implements ComponentRunnerInterface
{
    public function __construct(
        private readonly ComponentListInterface $componentList,
        private readonly LoggerInterface $log,
        private readonly SourceDataParserInterface $parser
    ) {
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(
        string $componentAlias,
        array $componentConfig,
        string $environment,
        bool $ignoreMissingFiles
    ): void {
        $this->logHeader($componentAlias);

        $component  = $this->componentList->getComponent($componentAlias);
        $sourceType = $componentConfig['type'] ?? null;

        // ── Global sources ──────────────────────────────────────────────────
        if (isset($componentConfig['sources'])) {
            foreach ($componentConfig['sources'] as $source) {
                try {
                    $sourceData = ($component instanceof FileComponentInterface)
                        ? $source
                        : $this->parser->parse($source, $sourceType);
                    $component->execute($sourceData);
                } catch (ComponentException $e) {
                    if ($ignoreMissingFiles === true) {
                        $this->log->logInfo("Skipping file {$source} as it could not be found.");
                        continue;
                    }
                    throw $e;
                }
            }
        }

        // ── Environment-specific sources ────────────────────────────────────
        if (!isset($componentConfig['env'])) {
            $this->log->logComment(
                sprintf("No environment node for '%s' component", $componentAlias)
            );
            return;
        }

        if (!isset($componentConfig['env'][$environment])) {
            $this->log->logComment(
                sprintf(
                    "No '%s' environment specific node for '%s' component",
                    $environment,
                    $componentAlias
                )
            );
            return;
        }

        if (!isset($componentConfig['env'][$environment]['sources'])) {
            $this->log->logComment(
                sprintf(
                    "No '%s' environment specific sources for '%s' component",
                    $environment,
                    $componentAlias
                )
            );
            return;
        }

        foreach ((array)$componentConfig['env'][$environment]['sources'] as $source) {
            try {
                $envSourceType = $componentConfig['type'] ?? null;
                $sourceData    = $this->parser->parse($source, $envSourceType);
                $component->execute($sourceData);
            } catch (ComponentException $e) {
                if ($ignoreMissingFiles === true) {
                    $this->log->logInfo("Skipping file {$source} as it could not be found.");
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Log the component header banner.
     */
    private function logHeader(string $componentAlias): void
    {
        $border = str_pad("----------------------", 22 + strlen($componentAlias), "-");
        $this->log->logComment("");
        $this->log->logComment($border);
        $this->log->logComment(sprintf("| Loading component %s |", $componentAlias));
        $this->log->logComment($border);
    }
}
