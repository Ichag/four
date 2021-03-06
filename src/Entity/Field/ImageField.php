<?php

declare(strict_types=1);

namespace Bolt\Entity\Field;

use Bolt\Entity\Field;
use Doctrine\ORM\Mapping as ORM;
use League\Glide\Urls\UrlBuilderFactory;

/**
 * @ORM\Entity
 */
class ImageField extends Field
{
    public function __toString(): string
    {
        $config = $this->getContent()->getConfig();

        $path = $config->getPath('files', false, $this->get('filename'));

        return $path;
    }

    public function getValue(): ?array
    {
        $value = parent::getValue();

        // Create an instance of the URL builder
        $urlBuilder = UrlBuilderFactory::create('/thumbs/');

        // Generate a URL
        $value['path'] = $urlBuilder->getUrl($this->get('filename'), ['w' => 240, 'h' => 160, 'area' => 'files']);

        return $value;
    }
}
