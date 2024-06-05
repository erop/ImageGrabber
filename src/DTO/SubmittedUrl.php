<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SubmittedUrl
{
    #[Assert\Url()]
    private string $url;
    
    public function getUrl(): string
    {
        return $this->url;
    }
    
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
}
