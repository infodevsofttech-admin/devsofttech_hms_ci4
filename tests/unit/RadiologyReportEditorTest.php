<?php

namespace Tests\Unit;

use App\Controllers\Diagnosis;
use PHPUnit\Framework\TestCase;

final class RadiologyReportEditorTest extends TestCase
{
    private function buildController(array $templates = [], array $hcItems = [], array $invoiceItem = null, array $files = []): Diagnosis
    {
        $controller = new class extends Diagnosis {
            public function __construct()
            {
            }

            public function setDbStub(object $db): void
            {
                $this->db = $db;
            }

            public function exposeGetRadiologyTemplatesForReport($reportRow): array
            {
                return $this->getRadiologyTemplatesForReport($reportRow);
            }

            public function exposeFindImagingFilesForRequest(int $invoiceId, int $labType, int $labReqId): array
            {
                return $this->findImagingFilesForRequest($invoiceId, $labType, $labReqId);
            }
        };

        $dbStub = new class($templates, $hcItems, $invoiceItem, $files) {
            private array $templates;
            private array $hcItems;
            private ?array $invoiceItem;
            private array $files;

            public function __construct(array $templates, array $hcItems, ?array $invoiceItem, array $files)
            {
                $this->templates = $templates;
                $this->hcItems = $hcItems;
                $this->invoiceItem = $invoiceItem;
                $this->files = $files;
            }

            public function tableExists(string $table): bool
            {
                return true;
            }

            public function getFieldNames(string $table): array
            {
                if ($table === 'file_upload_data') {
                    return ['id', 'charge_id', 'charge_type', 'repo_id', 'file_name', 'orig_name', 'full_path', 'isdelete'];
                }
                return [];
            }

            public function table(string $tableName): object
            {
                $self = $this;
                return new class($self, $tableName, $this->templates, $this->hcItems, $this->invoiceItem, $this->files) {
                    private $parent;
                    private string $table;
                    private array $templates;
                    private array $hcItems;
                    private ?array $invoiceItem;
                    private array $files;
                    private array $conditions = [];

                    public function __construct($parent, string $table, array $templates, array $hcItems, ?array $invoiceItem, array $files)
                    {
                        $this->parent = $parent;
                        $this->table = $table;
                        $this->templates = $templates;
                        $this->hcItems = $hcItems;
                        $this->invoiceItem = $invoiceItem;
                        $this->files = $files;
                    }

                    public function select($fields): self { return $this; }
                    public function where($field, $val = null): self
                    {
                        $this->conditions[$field] = $val;
                        return $this;
                    }
                    public function groupStart(): self { return $this; }
                    public function orGroupStart(): self { return $this; }
                    public function groupEnd(): self { return $this; }
                    public function orWhere($field, $val = null): self { return $this; }
                    public function orderBy($col, $dir = 'ASC'): self { return $this; }

                    public function get($limit = null): object
                    {
                        $data = [];
                        if ($this->table === 'radiology_ultrasound_template') {
                            $data = array_map(fn($r) => (object) $r, $this->templates);
                        } elseif ($this->table === 'hc_items') {
                            $data = array_map(fn($r) => (object) $r, $this->hcItems);
                        } elseif ($this->table === 'invoice_item') {
                            $data = $this->invoiceItem ? [(object) $this->invoiceItem] : [];
                        } elseif ($this->table === 'file_upload_data') {
                            $data = $this->files;
                        }

                        return new class($data) {
                            private array $rows;
                            public function __construct(array $rows) { $this->rows = $rows; }
                            public function getResult(): array { return $this->rows; }
                            public function getResultArray(): array { return $this->rows; }
                            public function getRow(): ?object { return $this->rows[0] ?? null; }
                            public function getRowArray(): ?array { return $this->rows[0] ?? null; }
                        };
                    }
                };
            }
        };

        $controller->setDbStub($dbStub);
        return $controller;
    }

    public function testRelatedChestTemplatesRankedTop(): void
    {
        $templates = [
            [
                'id' => 19,
                'template_name' => 'X-RAY CHEST PA VIEW (Normal)',
                'title' => 'X-Ray Chest PA View',
                'keywords' => 'chest, pa view, lungs, heart, normal',
                'impression_cat' => 0,
                'charge_id' => 3930,
                'Modality' => 3,
            ],
            [
                'id' => 20,
                'template_name' => 'X-RAY CHEST PA VIEW - Pneumonia / Consolidation',
                'title' => 'X-Ray Chest PA View - Pneumonia',
                'keywords' => 'chest, pneumonia, consolidation, fever, cough',
                'impression_cat' => 1,
                'charge_id' => 3930,
                'Modality' => 3,
            ],
            [
                'id' => 21,
                'template_name' => 'X-RAY BOTH KNEE JOINTS AP & LATERAL - Osteoarthritis',
                'title' => 'X-Ray Both Knee Joints AP and Lateral View',
                'keywords' => 'knee, osteoarthritis',
                'impression_cat' => 1,
                'charge_id' => 3932,
                'Modality' => 3,
            ],
            [
                'id' => 23,
                'template_name' => 'X-RAY CERVICAL SPINE AP & LATERAL (Normal / Spondylosis)',
                'title' => 'X-Ray Cervical Spine AP & Lateral View',
                'keywords' => 'cervical, neck pain',
                'impression_cat' => 1,
                'charge_id' => 3934,
                'Modality' => 3,
            ],
        ];

        $hcItems = [
            ['id' => 42, 'itype' => 3, 'idesc' => 'CHEST PA VIEW'],
            ['id' => 3930, 'itype' => 3, 'idesc' => 'X-RAY CHEST PA VIEW'],
            ['id' => 3932, 'itype' => 3, 'idesc' => 'X-RAY BOTH KNEE JOINTS AP & LATERAL'],
        ];

        $invoiceItem = [
            'item_id' => 42,
            'item_name' => 'CHEST PA VIEW',
        ];

        $controller = $this->buildController($templates, $hcItems, $invoiceItem);

        $reportRow = (object) [
            'id' => 51450,
            'charge_id' => 40291,
            'charge_item_id' => 83079,
            'lab_type' => 3,
            'report_name' => 'CHEST PA VIEW',
        ];

        $results = $controller->exposeGetRadiologyTemplatesForReport($reportRow);

        $this->assertNotEmpty($results);
        $this->assertCount(4, $results);

        // Top 2 templates must be Chest PA View (Normal) and Pneumonia
        $topTemplate1 = $results[0];
        $topTemplate2 = $results[1];

        $this->assertTrue($topTemplate1->is_related);
        $this->assertTrue($topTemplate2->is_related);
        $this->assertContains($topTemplate1->id, [19, 20]);
        $this->assertContains($topTemplate2->id, [19, 20]);

        // Knee and Cervical spine must have lower score and not be related
        $this->assertFalse($results[2]->is_related);
        $this->assertFalse($results[3]->is_related);
    }

    public function testFindImagingFilesReturnsDicomFile(): void
    {
        $files = [
            [
                'id' => 43143,
                'charge_id' => 40291,
                'charge_type' => 3,
                'repo_id' => 51450,
                'file_name' => 'diag_20261001_113444_955045f6.dcm',
                'orig_name' => 'instance-0001.dcm',
                'full_path' => '/uploads/diagnosis/2026/10/diag_20261001_113444_955045f6.dcm',
                'file_type' => 'application/octet-stream',
                'isdelete' => 0,
            ],
        ];

        $controller = $this->buildController([], [], null, $files);

        $resultFiles = $controller->exposeFindImagingFilesForRequest(40291, 3, 51450);

        $this->assertCount(1, $resultFiles);
        $this->assertEquals(43143, $resultFiles[0]['id']);
        $this->assertEquals('instance-0001.dcm', $resultFiles[0]['orig_name']);
    }
}
