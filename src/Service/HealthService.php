<?php

namespace App\Service;

class HealthService
{
    public function __construct($health)
    {
        $this->health = $health;
    }

    public function getHealth()
    {
        return $this->health;
    }
}
