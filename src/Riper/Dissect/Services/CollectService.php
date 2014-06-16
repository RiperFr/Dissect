<?php
namespace Riper\Dissect\Services;

use Riper\Dissect\Factories\CodeCoverageFactory;
use Riper\Dissect\Factories\WriterFactory;
use SebastianBergmann\Exporter\Exception;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CollectService
{


    protected $databaseFile;
    protected $database;

    protected $coverageFiles;

    protected $newItemStatement;
    protected $newDirectoryStatement;
    protected $newMetricStatement;
    protected $newFileHasMetrictatement;
    protected $findDirByPath;


    public function __construct($databaseFile)
    {
        $this->databaseFile = $databaseFile;
    }

    public function setCoverageFile($coverageFile)
    {
        $this->coverageFiles = $coverageFile;
    }

    /**
     * @return \PHP_CodeCoverage
     */
    protected function getCoverage()
    {

        $my_coverage = include($this->coverageFiles);

        return $my_coverage;
    }


    private function initDatabase()
    {
        if (!file_exists($this->databaseFile)) {
            $creation = true;
        } else {
            $creation = false;
        }

        $pdo = new \PDO('sqlite:' . $this->databaseFile);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->setAttribute(
            \PDO::ATTR_ERRMODE,
            \PDO::ERRMODE_EXCEPTION
        ); // ERRMODE_WARNING | ERRMODE_EXCEPTION | ERRMODE_SILENT


        $pdo->query(
            "CREATE TABLE IF NOT EXISTS item (
                id            INTEGER      NOT NULL   ,
                extension varchar(10),
                path varchar(255),
                fqn varchar(255),
                filename varchar(255),
                type varchar(255),
                PRIMARY KEY (id)
            );"
        );


        $pdo->query(
            "CREATE TABLE IF NOT EXISTS metric (
                id            INTEGER ,
                type varchar(255),
                value REAL ,
                PRIMARY KEY (id)
            );"
        );


        $pdo->query(
            "CREATE TABLE IF NOT EXISTS item_has_metric (
                id_item   INTEGER    NOT NULL,
                id_metric integer NOT NULL,
                 PRIMARY KEY (id_item,id_metric)
            );"
        );

        $this->database = $pdo;


        $this->newItemStatement = $pdo->prepare(
            "INSERT INTO item (id,filename,extension,path,fqn,type)
              VALUES (:id,:filename,:extension,:path,:fqn,:type)"
        );


        $this->newMetricStatement = $pdo->prepare(
            "INSERT INTO metric (id,type,value)
              VALUES (:id,:type,:value)"
        );
        $this->newFileHasMetrictatement = $pdo->prepare(
            "INSERT INTO item_has_metric (id_item,id_metric)
              VALUES (:id_item,:id_metric)"
        );
    }


    public function process()
    {
        $this->initDatabase();


        $coverage = $this->getCoverage();
        $reports = $coverage->getReport();


        foreach ($reports as $report) {
            if ($report instanceof \PHP_CodeCoverage_Report_Node_Directory) {
                foreach ($report->getDirectories() as $item) {
                    $this->addItem($item);
                }

                foreach ($report->getFiles() as $item) {
                    $this->addItem($item);
                }
            }
        }
    }

    protected function addItem(\PHP_CodeCoverage_Report_Node $item)
    {
        $data = array(
            'numClasses'           => $item->getNumClassesAndTraits(),
            'numTestedClasses'     => $item->getNumTestedClassesAndTraits(),
            'numMethods'           => $item->getNumMethods(),
            'numTestedMethods'     => $item->getNumTestedMethods(),
            'linesExecutedPercent' => $item->getLineExecutedPercent(false),
            'numExecutedLines'     => $item->getNumExecutedLines(),
            'numExecutableLines'   => $item->getNumExecutableLines(),
            'testedMethodsPercent' => $item->getTestedMethodsPercent(false),
            'testedClassesPercent' => $item->getTestedClassesAndTraitsPercent(false),
        );

        static $id_file = 0;
        static $id_metric = 0;

        if ($item instanceof \PHP_CodeCoverage_Report_Node_Directory) {
            echo '+';
            $this->newItemStatement->execute(
                array(
                    'id'        => $id_file,
                    'path'      => $item->getId(),
                    'fqn'       => null,
                    'type'      => 'dir',
                    'extension' => null
                )
            );
            $id_file++;
        } else {
            echo '.';
            $info = pathinfo($item->getId());
            $this->newItemStatement->execute(
                array(
                    'id'        => $id_file,
                    'path'      => $info['dirname'],
                    'filename'  => $info['filename'],
                    'extension' => $info['extension'],
                    'type'      => 'file',
                    'fqn'       => null,
                )
            );

            $id_file++;
        }
        foreach ($data as $key => $value) {
            $this->newMetricStatement->execute(
                array(
                    'id'    => $id_metric,
                    'type'  => $key,
                    'value' => $value
                )
            );
            $this->newFileHasMetrictatement->execute(
                array(
                    'id_item'   => $id_file,
                    'id_metric' => $id_metric
                )
            );
            $id_metric++;
        }
    }


}
