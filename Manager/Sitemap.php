<?php

namespace Buccioni\Bundle\SitemapBundle\Manager;

/**
 * This file is part of the BuccioniSitemapBundle package what is based on
 * BerriartSitemapBundle package what is based on the AvalancheSitemapBundle
 *
 * (c) Bulat Shakirzyanov <avalanche123.com>
 * (c) Alberto Varela <alberto@berriart.com>
 * (c) Felipe Alcacibar <falcacibar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Buccioni\Bundle\SitemapBundle\Entity\Url;
use Buccioni\Bundle\SitemapBundle\Repository\UrlRepositoryInterface;

class Sitemap
{
    const reEnum                = '/^(.*?)(\d+)$/';
    const filesSuffixExtension  = '.xml';
    const _ErrorFileNotExists   = 'Sitemap file "%s" does not exists.';

    const XMLElementUrl         = 'url';
    const XMLElementSitemap     = 'sitemap';
    const XMLElementLoc         = 'loc';

    const _findXMLModeSeekAtEnd         = 0;
    const _findXMLModeSeekAtStart       = 1;
    const _findXMLModeSeekAtStartIter   = 2;
    const _findXMLModeRevSeekAtStart    = 3;

    public $dir;
    public $dirServerPath;
    public $baseUrl;

    public $swapSitemapFileName     = false;
    public $usingDefaultFile        = false;

    public $count              = 0;
    public $fileEnumCount      = 0;

    public $limit               = 50000;

    private $sitemapend;
    private $sitemapendSeek        = -10;

    private $sitemapindexend;
    private $sitemapindexendSeek   = -15;

    public $currentFile;
    public $sitemapIndex;

    public $defaultIndexFileName;
    public $defaultFileName;

    private $templating;

    public $files                  = array();
    public $sitemaps               = array();

    public function __construct($templating, $dir, $dirServerPath, $baseUrl, $limit, $fileName, $indexFileName, $swapSitemapFileName)
    {
            $this->setDir($dir);
            $this->dirServerPath        = $dirServerPath;
            $this->baseUrl              = $baseUrl;
            $this->limit                = $limit;
            $this->defaultFileName             = $fileName;
            $this->defaultIndexFileName        = $indexFileName;
            $this->swapSitemapFileName  = $swapSitemapFileName;
            $this->templating           = $templating;
    }

    public function __destruct() {
        foreach(array_keys($this->files) as $file) {
            $this->closeFile($file);
        }
    }

    static function checkDirectory($dir, $fromParent=false) {
        if(!file_exists($dir)) {
            $parent     = dirname($dir);
            $created    = true;

            if(!file_exists($parent))
                $created = self::checkDirectory($parent, true);

            if($created) {
                if(is_writable($parent)) {
                    mkdir($dir);
                    return true;
                } else if($fromParent)
                    return false;
                else
                    throw new \Exception(sprintf('Cannot create "%s" directory', $dir));
            }
        }

        if(file_exists($dir)) {
            if(is_writable($dir))
                return true;
            else
                throw new \Exception(sprintf('Directory "%s" is not writable.', $dir));
        }
    }

    public function setDir($dir) {
        if(self::checkDirectory($dir)) {
            $this->dir = $dir;
        }

        return $this;
    }

    public function genFileName($name) {
        return $this->dir.'/'.$name.self::filesSuffixExtension;
    }

    public function genSitemapPath($name) {
        return $this->baseUrl.$this->dirServerPath.'/'.$name.self::filesSuffixExtension;
    }

    public static function genNameFromPath($path) {
        if(is_string($path))
            return substr(basename($path), 0, -strlen(self::filesSuffixExtension));
    }

    public function defaultSitemapPath() {
        return self::genSitemapPath($this->defaultFileName);
    }

    public function defaultIndexPath() {
        return self::genSitemapPath($this->defaultIndexFileName);
    }

    public function createIndexFile($name=null) {
        if(is_null($name))
            $name = $this->defaultIndexFileName;

        return $this->_addFile($name, true);
    }

    public function addFile($name) {
        return $this->_addFile($name, false);
    }

    private function _addFile($name, $index) {
        $fileName = $this->genFileName($name);

        if(file_exists($fileName))
            throw new \Exception(sprintf(
                            '%s file "%s" yet exists. Consider the use of openFile() method instead.'
                            , 'Sitemap'.(($index) ? ' Index' : null)
                            , $fileName
            ));
        else {
            touch($fileName);
            $this->_openFile($name, false, $index);

            if($index)
                $this->sitemapIndex = $name;

            $this->initFile($name);
        }

        return $this;
    }

    private function initFile($name) {
        if(isset($this->files[$name]))
            fseek($this->files[$name], 0, SEEK_SET);

            fwrite(
                    $this->files[$name]
                    , $this->templating->render(
                        ($name === $this->sitemapIndex
                            ? 'BuccioniSitemapBundle:Sitemap:sitemapindex.xml.twig'
                            : 'BuccioniSitemapBundle:Sitemap:sitemap.xml.twig'
                        )
                        , array('sitemap' => $this)
                    )
                    , 2048
            );

            $this->seekFileToLastEntry($name);

            return $this;
    }

    public function moveFile($oldName, $newName) {
        $oldFileName = $this->genFileName($oldName);

        if(file_exists($oldFileName)) {
            $newFileName = $this->genFileName($newName);

            if(file_exists($newFileName))
                throw new \Exception(sprintf('Sitemap file "%s" already exists.', $newFileName));

            if(isset($this->files[$oldName])) {
                $repointer = true;
                $this->closeFile($oldName);
            }

            rename($oldFileName, $newFileName);

            if($repointer)
                $this->openFile($newName);
        } else
            throw new \Exception(sprintf(self::_ErrorFileNotExists, $oldFileName));
    }

    public function openIndexFile($name=null, $addIfNotExists=true) {
        if(is_null($name))
            $name = $this->defaultIndexFileName;

        return $this->_openFile($name, $addIfNotExists, true);
    }

    public function openFile($name, $addIfNotExists=false) {
        return $this->_openFile($name, $addIfNotExists, false);
    }

    private function _openFile($name, $addIfNotExists, $index) {
        $this->processEndTemplates();
        $fileName = $this->genFileName($name);

        if(file_exists($fileName)) {
            $this->files[$name]     = fopen($fileName, 'ra+');

            if($index) {
                if(!empty($this->sitemapIndex))
                    $this->closeFile($this->sitemapIndex);

                $this->sitemapIndex = $name;
                $this->readSitemapsFromIndex();
            } else
                $this->setCurrentFile($name);

            $this->seekFileToLastEntry($name);

            return $this;
        } elseif($addIfNotExists)
            return $this->_addFile($name, $index);
        else throw new \Exception(sprintf(
                            self::_ErrorFileNotExists.'Consider the second bool parameter $addIfNoExists.'
                            , $fileName
        ));
    }

    public function closeFile($name) {
        if(isset($this->files[$name])) {
            fclose($this->files[$name]);
            unset($this->files[$name]);

            if($this->sitemapIndex === $name) {
                $this->sitemapIndex = null;
                $this->sitemaps     = array();
            }
        }

        return $this;
    }

    public function deleteFile($name, $enumsToo=false) {
        $sel = strlen(self::filesSuffixExtension);

        foreach(glob($this->dir.'/*'.self::filesSuffixExtension) as $file) {
            if($enumsToo)
                $filter = preg_match('/^'.$name.'(\d+)?$/', substr(basename($file), 0, -$sel));
            else
                $filter = substr(basename($file), 0, -$sel) === $name;

            if($filter)
                unlink($file);
        }

        return $this;
    }

    private function processEndTemplates() {
        if(empty($this->sitemapend)) {
            $this->sitemapend = $this->templating->render(
                            'BuccioniSitemapBundle:Sitemap:sitemap-end.xml.twig'
            );

            $this->sitemapendSeek = -strlen($this->sitemapend);
        }

        if(empty($this->sitemapindexend)) {
            $this->sitemapindexend = $this->templating->render(
                            'BuccioniSitemapBundle:Sitemap:sitemapindex-end.xml.twig'
            );

            $this->sitemapindexendSeek = -strlen($this->sitemapindexend);
        }
    }

    private function seekFileToLastEntry($name) {
        fseek(
                $this->files[$name]
                , ($name === $this->sitemapIndex
                    ? $this->sitemapindexendSeek
                    : $this->sitemapendSeek
                )
                , SEEK_END
        );

        return $this;
    }

    public static function _findXMLElem($fp, $elem, $trim=true, $findEqual=null, $seekMode=self::_findXMLModeSeekAtEnd) {
        $seekAtStart = true;

        $elemStart  = '<'.$elem;
        $elemEnd    = '</'.$elem.'>';

        $len        = strlen($elem);
        $lenStart   = $len + 2; // <elem
        $lenEnd     = $len + 3; // </elem>
        $lenBuff    = 0;
        $lenCur     = $lenStart;

        $buff       = '';
        $string     = '';
        $record     = false;

        $return =   (
                        $seekMode === self::_findXMLModeSeekAtStartIter
                        && ftell($fp) !== 0
                    )
                    ? false
                    : empty($findEqual)
        ;


        while(!feof($fp)) {
            $c     = fgetc($fp);
            $buff .= $c;
            ++$lenBuff;

            if($lenBuff > $lenCur) {
                $buff       = substr($buff, -$lenCur);
                $lenBuff    = $lenCur;
            }

            if($record) {
                $string .= $c;

                if(
                    $lenBuff === $lenCur
                    && $buff === $elemEnd
                ) {
                    $record = false;
                    $string = html_entity_decode(substr($string, 0, -$lenCur));
                    $lenCur = $lenStart;

                    if(!empty($findEqual)) {
                        if($trim)
                            $string = trim($string);

                        $return = ($string === $findEqual);
                    }

                    if($return)
                        return $string;
                    elseif($seekMode === self::_findXMLModeSeekAtStartIter)
                        $return  = true;
                }
            } else {
                if(
                    $lenBuff === $lenCur
                    && self::isXMLTagNameEnd($c)
                    && substr($buff, 0, -1) === $elemStart
                ) {
                    $seekStart = ftell($fp) - ($lenStart + 1);
                    $record = true;
                    $lenCur = $lenEnd;
                }
            }
        }
    }

    public static function isXMLTagNameEnd($c) {
        switch(ord($c)) {
            case 0:
            case 9:
            case 10:
            case 11:
            case 13:
            case 21:
            case 32:
            case 62;
                return true;
            break;
            default:
                return false;
        }
    }

    private function checkCurrentFile() {
        if(
                is_null($this->currentFile)
                || !isset($this->files[$this->currentFile])
        )
            $this->openFile($this->defaultFileName, true);
    }

    public function setCurrentFile($name) {
        if(isset($this->files[$name])) {
            $this->currentFile      = $name;
            $this->fileEnumCount    = (int) preg_replace(self::reEnum, '$2', $name);
        } else throw new \Exception(sprintf(
                            "Sitemap file '%s' is not opened yet."
                            , $name
        ));

        return $this;
    }

    public function nextCurrentEnumFile() {
        $this->fileEnumCount += 1;

        return preg_replace(
                        self::reEnum
                        , '$1'
                        , $this->defaultFileName
                )
                .$this->fileEnumCount
        ;
    }

    public function addSitemap() {
        $sitemaps = func_get_args();

        if(!($c=count($sitemaps)))
            $sitemaps = array($this->currentFile);
        elseif($c==1&&  is_array($sitemaps[0]))
            $sitemaps = $sitemaps[0];

        $buff = 3072 + abs($this->sitemapindexendSeek);

        foreach($sitemaps as &$sitemap) {
            if(!in_array($sitemap, $this->sitemaps)) {
                if(file_exists($this->genFileName($sitemap))) {
                    fwrite(
                            $this->files[$this->sitemapIndex]
                            , $this->templating->render(
                                    'BuccioniSitemapBundle:Sitemap:sitemapindex-sitemap.xml.twig'
                                    , array(
                                        'path' => $this->genSitemapPath($sitemap)
                                    )
                            ).$this->sitemapindexend
                            , $buff
                    );

                    $this->seekFileToLastEntry($this->sitemapIndex);
                    $this->sitemaps[] = $sitemap;
                } else throw new \Exception(sprintf(self::_ErrorFileNotExists, $sitemap));
            }
        }
    }

    public function readSitemapsFromIndex($name=null) {
        if(is_null($name))
            $name = $this->defaultIndexFileName;

        if(is_null($this->sitemapIndex))
            $this->openIndexFile($name);

        $fp = $this->files[$this->sitemapIndex];
        fseek($fp, 0, SEEK_SET);

        while(!is_null($name = self::genNameFromPath(self::_findXMLElem($fp, self::XMLElementLoc, false)))) {
            if(!isset($this->files[$name]))
                $this->openFile($name);

            if(!in_array($name, $this->sitemaps))
                $this->sitemaps[] = $name;
        }

        return $this;
    }

    public function removeSitemap() {
        // not implemented yet
    }

    public function addUrl(Url $url)
    {
        $this->checkCurrentFile();

        $buff = 3072 + (count($url->getImages()) * 5120) + abs($this->sitemapendSeek);

        fwrite(
                $this->files[$this->currentFile]
                , $this->templating->render(
                        'BuccioniSitemapBundle:Sitemap:sitemap-url.xml.twig'
                        , compact('url')
                ).$this->sitemapend
                , $buff
        );

        $this->seekFileToLastEntry($this->currentFile);

        $this->count++;

        if($this->count>=$this->limit) {
            $toIndex = array();

            if($this->swapSitemapFileName) {
                $this->defaultIndexFileName = $this->currentFile;
                $this->moveFile($this->currentFile, $this->nextCurrentEnumFile());
            }

            if(empty($this->sitemapIndex))
                $this->openIndexFile(null, true);

            $oldFile  = $this->currentFile;
            $this
                ->addFile($this->nextCurrentEnumFile())
                ->addSitemap($oldFile, $this->currentFile)
            ;

            $this->count = 0;
        }

        return $this;
    }

    public function removeUrl(Url $url) {
        // not implemented yet
        return $this;
    }

    public function add(Url $url) {
        return $this->addUrl($url);
    }

    public function remove(Url $url) {
        return $this->removeUrl($url);
    }

    public function all()
    {
        // dangerous
    }

    public function get($loc)
    {
        // not implemented yet
    }

    public function pages()
    {
        return 0;
    }

    public function setPage($page)
    {
        return $this;
    }

    public function getPage()
    {
        return $this;
    }

    public function save()
    {
        $this->closeFile($this->currentFile);
        return $this;
    }

    public function lastmod()
    {
        return ;
    }
}