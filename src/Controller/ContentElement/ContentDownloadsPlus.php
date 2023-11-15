<?php

declare(strict_types=1);

namespace MatejJurancic\ContaoDownloadsPlusBundle\Controller\ContentElement;

use Contao\ArrayUtil;
use Contao\Config;
use Contao\ContentDownloads;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\File;
use Contao\FilesModel;
use Contao\Image;
use Contao\Input;
use Contao\PageModel;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;

use function count;
use function in_array;

/**
 * Front end content element "downloadsPlus".
 */
class ContentDownloadsPlus extends ContentDownloads
{
    public const TYPE = 'downloadsPlus';

    protected $strTemplate = 'ce_downloads_plus';

    protected function compile(): void
    {
        $files           = [];
        $allowedDownload = StringUtil::trimsplit(
            ',',
            strtolower(Config::get('allowedDownload'))
        );

        $container  = System::getContainer();
        $projectDir = $container->getParameter('kernel.project_dir');
        $request    = $container->get('request_stack')->getCurrentRequest();
        $isBackend  = $request
                      && $container->get('contao.routing.scope_matcher')
                                   ->isBackendRequest($request);

        $objFiles = $this->objFiles;

        // Get all files
        while ($objFiles->next()) {
            // Continue if the files has been processed or does not exist
            if (
                isset($files[$objFiles->path])
                || ! file_exists($projectDir . '/' . $objFiles->path)
            ) {
                continue;
            }

            // Single files
            if ($objFiles->type == 'file') {
                $objFile = new File($objFiles->path);

                if (
                    ! in_array($objFile->extension, $allowedDownload)
                    || preg_match(
                        '/^meta(_[a-z]{2})?\.txt$/',
                        $objFile->basename
                    )
                ) {
                    continue;
                }

                if ($isBackend) {
                    $arrMeta = $this->getMetaData(
                        $objFiles->meta,
                        $GLOBALS['TL_LANGUAGE']
                    );
                } else {
                    /** @var PageModel $objPage */
                    global $objPage;

                    $arrMeta = $this->getMetaData(
                        $objFiles->meta,
                        $objPage->language
                    );

                    if (empty($arrMeta)) {
                        if ($this->metaIgnore) {
                            continue;
                        }

                        if ($objPage->rootFallbackLanguage !== null) {
                            $arrMeta = $this->getMetaData(
                                $objFiles->meta,
                                $objPage->rootFallbackLanguage
                            );
                        }
                    }
                }

                // Use the file name as title if none is given
                if (empty($arrMeta['title'])) {
                    $arrMeta['title'] = StringUtil::specialchars(
                        $objFile->basename
                    );
                }

                $strHref = Environment::get('request');

                // Remove an existing file parameter (see #5683)
                if (isset($_GET['file'])) {
                    $strHref = preg_replace(
                        '/(&(amp;)?|\?)file=[^&]+/',
                        '',
                        $strHref
                    );
                }

                if (isset($_GET['cid'])) {
                    $strHref = preg_replace(
                        '/(&(amp;)?|\?)cid=\d+/',
                        '',
                        $strHref
                    );
                }

                $strHref .= (str_contains($strHref, '?') ? '&amp;' : '?')
                            . 'file=' . System::urlEncode($objFiles->path)
                            . '&amp;cid=' . $this->id;

                // Add the image
                $files[$objFiles->path] = [
                    'id'        => $objFiles->id,
                    'uuid'      => $objFiles->uuid,
                    'name'      => $objFile->basename,
                    'title'     => StringUtil::specialchars(
                        sprintf(
                            $GLOBALS['TL_LANG']['MSC']['download'],
                            $objFile->basename
                        )
                    ),
                    'link'      => $arrMeta['title'] ?? null,
                    'caption'   => $arrMeta['caption'] ?? null,
                    'href'      => $strHref,
                    'filesize'  => $this->getReadableSize($objFile->filesize),
                    'icon'      => Image::getPath($objFile->icon),
                    'mime'      => $objFile->mime,
                    'meta'      => $arrMeta,
                    'extension' => $objFile->extension,
                    'path'      => $objFile->dirname,
                    'previews'  => $this->getPreviews($objFile->path, $strHref),
                    'mtime'     => $objFile->mtime,
                ];
            } else {
                // Folders
                $objSubfiles = FilesModel::findByPid(
                    $objFiles->uuid,
                    array('order' => 'name')
                );

                if ($objSubfiles === null) {
                    continue;
                }

                while ($objSubfiles->next()) {
                    // Skip subfolders
                    if ($objSubfiles->type == 'folder') {
                        continue;
                    }

                    $objFile = new File($objSubfiles->path);

                    if (
                        ! in_array($objFile->extension, $allowedDownload)
                        || preg_match(
                            '/^meta(_[a-z]{2})?\.txt$/',
                            $objFile->basename
                        )
                    ) {
                        continue;
                    }

                    if ($isBackend) {
                        $arrMeta = $this->getMetaData(
                            $objSubfiles->meta,
                            $GLOBALS['TL_LANGUAGE']
                        );
                    } else {
                        /** @var PageModel $objPage */
                        global $objPage;

                        $arrMeta = $this->getMetaData(
                            $objSubfiles->meta,
                            $objPage->language
                        );

                        if (empty($arrMeta))
                        {
                            if ($this->metaIgnore)
                            {
                                continue;
                            }

                            if ($objPage->rootFallbackLanguage !== null)
                            {
                                $arrMeta = $this->getMetaData(
                                    $objSubfiles->meta,
                                    $objPage->rootFallbackLanguage
                                );
                            }
                        }
                    }

                    // Use the file name as title if none is given
                    if (empty($arrMeta['title'])) {
                        $arrMeta['title'] = StringUtil::specialchars(
                            $objFile->basename
                        );
                    }

                    $strHref = Environment::get('request');

                    // Remove an existing file parameter (see #5683)
                    if (preg_match('/(&(amp;)?|\?)file=/', $strHref))
                    {
                        $strHref = preg_replace(
                            '/(&(amp;)?|\?)file=[^&]+/',
                            '',
                            $strHref
                        );
                    }

                    $strHref .= (str_contains($strHref, '?') ? '&amp;' : '?')
                                . 'file='
                                . System::urlEncode($objSubfiles->path);

                    // Add the image
                    $files[$objSubfiles->path] = [
                        'id'        => $objSubfiles->id,
                        'uuid'      => $objSubfiles->uuid,
                        'name'      => $objFile->basename,
                        'title'     => StringUtil::specialchars(
                            sprintf(
                                $GLOBALS['TL_LANG']['MSC']['download'],
                                $objFile->basename
                            )
                        ),
                        'link'      => $arrMeta['title'],
                        'caption'   => $arrMeta['caption'] ?? null,
                        'href'      => $strHref,
                        'filesize'  => $this->getReadableSize($objFile->filesize),
                        'icon'      => Image::getPath($objFile->icon),
                        'mime'      => $objFile->mime,
                        'meta'      => $arrMeta,
                        'extension' => $objFile->extension,
                        'path'      => $objFile->dirname,
                        'previews'  => $this->getPreviews($objFile->path, $strHref),
                        'mtime'     => $objFile->mtime,
                    ];
                }
            }
        }

        // Sort array
        switch ($this->sortBy) {
            default:
            case 'name_asc':
                uksort(
                    $files,
                    static fn ($a, $b): int => strnatcasecmp(basename($a), basename($b))
                );
                break;

            case 'name_desc':
                uksort(
                    $files,
                    static fn ($a, $b): int => -strnatcasecmp(basename($a), basename($b))
                );
                break;

            case 'date_asc':
                uasort(
                    $files,
                    static fn (array $a, array $b) => $a['mtime'] <=> $b['mtime']
                );
                break;

            case 'date_desc':
                uasort(
                    $files,
                    static fn (array $a, array $b) => $b['mtime'] <=> $a['mtime']
                );
                break;

            // Deprecated since Contao 4.0, to be removed in Contao 5.0
            case 'meta':
                trigger_deprecation(
                    'contao/core-bundle',
                    '4.0',
                    'The "meta" key in "Contao\ContentDownloads::compile()" has'
                    . ' been deprecated and will no longer work in Contao 5.0.'
                );
            // no break

            case 'custom':
                $files = ArrayUtil::sortByOrderField($files, $this->orderSRC);
                break;

            case 'random':
                shuffle($files);
                break;
        }

        $offset = 0;
        $total  = count($files);
        $limit  = $total;

        // Paginate the result of not randomly sorted (see #8033)
        if ($this->perPage > 0 && $this->sortBy != 'random') {
            // Get the current page
            $id = 'page_dp' . $this->id;
            $page = (int) (Input::get($id) ?? 1);

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total/$this->perPage), 1)) {
                throw new PageNotFoundException(
                    'Page not found: ' . Environment::get('uri')
                );
            }

            // Set limit and offset
            $offset = ($page - 1) * $this->perPage;
            $limit  = min($this->perPage + $offset, $total);

            $objPagination = new Pagination(
                $total,
                $this->perPage,
                Config::get('maxPaginationLinks'),
                $id
            );
            $this->Template->pagination = $objPagination->generate("\n  ");
        }

        $tmp         = 1;
        $actualFiles = [];
        foreach ($files as $file) {
            if ($tmp > $offset && $tmp <= $limit) {
                $actualFiles[] = $file;
            }
            $tmp++;
        }

        $this->Template->files = $actualFiles;
    }
}
