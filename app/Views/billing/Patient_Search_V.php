<br /><br />
<div class="box">
<div class="box-header">
  <h3 class="box-title">Patient Search Results</h3>
</div>
<!-- /.box-header -->
<div class="box-body">
  <table id="example1" class="table table-bordered table-striped TableData">
    <thead>
    <tr>
      <th>Sr.No.</th>
      <th>Patient/UHID Code</th>
      <th>Name {Relative Name}</th>
      <th>Age</th>
      <th>Last Visit</th>
      <th>Insurance</th>
      <th>Patient History</th>
    </tr>
    </thead>
    <tbody>
    </tbody>
    <tfoot>
    <tr>
      <th>Sr.No.</th>
      <th>Patient/UHID Code</th>
      <th>Name {Relative Name}</th>
      <th>Age</th>
      <th>Last Visit</th>
      <th>Insurance</th>
      <th>Patient History</th>
    </tr>
    </tfoot>
  </table>
</div>
<!-- /.box-body -->
</div>

<script>
(function() {
    var searchQuery = '<?= esc($search_query ?? '', 'js') ?>';
  var advancedFilters = <?= json_encode($advanced_filters ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    
    if (window.jQuery && $.fn.DataTable) {
        $('#example1').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= base_url('billing/patient/search_ajax') ?>',
                type: 'GET',
        data: function(d) {
          d.search_query = searchQuery;
          d.adv_search_by = advancedFilters.adv_search_by || '';
          d.adv_search_value = advancedFilters.adv_search_value || '';
          d.adv_age_mode = advancedFilters.adv_age_mode || '';
          d.adv_age_value = advancedFilters.adv_age_value || '';
          d.adv_age_from = advancedFilters.adv_age_from || '';
          d.adv_age_to = advancedFilters.adv_age_to || '';
          d.adv_age_tolerance = advancedFilters.adv_age_tolerance || '2';
        }
            },
            columns: [
                { data: 0, orderable: false },
                { data: 1, orderable: true },
                { data: 2, orderable: true },
                { data: 3, orderable: true },
                { data: 4, orderable: true },
                { data: 5, orderable: true },
                { data: 6, orderable: false }
            ],
            order: [[4, 'desc']], // Order by Last Visit (column index 4) DESC
            searching: false,
            pageLength: 30,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span>',
              emptyTable: searchQuery ? 'No patients found matching your search' : 'No patients available for the selected criteria',
                info: 'Showing _START_ to _END_ of _TOTAL_ patients',
                infoEmpty: 'Showing 0 to 0 of 0 patients',
                infoFiltered: '(filtered from _MAX_ total patients)',
                lengthMenu: 'Show _MENU_ patients per page',
                zeroRecords: 'No matching patients found'
            }
        });
    }
})();
</script>
    