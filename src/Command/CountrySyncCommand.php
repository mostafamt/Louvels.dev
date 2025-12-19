<?php
declare(strict_types=1);

namespace App\Command;

use App\Repository\CountryRepository;
use App\Service\RestCountriesApiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'countries:sync',
    description: 'Synchronize countries from REST Countries API to database'
)]
class CountrySyncCommand extends Command
{
    public function __construct(
        private RestCountriesApiService $apiService,
        private CountryRepository $countryRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Syncing Countries from REST Countries API');

        // Step 1: Fetch countries from API using service
        try {
            $io->section('Fetching countries from API...');
            $apiCountries = $this->apiService->fetchAllCountries();
            $io->success('Fetched ' . count($apiCountries) . ' countries from API');
        } catch (\Exception $e) {
            $io->error('Failed to fetch countries from API: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Step 2: Get existing UUIDs before sync
        $existingUuids = $this->countryRepository->getAllUuids();
        $apiUuids = array_map(fn($c) => $c->getUuid(), $apiCountries);

        // Step 3: Sync countries (create/update)
        $io->section('Syncing countries to database...');

        $progressBar = $io->createProgressBar(count($apiCountries));
        $progressBar->start();

        $errors = 0;
        foreach ($apiCountries as $apiCountry) {
            try {
                $this->countryRepository->upsertCountry($apiCountry);
            } catch (\Exception $e) {
                $errors++;
                if ($output->isVerbose()) {
                    $io->warning('Failed to sync ' . $apiCountry->getUuid() . ': ' . $e->getMessage());
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Calculate statistics
        $created = count(array_diff($apiUuids, $existingUuids));
        $updated = count(array_intersect($apiUuids, $existingUuids));

        // Step 4: Delete obsolete countries
        $io->section('Removing obsolete countries...');

        try {
            $deleted = $this->countryRepository->deleteCountriesNotInList($apiUuids);

            if ($deleted > 0) {
                $io->success("Deleted $deleted obsolete countries");
            } else {
                $io->note('No obsolete countries to delete');
            }
        } catch (\Exception $e) {
            $io->error('Failed to delete obsolete countries: ' . $e->getMessage());
            $deleted = 0;
        }

        // Step 5: Display summary
        $io->section('Sync Summary');

        $summary = [
            ["Fetched from API", count($apiCountries)],
            ["Countries created", $created],
            ["Countries updated", $updated],
            ["Countries deleted", $deleted],
        ];

        if ($errors > 0) {
            $summary[] = ["Errors", $errors];
        }

        $io->table(['Metric', 'Count'], $summary);

        if ($errors > 0) {
            $io->warning("Sync completed with $errors errors");
            return Command::FAILURE;
        }

        $io->success('Sync completed successfully!');
        return Command::SUCCESS;
    }
}
