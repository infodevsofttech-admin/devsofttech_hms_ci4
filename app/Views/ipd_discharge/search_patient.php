<section class="content">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Search IPD Patient for Discharge</h5>
        </div>
        <div class="card-body">
            <form id="ipdDischargeSearchForm" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="search_input" class="form-label">Search by IPD Code, UHID, Patient Name, or Mobile</label>
                        <input type="text" 
                               class="form-control" 
                               id="search_input" 
                               name="q" 
                               value="" 
                               placeholder="Enter IPD Code, UHID, Patient Name, or Mobile Number"
                               autofocus>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnClearSearch">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                </div>
            </form>

            <div id="searchResultsContainer">
                <div class="alert alert-light border">
                    <i class="bi bi-search"></i> Enter a search term above to find IPD patients.
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var searchInput = $('#search_input');
    var searchForm = $('#ipdDischargeSearchForm');
    var resultsContainer = $('#searchResultsContainer');
    var dataTable = null;
    var dischargeTemplates = <?= json_encode(array_map(static function (array $template): array {
        return ['id' => (int) ($template['id'] ?? 0), 'name' => (string) ($template['template_name'] ?? '')];
    }, $discharge_templates ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    function performSearch() {
        var searchQuery = searchInput.val().trim();
        
        if (searchQuery === '') {
            resultsContainer.html(
                '<div class="alert alert-light border">' +
                '<i class="bi bi-search"></i> Enter a search term above to find IPD patients.' +
                '</div>'
            );
            return;
        }

        // Show loading
        resultsContainer.html(
            '<div class="text-center py-4">' +
            '<div class="spinner-border text-primary" role="status">' +
            '<span class="visually-hidden">Loading...</span>' +
            '</div>' +
            '<p class="mt-2">Searching...</p>' +
            '</div>'
        );

        $.ajax({
            url: '<?= base_url('Ipd_discharge/search_patient_ajax') ?>',
            method: 'GET',
            data: { q: searchQuery },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayResults(response.records, searchQuery);
                } else {
                    resultsContainer.html(
                        '<div class="alert alert-danger">' +
                        '<i class="bi bi-exclamation-triangle"></i> ' + (response.message || 'Error occurred during search') +
                        '</div>'
                    );
                }
            },
            error: function(xhr) {
                resultsContainer.html(
                    '<div class="alert alert-danger">' +
                    '<i class="bi bi-exclamation-triangle"></i> Failed to perform search. Please try again.' +
                    '</div>'
                );
            }
        });
    }

    function displayResults(records, searchQuery) {
        if (!records || records.length === 0) {
            resultsContainer.html(
                '<div class="alert alert-info">' +
                '<i class="bi bi-info-circle"></i> No patients found matching "' + escapeHtml(searchQuery) + '".' +
                '</div>'
            );
            return;
        }

        var html = '<div class="alert alert-success">' +
            'Found ' + records.length + ' patient(s) matching "' + escapeHtml(searchQuery) + '".' +
            '</div>' +
            '<div class="table-responsive">' +
            '<table class="table table-striped table-bordered table-hover" id="ipdDischargeSearchTable">' +
            '<thead>' +
            '<tr>' +
            '<th>IPD Code</th>' +
            '<th>UHID</th>' +
            '<th>Patient Name</th>' +
            '<th>Age/Gender</th>' +
            '<th>Bed</th>' +
            '<th>Doctor</th>' +
            '<th>Admit Date</th>' +
            '<th>Days</th>' +
            '<th>Status</th>' +
            '<th>Actions</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>';

        records.forEach(function(row) {
            var patientName = (row.p_fname || '') + ' ' + (row.p_rname || '');
            // Determine age display using server calculated age_display with DOB fallback
            var rawAge = (row.age_display || '').trim();
            var ageDisplay = '';
            if (rawAge !== '') {
                if (/^\d+\s*year/i.test(rawAge)) {
                    var m = rawAge.match(/^(\d+)/);
                    ageDisplay = m ? m[1] + 'Y' : rawAge;
                } else if (/^\d+\s*month/i.test(rawAge)) {
                    var m = rawAge.match(/^(\d+)/);
                    ageDisplay = m ? m[1] + 'M' : rawAge;
                } else if (/^\d+\s*days/i.test(rawAge)) {
                    var m = rawAge.match(/^(\d+)/);
                    ageDisplay = m ? m[1] + 'D' : rawAge;
                } else {
                    ageDisplay = rawAge;
                }
            } else {
                var age = parseInt(row.p_age || 0);
                var ageInMonth = parseInt(row.age_in_month || 0);
                if (age > 0) {
                    ageDisplay = age + 'Y';
                } else if (ageInMonth > 0) {
                    if (ageInMonth >= 12) {
                        ageDisplay = Math.floor(ageInMonth / 12) + 'Y';
                    } else {
                        ageDisplay = ageInMonth + 'M';
                    }
                } else if (row.dob) {
                    var dobDate = new Date(row.dob);
                    if (!isNaN(dobDate.getTime())) {
                        var diffMs = Date.now() - dobDate.getTime();
                        var ageDate = new Date(diffMs);
                        var calcYears = Math.abs(ageDate.getUTCFullYear() - 1970);
                        ageDisplay = calcYears + 'Y';
                    }
                }
            }
            
            if (!ageDisplay) {
                ageDisplay = '-';
            }
            
            var gender = (row.xgender || 'Unknown').charAt(0).toUpperCase();
            var ageGender = ageDisplay + '/' + gender;
            
            var dischargeStatus = parseInt(row.discharge_status || 0);
            var statusBadge = '';
            switch (dischargeStatus) {
                case 1:
                    statusBadge = '<span class="badge bg-success">Discharged</span>';
                    break;
                case 2:
                    statusBadge = '<span class="badge bg-info">LAMA</span>';
                    break;
                case 3:
                    statusBadge = '<span class="badge bg-danger">Death</span>';
                    break;
                case 4:
                    statusBadge = '<span class="badge bg-warning">Absconding</span>';
                    break;
                default:
                    statusBadge = '<span class="badge bg-primary">Admitted</span>';
            }
            
            var ipdId = parseInt(row.id || 0);
            var admitDate = row.register_date ? formatDate(row.register_date) : '';
            var templateOptions = dischargeTemplates.map(function(template) {
                return '<option value="' + template.id + '">' + escapeHtml(template.name) + '</option>';
            }).join('');

            html += '<tr>' +
                '<td>' + escapeHtml(row.ipd_code || '') + '</td>' +
                '<td>' + escapeHtml(row.uhid || '') + '</td>' +
                '<td>' + escapeHtml(patientName.trim()) + '</td>' +
                '<td>' + escapeHtml(ageGender) + '</td>' +
                '<td>' + escapeHtml(row.Bed_Desc || '') + '</td>' +
                '<td>' + escapeHtml(row.doc_name || '') + '</td>' +
                '<td>' + admitDate + '</td>' +
                '<td>' + escapeHtml(row.no_days || '0') + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '<td class="text-nowrap">' +
                '<button type="button" class="btn btn-success btn-sm me-1" ' +
                'onclick="load_form(\'<?= base_url('Ipd_discharge/ipd_select/') ?>' + ipdId + '\',\'Create Discharge - ' + escapeHtml(row.ipd_code || '') + '\');" ' +
                'title="Create/Edit Discharge Summary">' +
                '<i class="bi bi-file-earmark-medical"></i> Create Discharge' +
                '</button>' +
                '<button type="button" class="btn btn-primary btn-sm" ' +
                'onclick="openDischargePrintWindow(' + ipdId + ', this)" ' +
                'title="Print Discharge Summary">' +
                '<i class="bi bi-printer"></i> Print Discharge' +
                '</button>' +
                '<select class="form-select form-select-sm mt-1 discharge-template-select" style="width:190px;" aria-label="Print template">' +
                templateOptions +
                '</select>' +
                '</td>' +
                '</tr>';
        });

        html += '</tbody></table></div>';
        resultsContainer.html(html);

        // Destroy existing DataTable if any
        if (dataTable) {
            dataTable.destroy();
        }

        // Initialize DataTable
        if (window.jQuery && $.fn.DataTable) {
            dataTable = $('#ipdDischargeSearchTable').DataTable({
                pageLength: 25,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: -1 }
                ]
            });
        }
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatDate(dateStr) {
        try {
            var date = new Date(dateStr);
            var day = ('0' + date.getDate()).slice(-2);
            var month = ('0' + (date.getMonth() + 1)).slice(-2);
            var year = date.getFullYear();
            return day + '-' + month + '-' + year;
        } catch (e) {
            return dateStr;
        }
    }

    // Form submit handler
    searchForm.on('submit', function(e) {
        e.preventDefault();
        performSearch();
    });

    // Clear button handler
    $('#btnClearSearch').on('click', function() {
        searchInput.val('');
        resultsContainer.html(
            '<div class="alert alert-light border">' +
            '<i class="bi bi-search"></i> Enter a search term above to find IPD patients.' +
            '</div>'
        );
        searchInput.focus();
    });

    // Global function for print window
    window.openDischargePrintWindow = function(ipdId, button) {
        if (!ipdId || ipdId <= 0) {
            alert('Invalid IPD ID');
            return;
        }
        
        var url = '<?= base_url('Ipd_discharge/show_discharge') ?>/' + ipdId + '/1';
        var selector = button ? button.parentElement.querySelector('.discharge-template-select') : null;
        if (selector && selector.value) {
            url += '?tpl=' + encodeURIComponent(selector.value);
        }
        var printWindow = window.open(url, 'DischargePrint', 'width=900,height=700,scrollbars=yes,resizable=yes');
        
        if (printWindow) {
            printWindow.focus();
        } else {
            alert('Please allow pop-ups to print the discharge summary.');
        }
    };
})();
</script>

<style>
#ipdDischargeSearchTable {
    font-size: 0.9rem;
}

#ipdDischargeSearchTable th {
    background-color: #f8f9fa;
    font-weight: 600;
}

#ipdDischargeSearchTable td {
    vertical-align: middle;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>
