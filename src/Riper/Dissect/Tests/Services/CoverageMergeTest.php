<?php

namespace Riper\Dissect\Tests;

use Riper\Dissect\Services\CoverageMergeService;

class CoverageMergeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CoverageMergeService ;
     */
    protected $CoverageReportMerge;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $WriterFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $HTMLWriterMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $CodeCoverageFactoryMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $CodeCoverageMock;


    private $directoryOutput;


    protected function setUp()
    {
        $this->WriterFactoryMock = $this->getMockBuilder('\Riper\Dissect\Factories\WriterFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->HTMLWriterMock = $this->getMockBuilder('\PHP_CodeCoverage_Report_HTML')
            ->disableOriginalConstructor()
            ->getMock();

        $this->CodeCoverageFactoryMock = $this->getMockBuilder(
            '\Riper\Dissect\Factories\CodeCoverageFactory'
        )   ->disableOriginalConstructor()
            ->getMock();

        $this->CodeCoverageMock = $this->getMockBuilder('\PHP_CodeCoverage')
            ->disableOriginalConstructor()
            ->getMock();


        $this->CoverageReportMerge = new CoverageMergeService(
            $this->WriterFactoryMock,
            $this->CodeCoverageFactoryMock
        );


        $lower     = 40;
        $higher    = 79;
        $generator = 'yellow Submarine';

        $this->directoryOutput = $this->tempdir();

        $this->CoverageReportMerge->setOutputHTMLReportFolder($this->directoryOutput);

        //Check that the factory is used to get the writer (not instantiated directly within the instance)
        $this->WriterFactoryMock
            ->expects($this->once())->method('getHTMLWriter')->with(
                $this->equalTo($lower),
                $this->equalTo($higher),
                $this->equalTo($generator)
            )->will($this->returnValue($this->HTMLWriterMock));

        //Check that the outputDir is the right one when calling the report generation
        $this->HTMLWriterMock
            ->expects($this->once())
            ->method('process')
            ->with(
                $this->anything(),
                $this->equalTo($this->directoryOutput)
            );

        //CodeCoverage should be called only once, for the final result
        $this->CodeCoverageFactoryMock
            ->expects($this->once())
            ->method('getCodeCoverage')
            ->will($this->returnValue($this->CodeCoverageMock));

        $this->CoverageReportMerge->setGeneratorName($generator);
        $this->CoverageReportMerge->setHighLowerBound($higher);
        $this->CoverageReportMerge->setLowUpperBound($lower);

    }


    /**
     * Generate a tmp directory
     *
     * @return string
     */
    protected function tempdir()
    {
        $tempfile = tempnam(sys_get_temp_dir(), '');
        if (file_exists($tempfile)) {
            unlink($tempfile);
        }
        mkdir($tempfile);
        if (is_dir($tempfile)) {
            return $tempfile;
        } else {
            return '/tmp/';
        }
    }


    public function testGenerateByManuallyAddingReports()
    {

        $this->CoverageReportMerge->addCoverageReport(__DIR__ . '/../assets/Controllers.cov');
        $this->CoverageReportMerge->addCoverageReport(__DIR__ . '/../assets/Factory.cov');

        //With two coverage report to merge, two call to the function "merge"
        $this->CodeCoverageMock->expects($this->exactly(2))->method('merge');

        $this->assertEquals(
            2,
            count($this->CoverageReportMerge->getCoverageReports()),
            'The number of reports is wrong regarding number of report given'
        );

        $this->CoverageReportMerge->generate();
    }

    public function testGenerateByFindingReportsInDirectoryRecursively()
    {

        $this->CoverageReportMerge->findCoverageReport(__DIR__ . '/../assets/');

        $this->CodeCoverageMock->expects($this->exactly(3))->method('merge');
        $this->assertEquals(
            3,
            count($this->CoverageReportMerge->getCoverageReports()),
            'The number of reports is wrong regarding number of report it should ' .
            'found in first & second level of directory'
        );

        $this->CoverageReportMerge->generate();
    }
}
