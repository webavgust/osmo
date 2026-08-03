<?php


namespace App\Interfaces;


interface PortalSyncInterface
{
    public function syncAll(): void;
    public function syncOne(int $id): void;
}
