<?php

namespace App\Libraries\Abdm\Fhir;

use App\Libraries\Abdm\Fhir\Generators\DischargeFhirGenerator;
use App\Libraries\Abdm\Fhir\Generators\InvoiceFhirGenerator;
use App\Libraries\Abdm\Fhir\Generators\LabFhirGenerator;
use App\Libraries\Abdm\Fhir\Generators\OpdFhirGenerator;
use App\Libraries\Abdm\Fhir\Generators\RadiologyFhirGenerator;

class FhirGeneratorFactory
{
    public function opd(): OpdFhirGenerator
    {
        return new OpdFhirGenerator();
    }

    public function lab(): LabFhirGenerator
    {
        return new LabFhirGenerator();
    }

    public function radiology(): RadiologyFhirGenerator
    {
        return new RadiologyFhirGenerator();
    }

    public function discharge(): DischargeFhirGenerator
    {
        return new DischargeFhirGenerator();
    }

    public function invoice(): InvoiceFhirGenerator
    {
        return new InvoiceFhirGenerator();
    }
}
