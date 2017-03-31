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
use CtiDigital\Configurator\Model\Component\ApiIntegrations;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use Magento\Authorization\Model\ResourceModel\Rules\CollectionFactory as RulesCollectionFactory;

class ApiIntegrationsTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
//        $aclBuilder = $this->getMock(Builder::class, [], [], '', false);
//        $roleFactory = $this->getMock(RoleFactory::class, ['create'], [], '', false);
//        $roleCollectionMock = $this->getMock(RoleCollectionFactory::class, ['create'], [], '', false);
//        $rulesFactory = $this->getMock(RulesFactory::class, ['create'], [], '', false);
//        $rulesCollectionMock = $this->getMock(RulesCollectionFactory::class, [], [], '', false);
//        $rootAclResource = $this->getMock(RootResource::class, ['getId'], [], '', false);
//        $authorizationService = new AuthorizationService(
//            $aclBuilder,
//            $roleFactory,
//            $roleCollectionMock,
//            $rulesFactory,
//            $rulesCollectionMock,
//            $this->logInterface,
//            $rootAclResource
//        );

        $authorizationService = $this->getMock(AuthorizationService::class, [], [], '', false);
        $integrationFactory = $this->getMock(IntegrationFactory::class);
        $integrationService = $this->getMock(IntegrationServiceInterface::class);
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
