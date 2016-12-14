<?php

namespace Dxw\Whippet\Services;

// Responsible for checking for the existence of dxw inspections
// associated with a dependency
class InspectionChecker
{
    public function __construct(
        \Dxw\Whippet\Services\InspectionsApi $inspections_api
    ) {
        $this->inspectionsApi = $inspections_api;
    }

    public function check($type, $dependency)
    {
        switch ($type) {
        case 'themes':
            return \Result\Result::ok('');
        case 'plugins':
            return $this->check_plugin($dependency);
        default:
            return \Result\Result::err("Unknown type '".$type."'");
        }
    }

    private function check_plugin($dependency)
    {
        $result = $this->inspectionsApi->get_inspections($dependency['name']);

        if ($result->isErr()) {
            return \Result\Result::err("Error fetching plugin inspections from API: '".$result->getErr()."'");
        }

        $inspections = $result->unwrap();
        if (empty($inspections)) {
            $warning_msg = <<<'EOT'
#############################################
#                                           #
#  WARNING: No inspections for this plugin  #
#                                           #
#############################################
EOT;
            return \Result\Result::ok($warning_msg);
        } else {
            return \Result\Result::ok($this->inspections_message($inspections));
        }
    }

    private function inspections_message($inspections)
    {
        $lines = [];
        $lines[] = "Inspections for this plugin:";
        foreach ($inspections as $inspection) {
            $lines[] = $this->format_inspection($inspection);
        }
        return implode("\n", $lines);
    }

    private function format_inspection($inspection)
    {
        $date = date_format($inspection->date, 'd/m/Y');
        return sprintf("* %s - %s - %s - %s", $date, $inspection->versions, $inspection->result, $inspection->url);
    }
}
