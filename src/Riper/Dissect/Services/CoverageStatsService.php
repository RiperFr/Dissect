<?php
namespace Riper\Dissect\Services;

use Riper\Dissect\Factories\CodeCoverageFactory;
use Riper\Dissect\Factories\WriterFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CoverageStatsService
{


    protected $coverageFile;
    protected $CSVReportFile;
    protected $metricToProcess;
    protected $filters = array();
    protected $groupBy = array();
    protected $reportedMetric ;

    public function setCSVReportFiles($CSVReportFile)
    {
        $this->CSVReportFile = $CSVReportFile;
    }

    public function setCoverageFile($coverageFile)
    {
        $this->coverageFile = $coverageFile;
    }

    public function setGroupBy($fieldName, $regex, array $catches)
    {
        $this->groupBy = array($fieldName, $regex, $catches);
    }

    public function setReportedMetric($metricName){
        $this->reportedMetric = $metricName ;
    }

    /**
     * @param $coverageFile
     *
     * @return \DOMDocument
     */
    protected function loadCoverage($coverageFile)
    {
        $dom = new \DOMDocument();
        $dom->load($coverageFile);


        return $dom;
    }


    public function setMetricToProcess($metricName)
    {
        $this->metricToProcess = $metricName;
    }

    public function generate()
    {
        $XMLCoverageReport = $this->loadCoverage($this->coverageFile);

        $classesStats = $this->getClassesStats($XMLCoverageReport);


        $resultStat = array();
        foreach ($classesStats as $classStat) {
            $group = $this->getClassStatGroup($classStat, $this->groupBy[0], $this->groupBy[1], $this->groupBy[2]);
            if ($group !== null) {
                if (!isset($resultStat[$group])) {
                    $resultStat[$group] = array();
                }
                $resultStat[$group][] = $classStat;
            } else {
                if (!isset($resultStat['other'])) {
                    $resultStat['other'] = array();
                }
                $resultStat['other'][] = $classStat;
            }
        }
        $finalStats = array();
        $metricNampe = $this->reportedMetric ? $this->reportedMetric : "coveredElementsPercent";
        foreach ($resultStat as $groupName => $stat) {
            $finalStats[$groupName] = $this->calculateAverage($stat, $metricNampe);
        }
        arsort($finalStats);

        return $finalStats ;
        /*
        //by Type
        $resultStat = array();
        foreach ($classesStats as $classStat) {
            $group = $this->getClassStatGroup($classStat, 'name', '#.*[a-z]([A-Z]+[a-z]*)$#', array(1));
            if ($group !== null) {
                if (!isset($resultStat[$group])) {
                    $resultStat[$group] = array();
                }
                $resultStat[$group][] = $classStat;
            } else {
                if (!isset($resultStat['other'])) {
                    $resultStat['other'] = array();
                }
                $resultStat['other'][] = $classStat;
            }
        }
        $finalStats = array();
        foreach ($resultStat as $groupName => $stat) {
            $finalStats[$groupName] = $this->calculateAverage($stat, 'coveredElementsPercent');
        }
        arsort($finalStats);

        print_r($finalStats);


        //by Bundle
        $resultStat = array();
        foreach ($classesStats as $classStat) {
            $group = $this->getClassStatGroup(
                $classStat,
                'namespace',
                '#([^\\\\]+Bundle)|Component\\\\([^\\\\]+)#',
                array(1, 2)
            );
            if ($group !== null) {
                if (!isset($resultStat[$group])) {
                    $resultStat[$group] = array();
                }
                $resultStat[$group][] = $classStat;
            } else {
                if (!isset($resultStat['other'])) {
                    $resultStat['other'] = array();
                }
                $resultStat['other'][] = $classStat;
            }
        }
        $finalStats = array();
        foreach ($resultStat as $groupName => $stat) {
            $finalStats[$groupName] = $this->calculateAverage($stat, 'coveredElementsPercent');
        }
        arsort($finalStats);

        print_r($finalStats);*/
    }

    public function calculateAverage($classesStats, $metricName)
    {
        $total = 0;
        $count = 0;
        foreach ($classesStats as $classStat) {
            $total = $total + $classStat['metrics'][$metricName];
            $count++;
        }

        return $total / $count;
    }

    public function getClassStatGroup($classStat, $field, $reg, $catch = array(1))
    {
        $fieldData = $classStat[$field];
        preg_match($reg, $fieldData, $matches);
        foreach ($catch as $potentialCatch) {
            if (isset($matches[$potentialCatch]) && trim($matches[$potentialCatch]) !== '') {
                return $matches[$potentialCatch];
            }
        }

        return null;
    }

    protected function getClassesStats(\DOMDocument $dom)
    {
        $xpath   = new \DOMXPath($dom);
        $classes = $xpath->query('//class'); //Search for all class nodes in the file
        $return  = array();
        foreach ($classes as $class) {
            $fileNode               = $class->parentNode; //The fileNode is parent of a class as a class is in a file
            $classData              = array();
            $classData['name']      = $class->getAttribute('name');
            $classData['namespace'] = $class->getAttribute('namespace');
            $classData['file']      = $fileNode->getAttribute('name');

            //Get all metrics of the class
            $metrics = $class->getElementsByTagName('metrics');

            $classData['metrics'] = array();
            foreach ($metrics as $metric) {
                $classData['metrics']['methods']           = $metric->getAttribute('methods');
                $classData['metrics']['coveredMethods']    = $metric->getAttribute('coveredmethods');
                $classData['metrics']['statements']        = $metric->getAttribute('statements');
                $classData['metrics']['coveredStatements'] = $metric->getAttribute('coveredstatements');
                $classData['metrics']['elements']          = $metric->getAttribute('elements');
                $classData['metrics']['coveredElements']   = $metric->getAttribute('coveredelements');

                $classData['metrics']['coveredElementsPercent']
                                                           = $classData['metrics']['elements'] != 0 ?
                    ($classData['metrics']['coveredElements'] / $classData['metrics']['elements']) * 100 : 100;

                $classData['metrics']['coveredMethodsPercent']
                                                           =  $classData['metrics']['methods'] != 0 ?
                    ($classData['metrics']['coveredMethods'] / $classData['metrics']['methods']) * 100 : 100;

                $classData['metrics']['coveredStatementsPercent']
                                                           =  $classData['metrics']['statements'] != 0 ?
                    ($classData['metrics']['coveredStatements'] / $classData['metrics']['statements']) * 100 : 100;
            }

            $methods              = $xpath->query(".//line[@type='method']", $fileNode);
            $classData['methods'] = array();
            foreach ($methods as $method) {
                $classData['methods'][$method->getAttribute('name')] = array(
                    'name'  => $method->getAttribute('name'),
                    'crap'  => $method->getAttribute('crap'),
                    'count' => $method->getAttribute('count'),
                    'line'  => $method->getAttribute('num'),
                );
            }
            $return[] = $classData;
        }

        return $return;
    }
}
