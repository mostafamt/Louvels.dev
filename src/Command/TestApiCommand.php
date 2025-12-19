<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\RestCountriesApiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'test:api',
    description: 'Test REST Countries API integration'
)]
class TestApiCommand extends Command
{
    public function __construct(
        private RestCountriesApiService $apiService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->title('Testing REST Countries API');

            $countries = $this->apiService->fetchAllCountries();

            $io->success('Fetched ' . count($countries) . ' countries');

            // Show first 5 countries as samples
            $io->section('Sample countries:');

            $table = [];
            foreach (array_slice($countries, 0, 5) as $country) {
                $currencyName = $country->getCurrency()?->getName() ?? 'N/A';
                $currencySymbol = $country->getCurrency()?->getSymbol() ?? '';
                $currency = $currencySymbol ? "$currencyName ($currencySymbol)" : $currencyName;

                $table[] = [
                    $country->getUuid(),
                    $country->getName(),
                    $country->getRegion() ?? 'N/A',
                    $country->getSubRegion() ?? 'N/A',
                    number_format($country->getPopulation() ?? 0),
                    $currency,
                    $country->getIndependent() ? 'Yes' : 'No'
                ];
            }

            $io->table(
                ['UUID', 'Name', 'Region', 'Sub-Region', 'Population', 'Currency', 'Independent'],
                $table
            );

            // Show statistics
            $io->section('Statistics:');

            $withCurrency = 0;
            $withoutCurrency = 0;
            $independent = 0;
            $dependent = 0;

            foreach ($countries as $country) {
                if ($country->getCurrency()?->getName()) {
                    $withCurrency++;
                } else {
                    $withoutCurrency++;
                }

                if ($country->getIndependent()) {
                    $independent++;
                } else {
                    $dependent++;
                }
            }

            $io->listing([
                "Total countries: " . count($countries),
                "With currency: $withCurrency",
                "Without currency: $withoutCurrency",
                "Independent: $independent",
                "Dependent/territories: $dependent"
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to fetch countries: ' . $e->getMessage());
            $io->note('Exception: ' . get_class($e));

            if ($output->isVerbose()) {
                $io->block($e->getTraceAsString(), null, 'fg=red');
            }

            return Command::FAILURE;
        }
    }
}
