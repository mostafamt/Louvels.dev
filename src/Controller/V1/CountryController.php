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
use OpenApi\Attributes as OA;

#[Route('/countries')]
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
    #[OA\Get(
        path: '/api/v1/countries/list',
        summary: 'Get all countries',
        description: 'Retrieves a list of all countries from the database',
        tags: ['Countries']
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response with list of countries',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'uuid', type: 'string', example: 'USA'),
                    new OA\Property(property: 'name', type: 'string', example: 'United States'),
                    new OA\Property(property: 'region', type: 'string', example: 'Americas'),
                    new OA\Property(property: 'subRegion', type: 'string', example: 'North America'),
                    new OA\Property(property: 'demonym', type: 'string', example: 'American'),
                    new OA\Property(property: 'population', type: 'integer', example: 340110988),
                    new OA\Property(property: 'independent', type: 'boolean', example: true),
                    new OA\Property(property: 'flag', type: 'string', example: '🇺🇸'),
                    new OA\Property(
                        property: 'currency',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'code', type: 'string', example: 'USD'),
                            new OA\Property(property: 'name', type: 'string', example: 'United States dollar'),
                            new OA\Property(property: 'symbol', type: 'string', example: '$')
                        ]
                    )
                ]
            )
        )
    )]
    public function getCountries(): JsonResponse
    {
        $countries = $this->countryRepository->findAll();

        return $this->json($countries, Response::HTTP_OK, [], ['groups' => 'country:read']);
    }

    #[Route('/{uuid}', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/countries/{uuid}',
        summary: 'Get a single country',
        description: 'Retrieves a single country by its UUID (3-letter country code)',
        tags: ['Countries']
    )]
    #[OA\Parameter(
        name: 'uuid',
        description: 'Country UUID (3-letter code, e.g., USA, GBR, FRA)',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'USA')
    )]
    #[OA\Response(
        response: 200,
        description: 'Successful response with country data',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'uuid', type: 'string', example: 'USA'),
                new OA\Property(property: 'name', type: 'string', example: 'United States'),
                new OA\Property(property: 'region', type: 'string', example: 'Americas'),
                new OA\Property(property: 'subRegion', type: 'string', example: 'North America'),
                new OA\Property(property: 'demonym', type: 'string', example: 'American'),
                new OA\Property(property: 'population', type: 'integer', example: 340110988),
                new OA\Property(property: 'independent', type: 'boolean', example: true),
                new OA\Property(property: 'flag', type: 'string', example: '🇺🇸'),
                new OA\Property(
                    property: 'currency',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'code', type: 'string', example: 'USD'),
                        new OA\Property(property: 'name', type: 'string', example: 'United States dollar'),
                        new OA\Property(property: 'symbol', type: 'string', example: '$')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Country not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Country not found')
            ]
        )
    )]
    public function getCountry(string $uuid): JsonResponse
    {
        $country = $this->countryRepository->findOneBy(['uuid' => $uuid]);

        if (!$country) {
            throw new NotFoundHttpException('Country not found');
        }

        return $this->json($country, Response::HTTP_OK, [], ['groups' => 'country:read']);
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/countries',
        summary: 'Create a new country',
        description: 'Creates a new country with the provided data. Requires authentication.',
        security: [['basicAuth' => []]],
        tags: ['Countries']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['uuid', 'name'],
            properties: [
                new OA\Property(property: 'uuid', type: 'string', maxLength: 10, example: 'TESTCOUNT1'),
                new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Test Country'),
                new OA\Property(property: 'region', type: 'string', maxLength: 100, example: 'Europe', nullable: true),
                new OA\Property(property: 'subRegion', type: 'string', maxLength: 100, example: 'Western Europe', nullable: true),
                new OA\Property(property: 'demonym', type: 'string', maxLength: 100, example: 'Test', nullable: true),
                new OA\Property(property: 'population', type: 'integer', example: 1000000, nullable: true),
                new OA\Property(property: 'independent', type: 'boolean', example: true, nullable: true),
                new OA\Property(property: 'flag', type: 'string', maxLength: 500, example: '🏴', nullable: true),
                new OA\Property(property: 'currencyCode', type: 'string', maxLength: 3, example: 'EUR', nullable: true),
                new OA\Property(property: 'currencyName', type: 'string', maxLength: 255, example: 'Euro', nullable: true),
                new OA\Property(property: 'currencySymbol', type: 'string', maxLength: 10, example: '€', nullable: true)
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Country created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'uuid', type: 'string', example: 'TESTCOUNT1'),
                new OA\Property(property: 'name', type: 'string', example: 'Test Country'),
                new OA\Property(property: 'region', type: 'string', example: 'Europe'),
                new OA\Property(property: 'subRegion', type: 'string', example: 'Western Europe'),
                new OA\Property(property: 'demonym', type: 'string', example: 'Test'),
                new OA\Property(property: 'population', type: 'integer', example: 1000000),
                new OA\Property(property: 'independent', type: 'boolean', example: true),
                new OA\Property(property: 'flag', type: 'string', example: '🏴'),
                new OA\Property(
                    property: 'currency',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'code', type: 'string', example: 'EUR'),
                        new OA\Property(property: 'name', type: 'string', example: 'Euro'),
                        new OA\Property(property: 'symbol', type: 'string', example: '€')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'type', type: 'string'),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'violations', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Authentication required'
    )]
    #[OA\Response(
        response: 409,
        description: 'Country with this UUID already exists',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Country with this UUID already exists')
            ]
        )
    )]
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
    #[OA\Patch(
        path: '/api/v1/countries/{uuid}',
        summary: 'Update a country',
        description: 'Partially updates a country. Only provided fields will be updated. Requires authentication.',
        security: [['basicAuth' => []]],
        tags: ['Countries']
    )]
    #[OA\Parameter(
        name: 'uuid',
        description: 'Country UUID to update',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'USA')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Updated Name', nullable: true),
                new OA\Property(property: 'region', type: 'string', maxLength: 100, example: 'Americas', nullable: true),
                new OA\Property(property: 'subRegion', type: 'string', maxLength: 100, example: 'North America', nullable: true),
                new OA\Property(property: 'demonym', type: 'string', maxLength: 100, example: 'American', nullable: true),
                new OA\Property(property: 'population', type: 'integer', example: 350000000, nullable: true),
                new OA\Property(property: 'independent', type: 'boolean', example: true, nullable: true),
                new OA\Property(property: 'flag', type: 'string', maxLength: 500, example: '🇺🇸', nullable: true),
                new OA\Property(property: 'currencyCode', type: 'string', maxLength: 3, example: 'USD', nullable: true),
                new OA\Property(property: 'currencyName', type: 'string', maxLength: 255, example: 'US Dollar', nullable: true),
                new OA\Property(property: 'currencySymbol', type: 'string', maxLength: 10, example: '$', nullable: true)
            ],
            example: [
                'population' => 350000000,
                'independent' => true
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Country updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'uuid', type: 'string', example: 'USA'),
                new OA\Property(property: 'name', type: 'string', example: 'United States'),
                new OA\Property(property: 'region', type: 'string', example: 'Americas'),
                new OA\Property(property: 'subRegion', type: 'string', example: 'North America'),
                new OA\Property(property: 'demonym', type: 'string', example: 'American'),
                new OA\Property(property: 'population', type: 'integer', example: 350000000),
                new OA\Property(property: 'independent', type: 'boolean', example: true),
                new OA\Property(property: 'flag', type: 'string', example: '🇺🇸'),
                new OA\Property(
                    property: 'currency',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'code', type: 'string', example: 'USD'),
                        new OA\Property(property: 'name', type: 'string', example: 'US Dollar'),
                        new OA\Property(property: 'symbol', type: 'string', example: '$')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error'
    )]
    #[OA\Response(
        response: 401,
        description: 'Authentication required'
    )]
    #[OA\Response(
        response: 404,
        description: 'Country not found'
    )]
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
    #[OA\Delete(
        path: '/api/v1/countries/{uuid}',
        summary: 'Delete a country',
        description: 'Deletes a country by its UUID. Requires authentication.',
        security: [['basicAuth' => []]],
        tags: ['Countries']
    )]
    #[OA\Parameter(
        name: 'uuid',
        description: 'Country UUID to delete',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string', example: 'TESTCOUNT1')
    )]
    #[OA\Response(
        response: 204,
        description: 'Country deleted successfully (no content)'
    )]
    #[OA\Response(
        response: 401,
        description: 'Authentication required'
    )]
    #[OA\Response(
        response: 404,
        description: 'Country not found'
    )]
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