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
    const reNameEnunm           = '/^%s(\d+)?$/';

    const filesSuffixExtension  = '.xml';
    const _ErrorFileNotExists   = 'Sitemap file "%s" does not exists.';

    const XMLElementUrl         = 'url';
    const XMLElementSitemap     = 'sitemap';
    const XMLElementLoc         = 'loc';

    const _findXMLModeSeekAtEnd         = 0;
    const _findXMLModeSeekAtStart       = 1;
    const _findXMLModeSeekAtStartIter   = 2;
    const _findXMLModeRevSeekAtStart    = 3;
    const _findXMLModeBoolFast          = 4;
    const _findXMLModeBool              = 5;

    public $dir;
    public $dirServerPath;
    public $baseUrl;

    public $swapSitemapFileName     = false;
    public $usingDefaultFile        = false;

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
    private $container;

    private $filesEnum             = array();
    public $filesCounter          = array();

    public $files                  = array();
    public $sitemaps               = array();

    public $twigExtension;

    public function __construct($container, $templateType, $dir, $dirServerPath, $baseUrl, $limit, $fileName, $indexFileName, $swapSitemapFileName)
    {
        $this->setDir($dir);

        switch($templateType) {
            case 'subphp':
                $this->templateType = self::templateTypeSubphp;
            break;
            case 'subphp-unstable':
                $this->templateType = self::templateTypeSubphpUnstable;
            break;
            default:
                $this->templateType = self::templateTypeTwig;
        }


        $this->dirServerPath            = $dirServerPath;
        $this->baseUrl                  = $baseUrl;
        $this->limit                    = $limit;
        $this->defaultFileName          = $fileName;
        $this->defaultIndexFileName     = $indexFileName;
        $this->swapSitemapFileName      = $swapSitemapFileName;
        $this->container                = $container;
        $this->templating               = ($this->templateType === self::templateTypeTwig)
                                            ? $container->get('templating')
                                            : $this
        ;

        if(is_null($this->twigExtension)) {
            $reflEnviron=new \ReflectionProperty($this->container->get('templating'), 'environment');
            $reflEnviron->setAccessible(true);
            $twigEnvironment = $reflEnviron->getValue($this->container->get('templating'));

            $twigExtensions = $twigEnvironment->getExtensions();
            $this->twigExtension = $twigExtensions['buccioni_sitemap'];
        }
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


    public function getNameWithEnums(/* $name, [$name...] */) {
        $names = func_get_args();
        if(!empty($names) && is_array($names[0]))
            $names = $names[0];

        return $this->_fileNameWithEnums($names, true);
    }

    public function getFileNameWithEnums(/* $name, [$name...] */) {
        $names = func_get_args();
        if(!empty($names) && is_array($names[0]))
            $names = $names[0];

        return $this->_fileNameWithEnums($names);
    }

    private function _fileNameWithEnums($names, $onlyNames=false) {
        $sel    = strlen(self::filesSuffixExtension);
        $files  = glob($this->dir.'/*'.self::filesSuffixExtension);

        $names = func_get_args();
        if(!empty($names) && is_array($names[0]))
            $names = $names[0];

        for($i=0,$c=count($files);$i<$c;++$i) {
            $found = false;

            foreach($names as &$name) {
                $nameFromFile = substr(basename($files[$i]), 0, -$sel);
                if($found = preg_match(sprintf(self::reNameEnunm, $name), $nameFromFile))
                    break;
            }

            if(!$found) {
                array_splice($files, $i--, 1);
                --$c;
            } elseif($onlyNames)
                $files[$i] = $nameFromFile;
        }

        return $files;
    }

    public function setFileEnum($name) {
        $names = $this->getNameWithEnums($name);
        $enumCount = 0;

        if(!empty($names)) {
            foreach($names as &$ename)  {
                $num = (int) preg_replace(
                        sprintf(self::reNameEnunm, $name)
                        , '$1'
                        , $ename
                );

                if($num > $enumCount)
                    $enumCount = $num;

            }
        }

        $this->filesEnum[$name] = $enumCount;
    }

    public function currentEnumFile($name=null) {
        return $this->_enumFile($name, false);
    }

    public function nextEnumFile($name=null) {
        return $this->_enumFile(is_null($name) ? null : preg_replace(self::reEnum, '$1', $name), true);
    }

    private function _enumFile($name=null, $next=false) {
        if(is_null($name))
            $name = $this->currentFile;

        if(!isset($this->filesEnum[$name]))
            $this->setFileEnum($name);

        if($next)
            $this->filesEnum[$name] += 1;

        return $name.($this->filesEnum[$name] === 0 ? '' : $this->filesEnum[$name] );
    }

    public function setCounterOf($name) {
        if(!isset($this->files[$name]))
            $this->openFile($name);

        if(!isset($this->filesCounter[$name]))
            $this->filesCounter[$name] = 0;

        $fp = $this->files[$name];
        fseek($fp, 0, SEEK_SET);

        $elemStart  = '<'.self::XMLElementSitemap;
        $len        = strlen(self::XMLElementSitemap) + 1;
		$buff 		= '';

		while(!feof($fp)){
			$buff    = fread($fp, 4096);
			$pos     = 0;

			while(($pos=strpos($buff, $elemStart, $pos)) !== false) {
                if(isset($buff{$pos + $len}) && $this::isXMLTagNameEnd($buff{$pos + $len})) {
                    ++$this->filesCounter[$name];
                }

				++$pos;
			}

			if(isset($buff{$len}))
				fseek($fp, -$len, SEEK_CUR);
		}

        $this->seekFileToLastEntry($name);

        return $this;
    }

    public function createIndexFile($name=null) {
        if(is_null($name))
            $name = $this->defaultIndexFileName;

        return $this->_addFile($name, true);
    }

    public function addFile($name, $addToIndex=false) {
        return $this->_addFile($name, $addToIndex, false);
    }

    private function _addFile($name, $addToIndex, $isIndex) {
        $fileName = $this->genFileName($name);

        if(file_exists($fileName))
            throw new \Exception(sprintf(
                        '%s file "%s" yet exists. Consider the use of openFile() method instead.'
                        , 'Sitemap'.(($isIndex) ? ' Index' : null)
                        , $fileName
            ));
        else {
            touch($fileName);
            $this->_openFile($name, false, false, $addToIndex, $isIndex);

            if($isIndex)
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
                            ? 'BuccioniSitemapBundle:Sitemap:sitemapindex.xml.php'
                            : 'BuccioniSitemapBundle:Sitemap:sitemap.xml.php'
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

        return $this->_openFile($name, $addIfNotExists, false, false, true);
    }

    public function openFile($name, $addIfNotExists=false, $lastEnum=false, $addToIndex=false) {
        return $this->_openFile($name, $addIfNotExists, $lastEnum, $addToIndex, false);
    }

    private function _openFile($name, $addIfNotExists, $lastEnum, $addToIndex, $isIndex) {
        $this->processEndTemplates();
        $fileName = $this->genFileName($name);

        if(file_exists($fileName)) {
            if(!$isIndex)
                $this->setFileEnum($name);

            if($lastEnum)
                $name = $this->currentEnumFile($name);

            $this->files[$name]     = fopen($fileName, 'rwa+');

            stream_set_write_buffer($this->files[$name], 0);
            stream_set_read_buffer($this->files[$name], 0);

            if($isIndex) {
                if(!empty($this->sitemapIndex))
                    $this->closeFile($this->sitemapIndex);

                $this->sitemapIndex = $name;
                $this->readSitemapsFromIndex();
            } else {
                $this->setCurrentFile($name);
                $this->setCounterOf($name);

                if($addToIndex)
                    $this->addSitemap($name);
            }

            $this->seekFileToLastEntry($name);

            return $this;
        } elseif($addIfNotExists)
            return $this->_addFile($name, $addToIndex, $isIndex);
        else throw new \Exception(sprintf(
                            self::_ErrorFileNotExists.' Consider the second bool parameter $addIfNoExists.'
                            , $fileName
        ));
    }

    public function closeFile($name) {
        if(isset($this->filesEnum[$name]))
            unset($this->filesEnum[$name]);

        if(isset($this->filesCounter[$name]))
            unset($this->filesCounter[$name]);

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
        if($enumsToo) {
            foreach($this->getFileNameWithEnums($name) as $file)
                unlink($file);
        } else {
            $file = $this->genFileName($name);

            if(file_exists($file))
                unlink($file);
        }
        return $this;
    }

    private function processEndTemplates() {
        if(empty($this->sitemapend)) {
            $this->sitemapend = $this->templating->render(
                     'BuccioniSitemapBundle:Sitemap:sitemap-end.xml.php'
            );

            $this->sitemapendSeek = -strlen($this->sitemapend);
        }

        if(empty($this->sitemapindexend)) {
            $this->sitemapindexend = $this->templating->render(
                    'BuccioniSitemapBundle:Sitemap:sitemapindex-end.xml.php'
            );

            $this->sitemapindexendSeek = -strlen($this->sitemapindexend);
        }
    }

    public function lastUpdate($name) {
        return filemtime($this->genFileName($name));
    }

    private function seekFileToLastEntry($name) {
        fseek(
                $this->files[$name]
                , ($name === $this->sitemapIndex
                    ? $this->sitemapindexendSeek
                    : $this->sitemapendSeek
                ) - 1
                , SEEK_END
        );

        fseek($this->files[$name], -5, SEEK_CUR);

        if(ftell($this->files[$name]) !== 0) {
            while(fgetc($this->files[$name]) !== '<');
            fseek($this->files[$name], -1, SEEK_CUR);
        }

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

        $rev        = $seekMode === self::_findXMLModeRevSeekAtStart;

        $seekAtStart =  (
                            $seekMode === self::_findXMLModeSeekAtStartIter
                            || $seekMode === self::_findXMLModeSeekAtStart
                            || $seekMode === self::_findXMLModeRevSeekAtStart
        );

        $return =   (
                        $seekMode === self::_findXMLModeSeekAtStartIter
                        && ftell($fp) !== 0
                    )
                    ? false
                    : empty($findEqual)
        ;

        while(!feof($fp)) {
            $c     = fgetc($fp);

            if($rev)
                $buff = $c.$buff;
            else
                $buff .= $c;

            ++$lenBuff;

            if($lenBuff > $lenCur) {
                $buff       = $rev
                                ? substr($buff, 0, $lenCur)
                                : substr($buff, -$lenCur)
                ;

                $lenBuff    = $lenCur;
            }

            if($rev)
                $ec   = substr($buff, -1);
            else
                $ec   = &$c;

            if($record) {
                $string .= $c;

                if(
                    $lenBuff === $lenCur
                    && $buff === $elemEnd
                ) {
                    $string = html_entity_decode(substr($string, 0, -$lenCur));

                    if(
                        !empty($findEqual)
                        && (
                            $seekMode !== self::_findXMLModeSeekAtStartIter
                            || $return
                        )
                    ) {
                        if($trim)
                            $string = trim($string);

                        $return = ($string === $findEqual);
                    }

                    if($return) {
                        if($seekAtStart)
                            fseek($fp, $seekStart);

                        return $string;
                    } elseif($seekMode === self::_findXMLModeSeekAtStartIter)
                        $return  = true;

                    $record = false;
                    $lenCur = $lenStart;
                    $string = '';
                }
            } else {
                if(
                    $lenBuff === $lenCur
                    && self::isXMLTagNameEnd($ec)
                    && substr($buff, 0, -1) === $elemStart
                ) {
                    if($seekMode === self::_findXMLModeBoolFast)
                        return true;
                    else if($rev) {
                        $seekMode   = self::_findXMLModeSeekAtStart;
                        $seekStart  = ftell($fp) - 1;
                        fseek($fp, $lenStart - 1, SEEK_CUR);

                        $rev = false;
                    } else
                        $seekStart = ftell($fp) - ($lenStart + 1);

                    $record = true;
                    $lenCur = $lenEnd;
                }
            }

            if($rev) {
                fseek($fp, -2, SEEK_CUR);
            }
        }

        if(
            $seekMode === self::_findXMLModeBoolFast
            || $seekMode === self::_findXMLModeBool
        )
            return false;
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

    public $templateNames   = array();
    public $templates       = array();

    public function absolutize($url) {
        return $this->twigExtension->getAbsoluteUrl($url);
    }

    private function templatePath($name) {
        return $this->container->get('templating.locator')->locate(
            $this->container->get('templating.name_parser')->parse($name)
        );
    }

    const templateTypeTwig             = 0;
    const templateTypeSubphp           = 1;
    const templateTypeSubphpUnstable   = 2;

    public $templateType = self::templateTypeSubphpUnstable;

    const reTemplateInclude = '/<\?php\s+include\s+(.*?)\s+\?>/smx';

    public function __get_tpl_contents($matches) {
        return file_get_contents(eval('return $this->templatePath('.$matches[1].');'));
    }

    public function render($name, $environment=array()) {
        if(!isset($this->templateNames[$name])) {
            $path = $this->templatePath($name);
            $this->templateNames[$name] = '___sitemaptemplate__'.hash('sha256', $path);

            switch($this->templateType) {
                case self::templateTypeSubphpUnstable:
                    $template = str_replace(
                        "'"
                        , "\\'"
                        , preg_replace_callback(
                                self::reTemplateInclude
                                , array($this, '__get_tpl_contents')
                                , file_get_contents($path)
                        )
                    );

                    if(substr($template, 0, 3) === '<?=')
                        $template = '/**/$___='.substr($template, 3);
                    elseif(substr($template, 0, 5) !== '<?php')
                        $template = '$___=\''.$template;

                    if(substr($template, -2) !== '?>')
                        $template .= '\'';

                    $template = preg_replace(
                        array(
                             '/<\?=/msx'
                            , '/<\?php/msx'
                            , '/\?\>/msx'
                        )
                        , array(
                             '\';$___.='
                            , '\';'
                            , ';$___.=\''
                        )
                        ,$template
                    ).';';

                    $GLOBALS[$this->templateNames[$name]] = $template;
                    unset($path);
                break;
                case self::templateTypeSubphp:
                    $GLOBALS[$this->templateNames[$name]] = preg_replace_callback(
                        self::reTemplateInclude
                        , array($this, '__get_tpl_contents')
                        , file_get_contents($path)
                    );

                    unset($path);
                break;
                default:
            }

            if(!defined('VARIABLE_STREAM'))
                include(__DIR__.'/../Include/VariableStream.php');
        }

        extract($environment, EXTR_OVERWRITE);
        if(!isset($environment['environment']))
            unset($environment);

        switch($this->templateType) {
            case self::templateTypeSubphpUnstable:
                eval($GLOBALS[$this->templateNames[$name]]);
                return $___;
            break;
            case self::templateTypeSubphp:
                ob_start();
                include 'var://'.$this->templateNames[$name];
                return ob_get_clean();
            break;
            default:
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

    public function addSitemap() {
        if(!isset($this->files[$this->sitemapIndex]))
            throw new \Exception("Cannot add sitemap when I don't have one opened yet");

        $sitemaps = func_get_args();

        if(!($c=count($sitemaps)))
            $sitemaps = array($this->currentFile);
        elseif($c==1&&  is_array($sitemaps[0]))
            $sitemaps = $sitemaps[0];

        $buff = 3072 + abs($this->sitemapindexendSeek);

        foreach($sitemaps as &$sitemap) {

            if(!in_array($sitemap, $this->sitemaps)) {
                if(file_exists($this->genFileName($sitemap))) {
                    $this->seekFileToLastEntry($this->sitemapIndex);

                    fwrite(
                            $this->files[$this->sitemapIndex]
                            , $this->templating->render(
                                    'BuccioniSitemapBundle:Sitemap:sitemapindex-sitemap.xml.php'
                                    , array(
                                        'path' => $this->genSitemapPath($sitemap)
                                    )
                            ).$this->sitemapindexend
                            , $buff
                    );

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

        $this->seekFileToLastEntry($this->sitemapIndex);

        return $this;
    }

    public function removeSitemap($name, $index=null, $enums=false) {
        if(is_null($index)) {
            if(empty($this->sitemapIndex))
                throw new \Exception('Cannot remove sitemap without a specific name or if not have opened index files.');
            else
                $index = $this->sitemapIndex;
        }

        if(!is_array($name))
            $names = array($name);

        if($enums)
            $names = $this->getNameWithEnums($name);

        foreach($names as &$name) {
            $sitemap_offset = array_search($name, $this->sitemaps);

            if($sitemap_offset !== false) {
                if(!isset($this->files[$index]))
                    $this->openFile($index);

                fseek($this->files[$index], 0);

                while(!is_null($sitemapUrl=self::_findXMLElem($this->files[$index], self::XMLElementLoc, true, null, self::_findXMLModeSeekAtStartIter))) {
                    if(preg_match(sprintf(self::reNameEnunm, $name), $this->genNameFromPath($sitemapUrl))) {
                        $xmlstr=self::_findXMLElem($this->files[$index], self::XMLElementSitemap, true, null, self::_findXMLModeRevSeekAtStart);

                        $this->_deleteFileChunk(
                                    $this->genFileName($index)
                                    , $this->files[$index]
                                    , (strlen($xmlstr) + ((strlen(self::XMLElementSitemap) * 2) + 5))
                        );

                        (unset) array_splice($this->sitemaps, $sitemap_offset, 1);
                    }
                }
            }
        }

        return $this;
    }

    private function _deleteFileChunk($filePath, $fpw, $rseekOffset=0) {
        flock($fpw, LOCK_EX);

        $fpr=fopen($filePath, 'r');
        fseek($fpr, ftell($fpw) + $rseekOffset, SEEK_SET);

        while(fgetc($fpr) !== '<')  ;

        fseek($fpr, -1, SEEK_CUR);

        while(!feof($fpr))
            fwrite($fpw, fread($fpr, 4096), 4096);

        fclose($fpr);

        ftruncate($fpw, ftell($fpw));
        flock($fpw, LOCK_UN);
    }

    public function addUrl(Url $url, $name=null, $addIfNotExists=false, $lastEnum=false)
    {
        if(is_null($name)) {
            $this->checkCurrentFile();
            $name = $this->currentFile;
        }

        if($lastEnum)
            $name = $this->currentEnumFile($name);

        if(!isset($this->files[$name]))
            $this->openFile($name, $addIfNotExists);

        if($this->filesCounter[$name]>=$this->limit) {
            $lastEnum = $this->currentEnumFile($name);

            if(
                $lastEnum !== $name
                && (
                    (!isset($this->filesCounter[$lastEnum]))
                    || $this->filesCounter[$lastEnum] < $this->limit
                )
            )
                $name = $lastEnum;
            else {
                $toIndex = array();

                if($this->swapSitemapFileName && empty($this->sitemapIndex)) {
                    $nextName = $this->nextEnumFile($name);

                    $this->defaultIndexFileName = $name;
                    $this->moveFile($name, $nextName);

                    $name = $nextName;
                }

                if(empty($this->sitemapIndex))
                    $this->openIndexFile(null, true);

                $oldFile  = $name;
                $name     = $this->nextEnumFile($name);

                $this
                    ->addFile($name)
                    ->addSitemap($oldFile, $name)
                ;
            }
        }

        $buff = 3072 + (count($url->getImages()) * 5120) + abs($this->sitemapendSeek);
        $this->seekFileToLastEntry($name);

            fwrite(
                $this->files[$name]
                , $this->templating->render(
                        'BuccioniSitemapBundle:Sitemap:sitemap-url.xml.php'
                        , compact('url')
                ).$this->sitemapend
                , $buff
        );

        ++$this->filesCounter[$name];

        return $this;
    }

    public function removeUrl($url, $names=null, $enums=false) {
        if($url instanceOf Url)
            $url = $url->getLoc();
        elseif(!is_string($url))
            throw new \Exception(__METHOD__.' - Argument url must be a string or an instance of '.get_class(new Url()));

        if(is_null($names)) {
            if(count($this->files))
                $names = array_keys($this->files);
            else
                throw new \Exception('Cannot remove an url without a specific names or if not have opened files.');
        } elseif(is_string($names))
            $names = array($names);

        if($enums)
            $names = $this->getNameWithEnums($names);

        foreach($names as &$name) {
            if(!isset($this->files[$name]))
                $this->openFile($name);

            fseek($this->files[$name], 0);

            if(!is_null(self::_findXMLElem($this->files[$name], self::XMLElementLoc, true, $url, self::_findXMLModeSeekAtStart))) {
                $xmlstr=self::_findXMLElem($this->files[$name], self::XMLElementSitemap, true, null, self::_findXMLModeRevSeekAtStart);

                $this->_deleteFileChunk(
                            $this->genFileName($name)
                            , $this->files[$name]
                            , (strlen($xmlstr) + ((strlen(self::XMLElementSitemap) * 2) + 5))
                );

                --$this->filesCounter[$name];
            }
        }

        return $this;
    }

    public function add(Url $url, $name=null, $addIfNotExists=false) {
        return $this->addUrl($url, $name, $addIfNotExists);
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