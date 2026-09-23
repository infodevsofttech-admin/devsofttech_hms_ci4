<section class="content">
    <div class="pagetitle mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-0 text-primary fs-4"><i class="bi bi-hospital me-2"></i>Admitted IPD Patient List &amp; Nursing Map</h1>
            <small class="text-muted">Real-time bed management, nursing station assignments, and patient workspaces</small>
        </div>
        <div class="btn-group btn-group-sm" role="group" aria-label="View Switch">
            <button type="button" class="btn btn-primary active" id="btn_view_grid" onclick="switchNursingView('grid')">
                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Visual Bed Grid
            </button>
            <button type="button" class="btn btn-outline-primary" id="btn_view_table" onclick="switchNursingView('table')">
                <i class="bi bi-table me-1"></i> Table View
            </button>
        </div>
    </div>

    <!-- Top KPI Stats Row (Matching Image 2 Style) -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-sm-4 col-md-2">
            <div class="card border-0 shadow-sm text-center py-2 bg-dark text-white rounded-3">
                <div class="fs-4 fw-bold mb-0"><?= (int)($stats['total_beds'] ?? 0) ?></div>
                <div class="small opacity-75">Total Beds</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <div class="card border-0 shadow-sm text-center py-2 bg-success-subtle text-success rounded-3 border border-success-subtle">
                <div class="fs-4 fw-bold mb-0"><?= (int)($stats['available'] ?? 0) ?></div>
                <div class="small text-success-emphasis">Available</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <div class="card border-0 shadow-sm text-center py-2 bg-danger-subtle text-danger rounded-3 border border-danger-subtle">
                <div class="fs-4 fw-bold mb-0"><?= (int)($stats['occupied'] ?? 0) ?></div>
                <div class="small text-danger-emphasis">Occupied</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <div class="card border-0 shadow-sm text-center py-2 bg-warning-subtle text-warning-emphasis rounded-3 border border-warning-subtle">
                <div class="fs-4 fw-bold mb-0"><?= (int)($stats['reserved'] ?? 0) ?></div>
                <div class="small">Reserved</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <div class="card border-0 shadow-sm text-center py-2 bg-primary-subtle text-primary rounded-3 border border-primary-subtle">
                <div class="fs-4 fw-bold mb-0"><?= (int)($stats['nursing_stations'] ?? 0) ?></div>
                <div class="small">Nursing Stations</div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <div class="card border-0 shadow-sm text-center py-2 bg-info-subtle text-info-emphasis rounded-3 border border-info-subtle">
                <div class="fs-4 fw-bold mb-0"><?= (int)($stats['total_admitted'] ?? 0) ?></div>
                <div class="small fw-semibold">Admitted Patients</div>
                <div class="d-flex justify-content-center gap-1 mt-1 flex-wrap" style="font-size:10px;">
                    <span class="badge bg-primary px-1" title="Regular Inpatient (IPD)">IPD: <?= (int)($stats['count_ipd'] ?? 0) ?></span>
                    <span class="badge bg-warning text-dark px-1 border border-warning-subtle" title="Day Care Unit (< 24h)">Day Care: <?= (int)($stats['count_daycare'] ?? 0) ?></span>
                    <span class="badge bg-danger px-1" title="Emergency / Casualty">Cas: <?= (int)($stats['count_emergency'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-12 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="nursing_search_input" placeholder="Search patient, IPD code, bed..." onkeyup="filterNursingBeds()">
                        <button class="btn btn-outline-secondary" type="button" onclick="$('#nursing_search_input').val(''); filterNursingBeds();">&times;</button>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <select class="form-select form-select-sm" id="filter_admission_type" onchange="onAdmissionTypeDropdownChange()">
                        <option value="">All Admission Types</option>
                        <option value="ipd">Regular IPD (<?= (int)($stats['count_ipd'] ?? 0) ?>)</option>
                        <option value="daycare">Day Care Unit (<?= (int)($stats['count_daycare'] ?? 0) ?>)</option>
                        <option value="emergency">Emergency / Casualty (<?= (int)($stats['count_emergency'] ?? 0) ?>)</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <select class="form-select form-select-sm" id="filter_nursing_station" onchange="filterNursingBeds()">
                        <option value="">All Nursing Stations</option>
                        <?php foreach (($nursingStations ?? []) as $st): ?>
                            <option value="<?= esc($st['station_name']) ?>"><?= esc($st['station_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-2">
                    <select class="form-select form-select-sm" id="filter_nursing_ward" onchange="filterNursingBeds()">
                        <option value="">All Wards</option>
                        <?php foreach (($wards ?? []) as $wId => $wName): ?>
                            <option value="<?= esc($wName) ?>"><?= esc($wName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-md-3 col-lg-3 text-end">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Status Filters">
                        <button type="button" class="btn btn-outline-secondary active btn-status-filter" data-status="all" onclick="setStatusFilter('all', this)">All</button>
                        <button type="button" class="btn btn-outline-success btn-status-filter" data-status="available" onclick="setStatusFilter('available', this)">Free</button>
                        <button type="button" class="btn btn-outline-danger btn-status-filter" data-status="occupied" onclick="setStatusFilter('occupied', this)">Busy</button>
                    </div>
                </div>
            </div>

            <!-- Quick Filter by Admission Type Row -->
            <div class="d-flex align-items-center gap-2 flex-wrap pt-2 border-top mt-2">
                <span class="small fw-bold text-muted text-uppercase" style="font-size:11px; letter-spacing:0.5px;">Quick Filter by Type:</span>
                <div class="btn-group btn-group-sm" role="group" aria-label="Admission Type Quick Filters">
                    <button type="button" class="btn btn-secondary btn-sm active btn-type-filter" data-type="all" onclick="setTypeFilter('all', this)">
                        All (<?= (int)($stats['total_admitted'] ?? 0) ?>)
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm btn-type-filter" data-type="ipd" onclick="setTypeFilter('ipd', this)">
                        <i class="bi bi-hospital me-1"></i>Regular IPD (<?= (int)($stats['count_ipd'] ?? 0) ?>)
                    </button>
                    <button type="button" class="btn btn-outline-warning text-dark btn-sm btn-type-filter" data-type="daycare" onclick="setTypeFilter('daycare', this)">
                        <i class="bi bi-clock-history me-1"></i>Day Care (< 24h) (<?= (int)($stats['count_daycare'] ?? 0) ?>)
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm btn-type-filter" data-type="emergency" onclick="setTypeFilter('emergency', this)">
                        <i class="bi bi-ambulance me-1"></i>Emergency / Casualty (<?= (int)($stats['count_emergency'] ?? 0) ?>)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Visual Bed Grid View Container (Image 2 Style) -->
    <div id="view_grid_container">
        <?php if (empty($floors)): ?>
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-hospital fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    <h5>No active beds or wards configured</h5>
                    <p class="mb-0 small">Please setup Wards and Beds in Admin Panel &gt; Bed Management.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($floors as $floorName => $wardGroup): ?>
                <?php
                    $floorBeds = 0; $floorFree = 0; $floorBusy = 0;
                    foreach ($wardGroup as $w) {
                        $floorBeds += $w['count']['total'];
                        $floorFree += $w['count']['available'];
                        $floorBusy += $w['count']['occupied'];
                    }
                ?>
                <!-- Floor Header Banner -->
                <div class="bg-dark text-white px-3 py-2 rounded-3 mb-3 d-flex align-items-center justify-content-between shadow-sm">
                    <div class="fw-bold fs-6">
                        <i class="bi bi-layers me-2 text-info"></i><?= esc($floorName) ?>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark me-1"><?= $floorBeds ?> beds</span>
                        <span class="badge bg-success me-1"><?= $floorFree ?> free</span>
                        <span class="badge bg-danger"><?= $floorBusy ?> busy</span>
                    </div>
                </div>

                <!-- Wards Grid Under Floor -->
                <?php foreach ($wardGroup as $wId => $wardInfo): ?>
                    <div class="card shadow-sm border-0 mb-4 ward-block-card" data-ward="<?= esc($wardInfo['ward_name']) ?>" data-station="<?= esc($wardInfo['station_name']) ?>">
                        <div class="card-header bg-light py-2 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-diagram-3 text-primary fs-5"></i>
                                <strong class="text-primary fs-6 mb-0"><?= esc($wardInfo['ward_name']) ?></strong>
                                <?php if (!empty($wardInfo['ward_type'])): ?>
                                    <span class="badge bg-secondary-subtle text-dark border me-2"><?= esc($wardInfo['ward_type']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($wardInfo['station_name']) && $wardInfo['station_name'] !== 'Unassigned Station'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-hospital me-1"></i>Station: <?= esc($wardInfo['station_name']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle me-1"><?= (int)$wardInfo['count']['available'] ?> Free</span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-1"><?= (int)$wardInfo['count']['occupied'] ?> Occupied</span>
                                <span class="badge bg-secondary"><?= (int)$wardInfo['count']['total'] ?> Total</span>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <?php foreach ($wardInfo['beds'] as $bed): ?>
                                    <?php
                                        $isOccupied = ($bed['bed_status'] === 'occupied');
                                        $cardClass = 'border-success-subtle bg-success-subtle bg-opacity-25';
                                        $headerColor = 'text-success';
                                        $statusBadgeClass = 'bg-success';
                                        $statusLabel = 'Available';

                                        if ($isOccupied) {
                                            $cardClass = 'border-danger-subtle bg-danger-subtle bg-opacity-25';
                                            $headerColor = 'text-danger';
                                            $statusBadgeClass = 'bg-danger';
                                            $statusLabel = 'Occupied';
                                        } elseif ($bed['bed_status'] === 'reserved') {
                                            $cardClass = 'border-warning-subtle bg-warning-subtle bg-opacity-25';
                                            $headerColor = 'text-warning-emphasis';
                                            $statusBadgeClass = 'bg-warning text-dark';
                                            $statusLabel = 'Reserved';
                                        } elseif (strpos($bed['bed_status'], 'maint') !== false || $bed['bed_status'] === 'cleaning') {
                                            $cardClass = 'border-secondary-subtle bg-secondary-subtle bg-opacity-25';
                                            $headerColor = 'text-secondary';
                                            $statusBadgeClass = 'bg-secondary';
                                            $statusLabel = ucfirst($bed['bed_status']);
                                        }

                                        $admType = strtolower(trim((string)($bed['admission_type'] ?? 'ipd')));
                                        $admTypeLabel = ($admType === 'emergency') ? 'emergency casualty' : (($admType === 'daycare') ? 'daycare day care' : 'ipd inpatient regular');
                                        $searchText = strtolower($bed['bed_number'] . ' ' . $bed['bed_code'] . ' ' . $bed['patient_name'] . ' ' . $bed['doctor_name'] . ' ' . $bed['ipd_code'] . ' ' . $wardInfo['ward_name'] . ' ' . $bed['station_name'] . ' ' . $admTypeLabel);
                                    ?>
                                    <div class="col-6 col-sm-4 col-md-3 col-lg-2 bed-card-col" 
                                         data-status="<?= esc($bed['bed_status']) ?>" 
                                         data-admission-type="<?= esc($admType) ?>" 
                                         data-ward="<?= esc($wardInfo['ward_name']) ?>" 
                                         data-station="<?= esc($bed['station_name']) ?>" 
                                         data-search="<?= esc($searchText) ?>">
                                        <div class="card h-100 border rounded-3 p-2 text-center transition-all <?= $cardClass ?>">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-bold small <?= $headerColor ?>"><?= esc($bed['bed_number']) ?></span>
                                                <span class="badge <?= $statusBadgeClass ?> font-monospace" style="font-size: 10px;"><?= $statusLabel ?></span>
                                            </div>

                                            <?php if ($isOccupied): ?>
                                                <div class="my-1">
                                                    <div class="fw-bold text-dark text-truncate small" title="<?= esc($bed['patient_name']) ?>">
                                                        <i class="bi bi-person-fill text-danger me-1"></i><?= esc($bed['patient_name']) ?>
                                                    </div>
                                                    <?php if (!empty($bed['doctor_name'])): ?>
                                                        <div class="text-muted text-truncate" style="font-size: 11px;" title="<?= esc($bed['doctor_name']) ?>">
                                                            <?= esc($bed['doctor_name']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="d-flex justify-content-center align-items-center gap-1 flex-wrap mt-1">
                                                        <?php if ($bed['days_admitted'] !== null): ?>
                                                            <span class="badge bg-light text-dark border border-secondary-subtle" style="font-size: 10px;">
                                                                <i class="bi bi-clock-history me-1"></i><?= $bed['days_admitted'] ?>d
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($admType === 'emergency'): ?>
                                                            <span class="badge bg-danger" style="font-size: 10px;" title="Emergency / Casualty">
                                                                <i class="bi bi-ambulance me-1"></i>Casualty
                                                            </span>
                                                        <?php elseif ($admType === 'daycare'): ?>
                                                            <span class="badge bg-warning text-dark border border-warning-subtle" style="font-size: 10px;" title="Day Care Unit (< 24h)">
                                                                <i class="bi bi-clock-history me-1"></i>Day Care
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 10px;" title="Regular Inpatient (IPD)">
                                                                <i class="bi bi-hospital me-1"></i>IPD
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="my-2 text-success small fw-semibold">
                                                    <i class="bi bi-check-circle me-1"></i>Ready
                                                </div>
                                            <?php endif; ?>

                                            <!-- Features Badges -->
                                            <div class="d-flex justify-content-center gap-1 my-1">
                                                <?php if ($bed['has_oxygen']): ?><span class="badge bg-white text-dark border" style="font-size:9px;" title="Oxygen Supply">O<sub>2</sub></span><?php endif; ?>
                                                <?php if ($bed['has_monitor']): ?><span class="badge bg-white text-dark border" style="font-size:9px;" title="Cardiac Monitor">MON</span><?php endif; ?>
                                                <?php if ($bed['has_ventilator']): ?><span class="badge bg-white text-dark border" style="font-size:9px;" title="Ventilator">VENT</span><?php endif; ?>
                                            </div>

                                            <!-- Action Button -->
                                            <div class="mt-auto pt-1">
                                                <?php if ($isOccupied && $bed['ipd_id'] > 0): ?>
                                                    <button type="button" class="btn btn-primary btn-sm w-100 py-0 text-nowrap" style="font-size:11px;" onclick="load_form('<?= base_url('ipd/patient/workspace/' . (int)$bed['ipd_id']) ?>','Nursing Workspace');">
                                                        Open Workspace
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 py-0 text-nowrap disabled" style="font-size:11px;">
                                                        Vacant Bed
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Table View Container (Image 1 Style) -->
    <div id="view_table_container" class="d-none">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 text-primary fw-bold"><i class="bi bi-list-task me-1"></i> Admitted IPD Patient List</h6>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-striped table-hover align-middle mb-0" id="ipdNursingListTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>IPD Code</th>
                            <th>UHID</th>
                            <th>Patient Name</th>
                            <th>Type</th>
                            <th>Bed &amp; Ward Location</th>
                            <th>Doctor</th>
                            <th>Admit Date</th>
                            <th>Days</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="bi bi-person-x fs-3 d-block mb-1"></i> No current IPD admissions found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $idx => $row) : ?>
                            <?php $rowType = strtolower(trim((string) ($row->admission_type ?? 'ipd'))); ?>
                            <tr class="table-row-admission" data-admission-type="<?= esc($rowType) ?>">
                                <td class="fw-bold text-secondary ps-3"><?= $idx + 1 ?></td>
                                <td><span class="badge bg-light text-dark border"><?= esc($row->ipd_code ?? '') ?></span></td>
                                <td><code><?= esc($row->p_code ?? '') ?></code></td>
                                <td class="fw-semibold text-primary"><?= esc(trim((string) (($row->p_fname ?? '') . ' ' . ($row->p_rname ?? '')))) ?></td>
                                <td>
                                    <?php if ($rowType === 'emergency'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-ambulance me-1"></i>Casualty</span>
                                    <?php elseif ($rowType === 'daycare'): ?>
                                        <span class="badge bg-warning text-dark border border-warning-subtle"><i class="bi bi-clock-history me-1"></i>Day Care</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary"><i class="bi bi-hospital me-1"></i>Regular IPD</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                        <i class="bi bi-hospital me-1"></i><?= esc($row->Bed_Desc ?? '') ?>
                                    </span>
                                </td>
                                <td><?= esc($row->doc_name ?? '') ?></td>
                                <td><?= esc($row->str_register_date ?? '') ?></td>
                                <td><span class="badge bg-secondary"><?= esc($row->no_days ?? '0') ?> days</span></td>
                                <td class="text-end pe-3">
                                    <button type="button" class="btn btn-primary btn-sm py-1 px-3" onclick="load_form('<?= base_url('ipd/patient/workspace/' . (int) ($row->id ?? 0)) ?>','Nursing Workspace');">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> Open Workspace
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
var currentStatusFilter = 'all';
var currentTypeFilter = 'all';

function switchNursingView(mode) {
    if (mode === 'grid') {
        $('#view_grid_container').removeClass('d-none');
        $('#view_table_container').addClass('d-none');
        $('#btn_view_grid').addClass('btn-primary active').removeClass('btn-outline-primary');
        $('#btn_view_table').addClass('btn-outline-primary').removeClass('btn-primary active');
    } else {
        $('#view_grid_container').addClass('d-none');
        $('#view_table_container').removeClass('d-none');
        $('#btn_view_table').addClass('btn-primary active').removeClass('btn-outline-primary');
        $('#btn_view_grid').addClass('btn-outline-primary').removeClass('btn-primary active');
        if (window.jQuery && $.fn.DataTable && !$.fn.DataTable.isDataTable('#ipdNursingListTable')) {
            $('#ipdNursingListTable').DataTable();
        }
    }
}

function setStatusFilter(status, btn) {
    currentStatusFilter = status;
    $('.btn-status-filter').removeClass('active btn-secondary btn-success btn-danger').addClass('btn-outline-secondary');
    if (status === 'available') {
        $(btn).removeClass('btn-outline-secondary').addClass('active btn-success');
    } else if (status === 'occupied') {
        $(btn).removeClass('btn-outline-secondary').addClass('active btn-danger');
    } else {
        $(btn).removeClass('btn-outline-secondary').addClass('active btn-secondary');
    }
    filterNursingBeds();
}

function setTypeFilter(type, btn) {
    currentTypeFilter = type;
    $('#filter_admission_type').val(type === 'all' ? '' : type);
    $('.btn-type-filter').removeClass('active btn-secondary btn-primary btn-warning text-dark btn-danger').addClass('btn-outline-secondary');
    if (type === 'ipd') {
        $(btn).removeClass('btn-outline-secondary').addClass('active btn-primary');
    } else if (type === 'daycare') {
        $(btn).removeClass('btn-outline-secondary').addClass('active btn-warning text-dark');
    } else if (type === 'emergency') {
        $(btn).removeClass('btn-outline-secondary').addClass('active btn-danger');
    } else {
        $(btn).removeClass('btn-outline-secondary').addClass('active btn-secondary');
    }
    filterNursingBeds();
}

function onAdmissionTypeDropdownChange() {
    var val = $('#filter_admission_type').val() || 'all';
    currentTypeFilter = val;
    $('.btn-type-filter').removeClass('active btn-secondary btn-primary btn-warning text-dark btn-danger').addClass('btn-outline-secondary');
    if (val === 'ipd') {
        $('.btn-type-filter[data-type="ipd"]').removeClass('btn-outline-secondary').addClass('active btn-primary');
    } else if (val === 'daycare') {
        $('.btn-type-filter[data-type="daycare"]').removeClass('btn-outline-secondary').addClass('active btn-warning text-dark');
    } else if (val === 'emergency') {
        $('.btn-type-filter[data-type="emergency"]').removeClass('btn-outline-secondary').addClass('active btn-danger');
    } else {
        $('.btn-type-filter[data-type="all"]').removeClass('btn-outline-secondary').addClass('active btn-secondary');
    }
    filterNursingBeds();
}

function filterNursingBeds() {
    var search = ($('#nursing_search_input').val() || '').toLowerCase().trim();
    var station = ($('#filter_nursing_station').val() || '').toLowerCase().trim();
    var ward = ($('#filter_nursing_ward').val() || '').toLowerCase().trim();
    var admType = currentTypeFilter;

    // Filter Visual Bed Grid
    $('.bed-card-col').each(function() {
        var $col = $(this);
        var bStatus = ($col.data('status') || '').toLowerCase();
        var bAdmType = ($col.data('admission-type') || '').toLowerCase();
        var bWard = ($col.data('ward') || '').toLowerCase();
        var bStation = ($col.data('station') || '').toLowerCase();
        var bSearch = ($col.data('search') || '').toLowerCase();

        var matchSearch = (search === '' || bSearch.indexOf(search) !== -1);
        var matchStation = (station === '' || bStation.indexOf(station) !== -1);
        var matchWard = (ward === '' || bWard.indexOf(ward) !== -1);
        var matchStatus = (currentStatusFilter === 'all' || bStatus === currentStatusFilter);

        var matchAdmType = true;
        if (admType !== 'all' && admType !== '') {
            matchAdmType = (bStatus === 'occupied' && bAdmType === admType);
        }

        if (matchSearch && matchStation && matchWard && matchStatus && matchAdmType) {
            $col.removeClass('d-none');
        } else {
            $col.addClass('d-none');
        }
    });

    // Hide empty ward blocks
    $('.ward-block-card').each(function() {
        var $card = $(this);
        var visibleBeds = $card.find('.bed-card-col:not(.d-none)').length;
        if (visibleBeds === 0) {
            $card.addClass('d-none');
        } else {
            $card.removeClass('d-none');
        }
    });

    // Filter Table View rows
    $('#ipdNursingListTable tbody tr.table-row-admission').each(function() {
        var $tr = $(this);
        var rowType = ($tr.data('admission-type') || '').toLowerCase();
        var rowSearch = $tr.text().toLowerCase();

        var matchSearch = (search === '' || rowSearch.indexOf(search) !== -1);
        var matchType = (admType === 'all' || admType === '' || rowType === admType);

        if (matchSearch && matchType) {
            $tr.removeClass('d-none');
        } else {
            $tr.addClass('d-none');
        }
    });
}
</script>
