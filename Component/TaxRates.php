<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\FileComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Tax\Api\Data\TaxRateInterfaceFactory;
use Magento\Tax\Api\TaxRateRepositoryInterface;

class TaxRates implements FileComponentInterface
{
    protected string $alias = 'taxrates';
    protected string $name = 'Tax Rates';
    protected string $description = 'Component to create Tax Rates';

    public function __construct(
        private readonly TaxRateRepositoryInterface $taxRateRepository,
        private readonly TaxRateInterfaceFactory $taxRateFactory,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly RegionFactory $regionFactory,
        private readonly LoggerInterface $log
    ) {}

    /**
     * Process tax rate data from CSV rows.
     *
     * The first row must contain column headers. Supported columns:
     *   code, tax_country_id, tax_region_id, tax_postcode, rate,
     *   zip_is_range, zip_from, zip_to
     *
     * Rates are created when they do not already exist; rates matched by code
     * are skipped so repeated runs are idempotent.
     */
    public function execute(mixed $data = null): void
    {
        if (empty($data) || count($data) < 2) {
            $this->log->logError('Tax rates: no data found.');
            return;
        }

        $headers = array_values($data[0]);
        unset($data[0]);

        $created = 0;
        $skipped = 0;

        foreach ($data as $row) {
            $rate = array_combine($headers, array_values($row));
            $code = (string) ($rate['code'] ?? '');

            if ($code === '') {
                $this->log->logError('Tax rate skipped: missing code.');
                continue;
            }

            try {
                if ($this->rateExists($code)) {
                    $this->log->logComment(
                        sprintf('Tax rate "%s" already exists, skipping.', $code),
                        1
                    );
                    $skipped++;
                    continue;
                }

                $this->saveRate($rate);
                $this->log->logInfo(sprintf('Tax rate "%s" created.', $code), 1);
                $created++;
            } catch (\Exception $e) {
                $this->log->logError(
                    sprintf('Tax rate "%s" failed: %s', $code, $e->getMessage())
                );
            }
        }

        $this->log->logInfo(
            sprintf('Tax rates import complete: %d created, %d skipped.', $created, $skipped)
        );
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Return true if a tax rate with the given code already exists.
     */
    private function rateExists(string $code): bool
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('code', $code)
            ->create();

        return $this->taxRateRepository->getList($criteria)->getTotalCount() > 0;
    }

    /**
     * Build and persist a single tax rate from an associative row array.
     */
    private function saveRate(array $rate): void
    {
        $taxRate = $this->taxRateFactory->create();
        $taxRate->setCode((string) $rate['code']);
        $taxRate->setTaxCountryId((string) $rate['tax_country_id']);
        $taxRate->setTaxPostcode((string) ($rate['tax_postcode'] ?: '*'));
        $taxRate->setRate((float) $rate['rate']);
        $taxRate->setTaxRegionId(
            $this->resolveRegionId(
                (string) ($rate['tax_region_id'] ?? ''),
                (string) $rate['tax_country_id']
            )
        );

        $zipIsRange = isset($rate['zip_is_range'])
            && $rate['zip_is_range'] !== ''
            && $rate['zip_is_range'] !== '0';

        if ($zipIsRange) {
            $taxRate->setZipIsRange(1);
            $taxRate->setZipFrom((int) ($rate['zip_from'] ?? 0));
            $taxRate->setZipTo((int) ($rate['zip_to'] ?? 0));
        }

        $this->taxRateRepository->save($taxRate);
    }

    /**
     * Resolve a region code (e.g. "CA") or wildcard ("*", "0", "") to its
     * integer region_id. Returns 0 to indicate "all regions".
     */
    private function resolveRegionId(string $regionCode, string $countryId): int
    {
        if ($regionCode === '' || $regionCode === '*' || $regionCode === '0') {
            return 0;
        }

        $region = $this->regionFactory->create()->loadByCode($regionCode, $countryId);
        return (int) $region->getId();
    }
}
