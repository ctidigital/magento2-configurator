<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

class Rewrite
{
    private string $requestPath;
    private string $targetPath;
    private string $redirectType;
    private string $storeId;
    private string $description;

    /**
     * ExpectedRewrite constructor.
     */
    public function __construct(
        string $requestPath,
        string $targetPath,
        string $redirectType,
        string $storeId,
        string $description
    ) {
        $this->requestPath = $requestPath;
        $this->targetPath = $targetPath;
        $this->redirectType = $redirectType;
        $this->storeId = $storeId;
        $this->description = $description;
    }

    public function getRequestPath(): string
    {
        return $this->requestPath;
    }

    public function setRequestPath(string $requestPath): void
    {
        $this->requestPath = $requestPath;
    }

    public function getTargetPath(): string
    {
        return $this->targetPath;
    }

    public function setTargetPath(string $targetPath): void
    {
        $this->targetPath = $targetPath;
    }

    public function getRedirectType(): string
    {
        return $this->redirectType;
    }

    public function setRedirectType(string $redirectType): void
    {
        $this->redirectType = $redirectType;
    }

    public function getStoreId(): string
    {
        return $this->storeId;
    }

    public function setStoreId(string $storeId): void
    {
        $this->storeId = $storeId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}
