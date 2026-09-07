<?php
// We can just instantiate the FhirR4Builder or similar and run the validation
// But since CI4 isn't fully booted in CLI without spark, let's create a spark command or just boot it manually.
define('FCPATH', __DIR__ . '/../public/');
chdir(__DIR__ . '/../');
require 'system/bootstrap.php';
// Alternatively, since CI is already built to run via spark:
// We can create a Custom Command for Spark!
