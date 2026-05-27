<?php

namespace App\Entity;

use App\Repository\BingoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BingoRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Bingo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'L\'année est requise.')]
    #[Assert\Range(min: 1900, max: 2100, notInRangeMessage: 'L\'année doit être entre {{ min }} et {{ max }}.')]
    private ?int $year = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est requis.')]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 8, unique: true)]
    private ?string $slug = null;

    /**
     * @var Collection<int, BingoItem>
     */
    #[ORM\OneToMany(targetEntity: BingoItem::class, mappedBy: 'bingo', cascade: ['persist', 'remove'])]
    private Collection $bingoItems;

    public function __construct()
    {
        $this->bingoItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, BingoItem>
     */
    public function getBingoItems(): Collection
    {
        return $this->bingoItems;
    }

    public function addBingoItem(BingoItem $bingoItem): static
    {
        if (!$this->bingoItems->contains($bingoItem)) {
            $this->bingoItems->add($bingoItem);
            $bingoItem->setBingo($this);
        }

        return $this;
    }

    public function removeBingoItem(BingoItem $bingoItem): static
    {
        if ($this->bingoItems->removeElement($bingoItem)) {
            // set the owning side to null (unless already changed)
            if ($bingoItem->getBingo() === $this) {
                $bingoItem->setBingo(null);
            }
        }

        return $this;
    }

    #[ORM\PrePersist]
    public function generateSlug(): void
    {
        if ($this->slug === null) {
            $this->slug = bin2hex(random_bytes(4));
        }
    }
}
