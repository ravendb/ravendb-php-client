<?php

namespace tests\RavenDB\Infrastructure\Graph;

class UserRating
{
    private ?string $movie = null;
    private ?int $score = null;

    public function getMovie(): ?string
    {
        return $this->movie;
    }

    public function setMovie(?string $movie): void
    {
        $this->movie = $movie;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): void
    {
        $this->score = $score;
    }
}
