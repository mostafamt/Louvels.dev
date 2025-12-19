<?php
declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCountryRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Length(exactly: 10)]
    public string $uuid;

    #[Assert\Length(max: 100)]
    public ?string $region = null;

    #[Assert\Length(max: 100)]
    public ?string $subRegion = null;

    #[Assert\Length(max: 100)]
    public ?string $demonym = null;

    #[Assert\PositiveOrZero]
    public ?int $population = null;

    #[Assert\NotNull]
    public bool $independent;

    #[Assert\Length(max: 500)]
    public ?string $flag = null;

    #[Assert\Length(exactly: 3)]
    public ?string $currencyCode = null;

    #[Assert\NotBlank]
    public ?string $currencyName = null;

    #[Assert\NotBlank]
    public ?string $currencySymbol = null;
}
