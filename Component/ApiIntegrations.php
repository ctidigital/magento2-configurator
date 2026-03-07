<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Model\AuthorizationService;
use Magento\Integration\Model\IntegrationFactory;
use Magento\Integration\Model\Oauth\TokenFactory;

class ApiIntegrations implements ComponentInterface
{
    protected string $alias = 'apiintegrations';
    protected string $name = 'Api Integrations';
    protected string $description = 'Component to create Api Integrations';

    public function __construct(
        protected readonly IntegrationFactory $integrationFactory,
        protected readonly IntegrationServiceInterface $integrationService,
        protected readonly AuthorizationService $authorizationService,
        protected readonly TokenFactory $tokenFactory,
        protected readonly LoggerInterface $log
    ) {
    }

    public function execute(mixed $data = null): void
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
                } catch (ComponentException $exception) {
                    $this->log->logError($exception->getMessage());
                }
            }
        }
    }

    private function createApiIntegration(array $integrationData): void
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
     * Prepare data for integrationFactory creation.
     */
    private function convertToUseableData(array $integrationData): array
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
     * Set permissions for API Integration.
     *
     * @throws LocalizedException
     */
    private function setPermissions(mixed $integrationId, ?array $resources): void
    {
        $authorizationService = $this->authorizationService;
        $authorizationService->grantPermissions($integrationId, $resources);
    }

    /**
     * Activate and Authorize the Integration.
     */
    private function activateAndAuthorize(mixed $consumerId): void
    {
        $token = $this->tokenFactory->create();
        $token->createVerifierToken($consumerId);
        $token->setType('access');
        $token->save();
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
