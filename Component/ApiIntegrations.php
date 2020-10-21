<?php
namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\Oauth\TokenFactory;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Integration\Model\AuthorizationService;
use Magento\Integration\Api\IntegrationServiceInterface;
use CtiDigital\Configurator\Exception\ComponentException;

/**
 * Class ApiIntegrations
 * @package CtiDigital\Configurator\Component
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class ApiIntegrations implements ComponentInterface
{
    protected $alias = 'apiintegrations';
    protected $name = 'Api Integrations';
    protected $description = 'Component to create Api Integrations';

    /**
     * @var  IntegrationServiceInterface
     */
    protected $integrationService;

    /**
     * @var IntegrationFactory
     */
    protected $integrationFactory;

    /**
     * @var AuthorizationService
     */
    protected $authorizationService;

    /**
     * @var TokenFactory
     */
    protected $tokenFactory;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * ApiIntegrations constructor.
     * @param IntegrationFactory $integrationFactory
     * @param IntegrationServiceInterface $integrationService
     * @param AuthorizationService $authorizationService
     * @param TokenFactory $tokenFactory
     * @param LoggerInterface $log
     */
    public function __construct(
        IntegrationFactory $integrationFactory,
        IntegrationServiceInterface $integrationService,
        AuthorizationService $authorizationService,
        TokenFactory $tokenFactory,
        LoggerInterface $log
    ) {
        $this->integrationFactory = $integrationFactory;
        $this->integrationService = $integrationService;
        $this->authorizationService = $authorizationService;
        $this->tokenFactory = $tokenFactory;
        $this->log = $log;
    }

    /**
     * @param array $data
     */
    public function execute($data = null)
    {
        if (isset($data['apiintegrations'])) {
            foreach ($data['apiintegrations'] as $integrationData) {
                try {
                    if (!isset($integrationData['name'])) {
                        $this->log->logError(
                            sprintf('Api Integration requires a Name to be set')
                        );
                        continue;
                    }

                    $this->createApiIntegration($integrationData);
                } catch (ComponentException $e) {
                    $this->log->logError($e->getMessage());
                }
            }
        }
    }

    /**
     * @param array $integrationData
     */
    private function createApiIntegration(array $integrationData)
    {
        $integration = $this->integrationFactory->create();
        $integrationCount = $integration->getCollection()
            ->addFieldToFilter('name', $integrationData['name'])
            ->getSize();

        if ($integrationCount > 0) {
            $integration = $integration
                ->getCollection()
                ->addFieldToFilter('name', $integrationData['name'])
                ->getFirstItem();

            $this->log->logComment(
                sprintf('API Integration "%s" already exists: Creation skipped', $integration->getName())
            );

            return;
        }

        $integrationDataArray = $this->convertToUseableData($integrationData);
        $integration = $this->integrationService->create($integrationDataArray);
        $integrationId = $integration->getId();

        $this->log->logInfo(
            sprintf('API Integration "%s" created', $integrationData['name'])
        );

        $this->setPermissions($integrationId, $integrationData['resources']);
        $this->activateAndAuthorize($integration->getConsumerId());

        $this->log->logInfo(
            sprintf('API Integration "%s" permissions and authorisation set.', $integrationData['name'])
        );
    }

    /**
     * Prepare data for integrationFactory creation
     *
     * @param array $integrationData
     * @return array
     */
    private function convertToUseableData(array $integrationData)
    {
        $data = [
            'name' => $integrationData['name'],
            'email' => $integrationData['email'],
            'status' => '1',
            'endpoint' => $integrationData['callbackurl'],
            'identity_link_url' => $integrationData['identityurl'],
            'setup_type' => 0
        ];

        return $data;
    }

    /**
     * Set permissions for API Integration
     *
     * @param $integrationId
     * @param array $resources
     */
    private function setPermissions($integrationId, array $resources = null)
    {
        $authorizationService = $this->authorizationService;
        $authorizationService->grantPermissions($integrationId, $resources);
    }

    /**
     * Activate and Authorize the Integration
     *
     * @param $consumerId
     */
    private function activateAndAuthorize($consumerId)
    {
        $token = $this->tokenFactory->create();
        $token->createVerifierToken($consumerId);
        $token->setType('access');
        $token->save();
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
