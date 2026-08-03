<?php


namespace App\Interfaces;


interface PortalRepositoryInterface
{
    public function getAll(): array;
    public function getOne(int $id): array;
}
