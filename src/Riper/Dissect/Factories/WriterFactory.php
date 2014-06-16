<?php

namespace Riper\Dissect\Factories;

class WriterFactory
{

    /**
     * Return a PhpUnitReportWriter that generate HTML
     *
     * @param int $lowUpperBound
     * @param int $highLowerBound
     * @param string $generator
     *
     * @return \PHP_CodeCoverage_Report_HTML
     */
    public function getHTMLWriter($lowUpperBound = 50, $highLowerBound = 90, $generator = '')
    {
        return new \PHP_CodeCoverage_Report_HTML($lowUpperBound, $highLowerBound, $generator);
    }

    /**
     * @return \PHP_CodeCoverage_Report_XML
     */
    public function getXMLWriter()
    {
        return new \PHP_CodeCoverage_Report_XML();
    }

    /**
     * @return \PHP_CodeCoverage_Report_Clover
     */
    public function getCloverXMLWriter()
    {
        return new \PHP_CodeCoverage_Report_Clover();
    }

    public function getPhpWriter()
    {
        return new \PHP_CodeCoverage_Report_PHP();
    }
}