<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Country;
// use Doctrine\Bundle\Doctrine\Repository\ServiceEntityRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    /**
     * Find a country by its UUID.
     */
    public function findByUuid(string $uuid): ?Country
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    /**
     * Create or update a country entity.
     * Uses merge to handle both insert and update operations.
     */
    public function upsertCountry(Country $country): void
    {
        $entityManager = $this->getEntityManager();

        // Check if country with this UUID already exists
        $existingCountry = $this->findByUuid($country->getUuid());

        if ($existingCountry) {
            // Update existing country with new data
            $existingCountry->setName($country->getName());
            $existingCountry->setRegion($country->getRegion());
            $existingCountry->setSubRegion($country->getSubRegion());
            $existingCountry->setDemonym($country->getDemonym());
            $existingCountry->setPopulation($country->getPopulation());
            $existingCountry->setIndependent($country->getIndependent());
            $existingCountry->setFlag($country->getFlag());
            $existingCountry->setCurrency($country->getCurrency());
        } else {
            // Persist new country
            $entityManager->persist($country);
        }

        $entityManager->flush();
    }

    /**
     * Delete all countries whose UUIDs are not in the provided list.
     * Used during sync to remove countries that no longer exist in the external API.
     *
     * @param array<string> $uuids List of UUIDs to keep
     */
    public function deleteCountriesNotInList(array $uuids): int
    {
        if (empty($uuids)) {
            // If no UUIDs provided, don't delete anything to be safe
            return 0;
        }

        $queryBuilder = $this->createQueryBuilder('c')
            ->delete()
            ->where('c.uuid NOT IN (:uuids)')
            ->setParameter('uuids', $uuids);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * Get all country UUIDs from the database.
     *
     * @return array<string>
     */
    public function getAllUuids(): array
    {
        $results = $this->createQueryBuilder('c')
            ->select('c.uuid')
            ->getQuery()
            ->getResult();

        return array_column($results, 'uuid');
    }

    /**
     * Save a country entity (shorthand for persist + flush).
     */
    public function save(Country $country): void
    {
        $this->getEntityManager()->persist($country);
        $this->getEntityManager()->flush();
    }

    /**
     * Remove a country entity.
     */
    public function remove(Country $country): void
    {
        $this->getEntityManager()->remove($country);
        $this->getEntityManager()->flush();
    }
}
