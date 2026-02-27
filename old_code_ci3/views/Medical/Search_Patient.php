<div class="col-md-12">
        <?php echo form_open('Medical/search', array('role'=>'form','class'=>'form2')); ?>
			<div class="input-group input-group-sm">
				<input class="form-control" type="text" id="txtsearch" name="txtsearch">
					<span class="input-group-btn">
					<button type="submit" class="btn btn-info btn-flat">Go!</button>
				</span>
			</div>
		<?php echo form_close(); ?>
		<div class="searchresult"></div>
	</div>
<script>
    $(document).ready(function(){
        $('form.form2').on('submit', function(form){
            form.preventDefault();
            $.post('/index.php/Medical/search', $('form.form2').serialize(), function(data){
                $('div.searchresult').html(data);
                $('#example1').DataTable();
            });
        });
	});
</script>