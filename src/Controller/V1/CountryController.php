<?php
declare(strict_types=1);

namespace App\Controller\V1;

use App\Dto\CreateCountryRequest;
use App\Dto\UpdateCountryRequest;
use App\Entity\Country;
use App\Entity\Embeddable\Currency;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('countries')]
class CountryController extends AbstractController
{
    public function __construct(
        private readonly CountryRepository $countryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/list', methods: ['GET'])]
    public function getCountries(): JsonResponse
    {
        $countries = $this->countryRepository->findAll();

        return $this->json($countries, Response::HTTP_OK, [], ['groups' => 'country:read']);
    }

    #[Route('/{uuid}', methods: ['GET'])]
    public function getCountry(string $uuid): JsonResponse
    {
        $country = $this->countryRepository->findOneBy(['uuid' => $uuid]);

        if (!$country) {
            throw new NotFoundHttpException('Country not found');
        }

        return $this->json($country, Response::HTTP_OK, [], ['groups' => 'country:read']);
    }

    #[Route('', methods: ['POST'])]
    public function addCountry(Request $request): JsonResponse
    {
        /** @var CreateCountryRequest $dto */
        $dto = $this->serializer->deserialize($request->getContent(), CreateCountryRequest::class, 'json');

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        if ($this->countryRepository->findOneBy(['uuid' => $dto->uuid])) {
            return $this->json(['error' => 'Country with this UUID already exists'], Response::HTTP_CONFLICT);
        }

        $country = new Country();
        $country->setUuid($dto->uuid);
        $country->setName($dto->name);
        $country->setRegion($dto->region);
        $country->setSubRegion($dto->subRegion);
        $country->setDemonym($dto->demonym);
        $country->setPopulation($dto->population);
        $country->setIndependent($dto->independent);
        $country->setFlag($dto->flag);

        $currency = new Currency();
        $currency->setCode($dto->currencyCode);
        $currency->setName($dto->currencyName);
        $currency->setSymbol($dto->currencySymbol);
        $country->setCurrency($currency);

        $this->entityManager->persist($country);
        $this->entityManager->flush();

        return $this->json($country, Response::HTTP_CREATED, [], ['groups' => 'country:read']);
    }

    #[Route('/{uuid}', methods: ['PATCH'])]
    public function updateCountry(string $uuid, Request $request): JsonResponse
    {
        $country = $this->countryRepository->findOneBy(['uuid' => $uuid]);

        if (!$country) {
            throw new NotFoundHttpException('Country not found');
        }

        /** @var UpdateCountryRequest $dto */
        $dto = $this->serializer->deserialize($request->getContent(), UpdateCountryRequest::class, 'json');

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        if ($dto->name !== null) {
            $country->setName($dto->name);
        }
        if ($dto->region !== null) {
            $country->setRegion($dto->region);
        }
        if ($dto->subRegion !== null) {
            $country->setSubRegion($dto->subRegion);
        }
        if ($dto->demonym !== null) {
            $country->setDemonym($dto->demonym);
        }
        if ($dto->population !== null) {
            $country->setPopulation($dto->population);
        }
        if ($dto->independent !== null) {
            $country->setIndependent($dto->independent);
        }
        if ($dto->flag !== null) {
            $country->setFlag($dto->flag);
        }

        // Handle currency updates
        $currency = $country->getCurrency() ?? new Currency();
        $currencyUpdated = false;

        if ($dto->currencyCode !== null) {
            $currency->setCode($dto->currencyCode);
            $currencyUpdated = true;
        }
        if ($dto->currencyName !== null) {
            $currency->setName($dto->currencyName);
            $currencyUpdated = true;
        }
        if ($dto->currencySymbol !== null) {
            $currency->setSymbol($dto->currencySymbol);
            $currencyUpdated = true;
        }

        if ($currencyUpdated) {
            $country->setCurrency($currency);
        }

        $this->entityManager->flush();

        return $this->json($country, Response::HTTP_OK, [], ['groups' => 'country:read']);
    }

    #[Route('/{uuid}', methods: ['DELETE'])]
    public function deleteCountry(string $uuid): JsonResponse
    {
        $country = $this->countryRepository->findOneBy(['uuid' => $uuid]);

        if (!$country) {
            throw new NotFoundHttpException('Country not found');
        }

        $this->entityManager->remove($country);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}