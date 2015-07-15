<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use League\Csv\Writer;
use League\Csv\Reader;

class TransExtractor
{
    private $urlPath = null;

    private $iterator;

    private $logger;

    private $writer;

    private $logsDir = 'raports';

    private $pattern = "/__\((?:(?:\"(?:\\\\\"|[^\"])+\")|(?:'(?:\\\'|[^'])+'))/is";

    private $inputCsv = '';

    public function __construct()
    {
        $this->logger = new Logger('main');
        $this->logger->pushHandler(
            new \Monolog\Handler\StreamHandler($this->logsDir . DIRECTORY_SEPARATOR . 'raport.log',
                \Monolog\Logger::INFO)
        );
        $this->writer = \League\Csv\Writer::createFromFileObject(new \SplTempFileObject());
        $this->writer->setDelimiter("\t");
        $this->writer->setNewline("\r\n");
        $this->writer->setEncodingFrom("utf-8");
    }

    /**
     * @return mixed
     */
    public function getWriter()
    {
        return $this->writer;
    }

    /**
     * @param mixed $writer
     */
    public function setWriter($writer)
    {
        $this->writer = $writer;
    }

    /**
     * @return null
     */
    public function getUrlPath()
    {
        return $this->urlPath;
    }

    /**
     * @param null $urlPath
     */
    public function setUrlPath($urlPath)
    {
        if(!is_dir($urlPath)) {
            throw new \Exception('Selected dir not exists');
        }
        $this->urlPath = $urlPath;
    }

    /**
     * @return mixed
     */
    public static function getIterator($path)
    {
        $forbidderDirs = array(
            '.',
            '..',
            '.git',
            '.idea'
        );
        $directory = new \RecursiveDirectoryIterator( $path );
        $it = new \RecursiveIteratorIterator($directory);

        return $it;
    }

    public function process()
    {
        $resultCount = 0;
        $searchExtensions = array('php', 'phtml');
        $results = array();
        $filters = array();

        if(!empty($this->inputCsv)) {
            try {
                foreach($this->inputCsv->fetchAll() as $k=>$row) {
                    $filters[] = $row[0];
                }
            } catch (\Exception $e) {
                $this->logger->addWarning('Error during parsing input csv: '.$e->getMessage());
            }
        }

        foreach (self::getIterator($this->getUrlPath()) as $filename => $file) {

            if (in_array( strtolower( pathinfo($file, PATHINFO_EXTENSION) ), $searchExtensions)) {
                $content = file_get_contents($filename);
                $matches = array();
                preg_match_all($this->pattern, $content, $matches);

                foreach($matches as $matched) {
                    foreach($matched as $match) {
                        $_item = trim( str_replace("__(", '', $match) , "'\"");
                        if ( !in_array($_item, $results) && !in_array($_item, $filters)) {
                            $results[] = $_item;
                            $resultCount++;
                        }
                    }
                }
            }
        }
        $this->getWriter()->insertAll( $results );
        $this->getLogger()->addInfo($resultCount . ' matches fount');
        file_put_contents('trans/'.$this->generateName(),$this->getWriter());

        echo('<p class="alert alert-success">Znaleziono ' . $resultCount . ' wyników</p>');
    }

    /**
     * @return mixed
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function setInputCsv($filepath)
    {
        if(is_file($filepath)) {
            try {
                $this->inputCsv = \League\Csv\Reader::createFromPath($filepath);
                $this->inputCsv->setFlags(SplFileObject::READ_AHEAD|SplFileObject::SKIP_EMPTY);
            } catch (\Exception $e) {
                $this->logger->addWarning('input csv corrupted or not existing');
            }
        }
    }

    public function generateName()
    {
        $max = 0;
        foreach (self::getIterator('trans') as $filename => $file) {
            if(in_array( strtolower( pathinfo($file, PATHINFO_EXTENSION) ), ['csv'])) {
                $tst0 = explode('_',$filename);
                $tst1 = explode('.',$tst0[1]);
                $test = intval($tst1[0]);
                if ( $test > $max) {
                    $max = $test;
                }
            }
        }
        if ($max > 0) {
            return 'raport_' . ($max+1) . '.csv';
        } elseif (empty($test)) {
            return 1;
        }
    }

    public static function getFilelist() {
        $files = array();
        foreach (self::getIterator('trans') as $filename => $file) {
            if(in_array( strtolower( pathinfo($file, PATHINFO_EXTENSION) ), ['csv'])) {
                $files[] = $filename;
            }
        }
        return $files;
    }
}
