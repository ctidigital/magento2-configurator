<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use Magento\Framework\Acl\Builder;
use Magento\Framework\Acl\RootResource;
use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Integration\Model\AuthorizationService;
use Magento\Integration\Api\IntegrationServiceInterface;
use CtiDigital\Configurator\Component\ApiIntegrations;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use Magento\Authorization\Model\ResourceModel\Rules\CollectionFactory as RulesCollectionFactory;

class ApiIntegrationsTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $authorizationService = $this->getMockBuilder(AuthorizationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $integrationFactory = $this->getMockBuilder(IntegrationFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $integrationService = $this->getMockBuilder(IntegrationServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $tokenFactory = $this->getMockBuilder(TokenFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->component = new ApiIntegrations(
            $this->logInterface,
            $this->objectManager,
            $this->json,
            $integrationFactory,
            $integrationService,
            $authorizationService,
            $tokenFactory
        );
        $this->className = ApiIntegrations::class;
    }
}
