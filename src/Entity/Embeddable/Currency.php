<?php
declare(strict_types=1);

namespace App\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Embeddable]
class Currency
{
    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    #[Groups(['country:read'])]
    private ?string $code = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['country:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Groups(['country:read'])]
    private ?string $symbol = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(?string $symbol): self
    {
        $this->symbol = $symbol;
        return $this;
    }
}
