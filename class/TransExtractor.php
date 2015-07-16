<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use League\Csv\Writer;
use League\Csv\Reader;

class TransExtractor
{
    private $urlPath = null;

    private $secondUrlPath = null;

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
        $this->writer->setDelimiter(",");
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
     * @return string
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
     * @param null $urlPath
     */
    public function setSecondUrlPath($urlPath)
    {
        if(!empty($urlPath)) {
            if(!is_dir($urlPath)) {
                throw new \Exception('Selected dir not exists');
            }
            $this->secondUrlPath = $urlPath;
        }
    }

    /**
     * @return string
     */
    public function getSecondUrlPath()
    {
        return $this->secondUrlPath;
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

        $firstTry = $this->searchTree(self::getIterator($this->getUrlPath()), $results, $filters, $searchExtensions);
        if( !empty($firstTry)) {
            $results = array_merge($results,$firstTry['items']);
            $resultCount += $firstTry['count'];
        }

        if(!empty($this->getSecondUrlPath())) {
            $secondTry = $this->searchTree(self::getIterator($this->getSecondUrlPath()), $results, $filters, $searchExtensions);
            if( !empty($secondTry)) {
                $results = array_merge($results,$secondTry['items']);
                $resultCount += $secondTry['count'];
            }
        }

        $this->getWriter()->insertAll( $results );
        $filename = $this->generateName();
        file_put_contents('trans/'.$filename,$this->getWriter());

        //log
        $this->getLogger()->addInfo('Processing results: ' . $resultCount . ' saved at ' .$filename);
        $this->getLogger()->addDebug('Input filepath: ' . $this->getUrlPath());
        if(!empty($this->getSecondUrlPath())) {
            $this->getLogger()->addDebug('Additionally loaded: ' . $this->getSecondUrlPath());
        }
        $this->getLogger()->addDebug('Filters count: ' . count($filters));
        $this->getLogger()->addDebug('Result: ' . $resultCount . ' matches found');
        $this->getLogger()->addDebug('saved as ' . $filename);


        echo('<p class="alert alert-success">Znaleziono ' . $resultCount . ' wyników</p>');
    }

    private function searchTree($path, $results, $filters, $searchExtensions)
    {
        $return = array();
        $count = 0;
        foreach ($path as $filename => $file) {

            if (in_array( strtolower( pathinfo($file, PATHINFO_EXTENSION) ), $searchExtensions)) {
                $content = file_get_contents($filename);
                $matches = array();
                preg_match_all($this->pattern, $content, $matches);

                foreach($matches as $matched) {
                    foreach($matched as $match) {
                        $_item = trim( str_replace("__(", '', $match) , "'\"");
                        if ( !in_array($_item, $return) && !in_array($_item, $results) && !in_array($_item, $filters)) {
                            $return[] = array( $_item, ' ');
                            $count++;
                        }
                    }
                }
            }
        }

        return array('items' => $return, 'count' => $count);
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
        if ($max >= 0) {
            return 'raport_' . ($max+1) . '.csv';
        } elseif (empty($test)) {
            return 'raport_0.csv';
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
