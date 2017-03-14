<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\ApiIntegrations;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Integration\Model\AuthorizationService;
use Magento\Integration\Api\IntegrationServiceInterface;

class ApiIntegrationsTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $integrationFactory = $this->getMock(IntegrationFactory::class);
        $integrationService = $this->getMock(IntegrationServiceInterface::class);
        $authorizationService = $this->getMock(AuthorizationService::class);
        $tokenFactory = $this->getMock(TokenFactory::class);

        $this->component = new ApiIntegrations(
            $this->logInterface,
            $this->objectManager,
            $integrationFactory,
            $integrationService,
            $authorizationService,
            $tokenFactory
        );
        $this->className = ApiIntegrations::class;
    }
}
