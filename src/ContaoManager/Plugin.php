<?php

declare(strict_types=1);

namespace MatejJurancic\ContaoDownloadsPlusBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use MatejJurancic\ContaoDownloadsPlusBundle\ContaoDownloadsPlusBundleBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * @return array<int, BundleConfig>
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoDownloadsPlusBundleBundle::class)
                        ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
