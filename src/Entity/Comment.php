<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => 'comment:item']),
        new GetCollection(normalizationContext: ['groups' => 'comment:list']),
    ],
    order: ['createdAt' => 'DESC'],
    paginationEnabled: false,
)]
#[ApiFilter(SearchFilter::class, properties: ['conference' => 'exact'])]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['comment:list', 'comment:item'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Constraints\NotBlank]
    #[Groups(['comment:list', 'comment:item'])]
    private ?string $author = null;

    #[ORM\Column(length: 255)]
    #[Constraints\NotBlank]
    #[Constraints\Email]
    #[Groups(['comment:list', 'comment:item'])]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Constraints\NotBlank]
    #[Groups(['comment:list', 'comment:item'])]
    private ?string $text = null;

    #[ORM\Column]
    #[Groups(['comment:list', 'comment:item'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['comment:list', 'comment:item'])]
    private ?string $photoFileName = null;

    #[ORM\ManyToOne(targetEntity: Conference::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['comment:list', 'comment:item'])]
    private ?Conference $conference = null;

    #[ORM\Column(length: 255, options: ['default' => 'submitted'])]
    private ?string $state = 'submitted';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text = null): static
    {
        $this->text = $text;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPhotoFileName(): ?string
    {
        return $this->photoFileName;
    }

    public function setPhotoFileName(?string $photoFileName): static
    {
        $this->photoFileName = $photoFileName;

        return $this;
    }

    public function getConference(): ?Conference
    {
        return $this->conference;
    }

    public function setConference(?Conference $conference): static
    {
        $this->conference = $conference;

        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtToCurrentDate(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }
}
