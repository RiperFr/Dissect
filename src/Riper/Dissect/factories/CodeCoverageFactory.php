<?php

namespace Ilius\Component\CoverageReportMerge;

class CodeCoverageFactory
{
    /**
     * @return \PHP_CodeCoverage a freshly created CodeCoverage object
     */
    public function getCodeCoverage()
    {
        return new \PHP_CodeCoverage();
    }
}