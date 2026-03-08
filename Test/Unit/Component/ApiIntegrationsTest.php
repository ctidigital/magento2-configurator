<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\ApiIntegrations;
use Magento\Integration\Api\Data\IntegrationInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Model\AuthorizationService;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Component\ApiIntegrations
 */
class ApiIntegrationsTest extends TestCase
{
    private ApiIntegrations $component;

    /** @var IntegrationServiceInterface&MockObject */
    private IntegrationServiceInterface $integrationService;

    /** @var AuthorizationService&MockObject */
    private AuthorizationService $authorizationService;

    /** @var TokenFactory&MockObject */
    private TokenFactory $tokenFactory;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    protected function setUp(): void
    {
        $this->integrationService   = $this->createMock(IntegrationServiceInterface::class);
        $this->authorizationService = $this->createMock(AuthorizationService::class);
        $this->tokenFactory         = $this->createMock(TokenFactory::class);
        $this->log                  = $this->createMock(LoggerInterface::class);

        $this->component = new ApiIntegrations(
            $this->integrationService,
            $this->authorizationService,
            $this->tokenFactory,
            $this->log
        );
    }

    // ── Alias ─────────────────────────────────────────────────────────────────

    public function testGetAliasReturnsApiintegrations(): void
    {
        $this->assertSame('apiintegrations', $this->component->getAlias());
    }

    // ── Guard clauses ─────────────────────────────────────────────────────────

    public function testExecuteIsNoOpWhenApiIntegrationsKeyAbsent(): void
    {
        $this->integrationService->expects($this->never())->method('findByName');

        $this->component->execute(['other' => 'data']);
    }

    public function testExecuteLogsErrorWhenNameNotSet(): void
    {
        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('Name'));

        $this->integrationService->expects($this->never())->method('findByName');

        $this->component->execute([
            'apiintegrations' => [
                [/* no name */],
            ],
        ]);
    }

    // ── Existing integration ──────────────────────────────────────────────────

    public function testExecuteSkipsWhenIntegrationAlreadyExists(): void
    {
        $existing = $this->createMock(IntegrationInterface::class);
        $existing->method('getId')->willReturn(42);
        $existing->method('getName')->willReturn('My API');

        $this->integrationService->method('findByName')->willReturn($existing);

        $this->log->expects($this->once())
            ->method('logComment')
            ->with($this->stringContains('already exists'));

        $this->integrationService->expects($this->never())->method('create');
        $this->authorizationService->expects($this->never())->method('grantPermissions');

        $this->component->execute([
            'apiintegrations' => [
                [
                    'name'        => 'My API',
                    'email'       => 'api@example.com',
                    'callbackurl' => 'https://example.com/cb',
                    'identityurl' => 'https://example.com/id',
                    'resources'   => ['Magento_Catalog::catalog'],
                ],
            ],
        ]);
    }

    // ── New integration ───────────────────────────────────────────────────────

    public function testExecuteCreatesIntegrationWithPermissionsWhenNew(): void
    {
        $nonExisting = $this->createMock(IntegrationInterface::class);
        $nonExisting->method('getId')->willReturn(null);

        $newIntegration = $this->createMock(IntegrationInterface::class);
        $newIntegration->method('getId')->willReturn(7);
        $newIntegration->method('getConsumerId')->willReturn(99);

        $this->integrationService->method('findByName')->willReturn($nonExisting);
        $this->integrationService->expects($this->once())
            ->method('create')
            ->willReturn($newIntegration);

        $resources = ['Magento_Catalog::catalog'];
        $this->authorizationService->expects($this->once())
            ->method('grantPermissions')
            ->with(7, $resources);

        // Token::setType() is a __call magic setter on the real class; addMethods() makes it mockable.
        // onlyMethods() lists the real declared methods we want to intercept.
        // disableOriginalConstructor() is required because the real Token constructor has required args.
        $mockToken = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createVerifierToken', 'save'])
            ->addMethods(['setType'])
            ->getMock();
        $mockToken->expects($this->once())->method('createVerifierToken')->with(99)->willReturnSelf();
        $mockToken->expects($this->once())->method('setType')->with('access')->willReturnSelf();
        $mockToken->expects($this->once())->method('save')->willReturnSelf();

        $this->tokenFactory->method('create')->willReturn($mockToken);

        $this->component->execute([
            'apiintegrations' => [
                [
                    'name'        => 'New API',
                    'email'       => 'api@example.com',
                    'callbackurl' => 'https://example.com/cb',
                    'identityurl' => 'https://example.com/id',
                    'resources'   => $resources,
                ],
            ],
        ]);
    }
}
