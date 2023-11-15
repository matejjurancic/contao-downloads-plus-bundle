<?php

declare(strict_types=1);

use MatejJurancic\ContaoDownloadsPlusBundle\Controller\ContentElement\ContentDownloadsPlus;

$GLOBALS['TL_DCA']['tl_content']['palettes'][ContentDownloadsPlus::TYPE] =
    '{type_legend},type,headline;'
    . '{source_legend},multiSRC,useHomeDir;'
    . '{download_legend},inline,perPage,sortBy,metaIgnore;'
    . '{preview_legend},showPreview;'
    . '{template_legend:hide},customTpl;'
    . '{protected_legend:hide},protected;'
    . '{expert_legend:hide},guests,cssID;'
    . '{invisible_legend:hide},invisible,start,stop';
