<?php

namespace App\Modules\Pub\Breadcrumbs\Models;

class BreadcrumbItem
{
    private string|null $link;
    private string $name;
    private bool $muted;
    private bool $is_last;



    public function __construct(string|null $link, string $name, bool $muted = false)
    {
        $this->link = $link;
        $this->name = $name;
        $this->muted = $muted;
        $this->is_last = true;
    }

    public function isMuted(): bool
    {
        return $this->muted;
    }
    public function isLast(): bool
    {
        return $this->is_last;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLink(): string|null
    {
        return $this->link;
    }

    public function setNotLast()
    {
        $this->is_last = false;
    }
}
