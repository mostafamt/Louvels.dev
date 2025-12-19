<?php
declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateCountryRequest
{
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\Length(max: 100)]
    public ?string $region = null;

    #[Assert\Length(max: 100)]
    public ?string $subRegion = null;

    #[Assert\Length(max: 100)]
    public ?string $demonym = null;

    #[Assert\PositiveOrZero]
    public ?int $population = null;

    public ?bool $independent = null;

    #[Assert\Length(max: 500)]
    public ?string $flag = null;

    #[Assert\Length(exactly: 3)]
    public ?string $currencyCode = null;

    public ?string $currencyName = null;

    public ?string $currencySymbol = null;
}
