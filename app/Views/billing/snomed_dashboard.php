<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>SNOMED Update Dashboard</strong>
                    <small class="text-muted">ABDM-ready monitor for release import and ICD mapping</small>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_snomed_refresh">Refresh Status</button>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('Opd_prescription/snomed_dashboard') ?>">Reload Page</a>
                    </div>

                    <div class="border rounded p-2 mb-3">
                        <div class="small fw-bold mb-2">Latest Release</div>
                        <div id="snomed_release_block" class="small text-muted">Loading...</div>
                    </div>

                    <div class="border rounded p-2 mb-3">
                        <div class="small fw-bold mb-2">Imported Table Counts</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" id="tbl_snomed_counts">
                                <thead>
                                    <tr>
                                        <th>Table</th>
                                        <th width="180">Rows</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="2" class="text-muted">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="border rounded p-2 mb-3">
                        <div class="small fw-bold mb-2">SNOMED to ICD Map Lookup</div>
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-md-4">
                                <label class="form-label mb-1">SNOMED Concept Id</label>
                                <input type="text" class="form-control form-control-sm" id="map_concept_id" placeholder="e.g. 44054006">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Or Term Search</label>
                                <input type="text" class="form-control form-control-sm" id="map_term" placeholder="e.g. Diabetes mellitus type 2">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btn_map_lookup">Find ICD</button>
                            </div>
                        </div>
                        <div class="small text-muted mb-2" id="map_lookup_note">Enter concept id or term and click Find ICD.</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" id="tbl_map_rows">
                                <thead>
                                    <tr>
                                        <th width="140">ICD Code</th>
                                        <th width="140">Map Source</th>
                                        <th width="100">Group</th>
                                        <th width="100">Priority</th>
                                        <th>Rule / Advice</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="5" class="text-muted">No lookup yet.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    function esc(text) {
        return $('<div>').text((text || '').toString()).html();
    }

    function renderRelease(data) {
        var rel = (data && data.latest_release) ? data.latest_release : {};
        if (!rel || !Object.keys(rel).length) {
            $('#snomed_release_block').removeClass('text-success').addClass('text-muted').text('No import log found yet.');
            return;
        }

        var parts = [];
        parts.push('<div><b>Package:</b> ' + esc(rel.package_name || '') + '</div>');
        parts.push('<div><b>Release:</b> ' + esc(rel.release_effective_time || '') + '</div>');
        parts.push('<div><b>Status:</b> ' + esc(rel.status || '') + '</div>');
        parts.push('<div><b>Imported At:</b> ' + esc(rel.imported_at || '-') + '</div>');
        parts.push('<div><b>RF2 MD5:</b> ' + esc(rel.rf2_md5 || '-') + '</div>');
        parts.push('<div><b>Notes MD5:</b> ' + esc(rel.release_notes_md5 || '-') + '</div>');
        if (rel.error_text) {
            parts.push('<div class="text-danger"><b>Error:</b> ' + esc(rel.error_text) + '</div>');
        }

        $('#snomed_release_block').removeClass('text-muted').html(parts.join(''));
    }

    function renderCounts(data) {
        var counts = (data && data.table_counts) ? data.table_counts : {};
        var rows = [
            { key: 'concept', label: 'snomed_concept' },
            { key: 'description', label: 'snomed_description' },
            { key: 'language_refset', label: 'snomed_language_refset' },
            { key: 'simple_map', label: 'snomed_map_simple' },
            { key: 'extended_map', label: 'snomed_map_extended' }
        ];

        var html = [];
        rows.forEach(function (row) {
            html.push('<tr><td>' + esc(row.label) + '</td><td>' + esc(counts[row.key] || 0) + '</td></tr>');
        });

        $('#tbl_snomed_counts tbody').html(html.join(''));
    }

    function loadStatus() {
        $('#snomed_release_block').removeClass('text-success').addClass('text-muted').text('Loading...');
        $.getJSON('<?= base_url('Opd_prescription/snomed_release_status') ?>', function (data) {
            renderRelease(data || {});
            renderCounts(data || {});
        }).fail(function () {
            $('#snomed_release_block').removeClass('text-success').addClass('text-danger').text('Unable to load SNOMED release status.');
            $('#tbl_snomed_counts tbody').html('<tr><td colspan="2" class="text-danger">Unable to load row counts.</td></tr>');
        });
    }

    function renderMapRows(data) {
        var rows = (data && data.rows) ? data.rows : [];
        var conceptId = (data && data.concept_id) ? data.concept_id : '';
        var conceptTerm = (data && data.concept_term) ? data.concept_term : '';

        if (!rows.length) {
            $('#map_lookup_note').removeClass('text-success').addClass('text-muted').text('No ICD mapping found for selected concept.');
            $('#tbl_map_rows tbody').html('<tr><td colspan="5" class="text-muted">No ICD mapping rows.</td></tr>');
            return;
        }

        $('#map_lookup_note').removeClass('text-muted').addClass('text-success').text('Mapped Concept: ' + conceptId + (conceptTerm ? (' - ' + conceptTerm) : ''));

        var html = [];
        rows.forEach(function (row) {
            var ruleAdvice = ((row.map_rule || '') + ' ' + (row.map_advice || '')).trim();
            html.push('<tr>'
                + '<td>' + esc(row.icd_code || '') + '</td>'
                + '<td>' + esc(row.source || '') + '</td>'
                + '<td>' + esc(row.map_group || 0) + '</td>'
                + '<td>' + esc(row.map_priority || 0) + '</td>'
                + '<td>' + esc(ruleAdvice) + '</td>'
                + '</tr>');
        });

        $('#tbl_map_rows tbody').html(html.join(''));
    }

    function runMapLookup() {
        var conceptId = ($('#map_concept_id').val() || '').toString().trim();
        var term = ($('#map_term').val() || '').toString().trim();
        if (!conceptId && !term) {
            $('#map_lookup_note').removeClass('text-success').addClass('text-danger').text('Enter concept id or term.');
            return;
        }

        $('#map_lookup_note').removeClass('text-danger text-success').addClass('text-muted').text('Searching mapping...');
        $.getJSON('<?= base_url('Opd_prescription/snomed_map_lookup') ?>', { concept_id: conceptId, q: term }, function (data) {
            renderMapRows(data || {});
        }).fail(function () {
            $('#map_lookup_note').removeClass('text-success text-muted').addClass('text-danger').text('Unable to fetch mapping.');
            $('#tbl_map_rows tbody').html('<tr><td colspan="5" class="text-danger">Lookup failed.</td></tr>');
        });
    }

    $('#btn_snomed_refresh').on('click', loadStatus);
    $('#btn_map_lookup').on('click', runMapLookup);
    $('#map_term, #map_concept_id').on('keydown', function (e) {
        if ((e.key || '').toLowerCase() === 'enter') {
            e.preventDefault();
            runMapLookup();
        }
    });

    loadStatus();
})();
</script>
