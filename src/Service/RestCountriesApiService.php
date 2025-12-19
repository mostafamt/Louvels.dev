<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Country;
use App\Entity\Embeddable\Currency;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;

class RestCountriesApiService
{
    private const API_URL = 'https://restcountries.com/v3.1/all?fields=name,cca3,region,subregion,population,independent,currencies,demonyms,flag,flags';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Fetch all countries from REST Countries API.
     *
     * @return Country[] Array of Country entities
     * @throws \RuntimeException If API request fails
     */
    public function fetchAllCountries(): array
    {
        try {
            $this->logger->info('Fetching countries from REST Countries API');

            $response = $this->httpClient->request('GET', self::API_URL);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException(
                    'REST Countries API returned status ' . $response->getStatusCode()
                );
            }

            $data = $response->toArray();
            $countries = [];

            foreach ($data as $countryData) {
                try {
                    $countries[] = $this->mapToEntity($countryData);
                } catch (\Exception $e) {
                    // Log and skip invalid country data
                    $this->logger->warning('Skipping invalid country data: ' . $e->getMessage(), [
                        'country' => $countryData['name']['common'] ?? 'unknown'
                    ]);
                }
            }

            $this->logger->info('Successfully fetched ' . count($countries) . ' countries');

            return $countries;

        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Network error fetching countries: ' . $e->getMessage());
            throw new \RuntimeException('Failed to connect to REST Countries API', 0, $e);

        } catch (DecodingExceptionInterface $e) {
            $this->logger->error('JSON decode error: ' . $e->getMessage());
            throw new \RuntimeException('Invalid JSON response from REST Countries API', 0, $e);

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error: ' . $e->getMessage());
            throw new \RuntimeException('Failed to fetch countries', 0, $e);
        }
    }

    /**
     * Map REST Countries API data to Country entity.
     *
     * @param array $data Single country data from API
     * @return Country Country entity
     */
    private function mapToEntity(array $data): Country
    {
        $country = new Country();

        // UUID - prefer cca3 (3-letter code), fallback to cca2
        $uuid = $data['cca3'] ?? $data['cca2'] ?? null;
        if (!$uuid) {
            throw new \InvalidArgumentException('Country data missing country code');
        }
        $country->setUuid($uuid);

        // Name - common name
        $name = $data['name']['common'] ?? null;
        if (!$name) {
            throw new \InvalidArgumentException('Country data missing name');
        }
        $country->setName($name);

        // Region
        $country->setRegion($data['region'] ?? null);

        // SubRegion - note: API uses 'subregion' (lowercase 'r')
        $country->setSubRegion($data['subregion'] ?? null);

        // Demonym - English masculine form
        $country->setDemonym($data['demonyms']['eng']['m'] ?? null);

        // Population
        $country->setPopulation($data['population'] ?? null);

        // Independent
        $country->setIndependent($data['independent'] ?? null);

        // Flag - prefer emoji flag, fallback to PNG URL
        $country->setFlag($data['flag'] ?? $data['flags']['png'] ?? null);

        // Currency - extract first currency
        $currency = $this->extractCurrency($data);
        $country->setCurrency($currency);

        return $country;
    }

    /**
     * Extract currency information from API data.
     * Returns the first currency found, or an empty Currency object if none exist.
     *
     * @param array $data Country data from API
     * @return Currency Currency embeddable object
     */
    private function extractCurrency(array $data): Currency
    {
        $currency = new Currency();

        if (!isset($data['currencies']) || !is_array($data['currencies'])) {
            return $currency; // Return empty currency
        }

        // Get first currency code (the array key) and its data
        $currencyCode = array_key_first($data['currencies']);
        $currencyData = $data['currencies'][$currencyCode] ?? null;

        if ($currencyCode && $currencyData && is_array($currencyData)) {
            $currency->setCode($currencyCode);
            $currency->setName($currencyData['name'] ?? null);
            $currency->setSymbol($currencyData['symbol'] ?? null);
        }

        return $currency;
    }
}
