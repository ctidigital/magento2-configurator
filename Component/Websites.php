<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Websites\WebsitesProcessor;

class Websites implements ComponentInterface
{
    protected string $alias = 'websites';
    protected string $name = 'Websites';
    protected string $description = 'Component to manage Websites, Stores and Store Views';

    public function __construct(
        private readonly WebsitesProcessor $processor,
        private readonly LoggerInterface $log
    ) {}

    public function execute(mixed $data = null): void
    {
        $this->processor->setData($data ?? [])->process();
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
