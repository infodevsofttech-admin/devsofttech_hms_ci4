<div class="row">
    <div class="col-md-12"> 
        <div class="form-group">
            <label>New Rx-Group Name</label>
            <input class="form-control input-sm" name="input_rx_group_name" id="input_rx_group_name" type="text" autocomplete="on"  >
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <button type="button" class="btn btn-primary" id="btn_save_rx_group">Save New Rx-Group</button>
        <input type="hidden" name="opd_session_id_2" id="opd_session_id_2" value="<?=$opd_id ?>" >
        <input type="hidden" name="opd_doc_id_2" id="opd_doc_id_2" value="<?=$doc_id ?>" >
   </div>
</div>
<script>
    $( function() {
        $('#btn_save_rx_group').click(function(){

            var opd_session_id = $('#opd_session_id_2').val();
			var rx_group_name = $('#input_rx_group_name').val();
            var doc_id = $('#opd_doc_id_2').val();
            var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
				
			$.post('/index.php/Opd_prescription/save_rx_group', 
                {"opd_session_id": opd_session_id,
			    "rx_group_name": rx_group_name,
			    "doc_id": doc_id,
                "<?=$this->security->get_csrf_token_name()?>":csrf_value}, function(data){
                    $('#Rx_Group_Panel').html(data);
            });
		})
    });
</script>