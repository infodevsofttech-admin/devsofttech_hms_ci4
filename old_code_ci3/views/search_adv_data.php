<?php if(count($search_result)>0) { ?>
    <table class="table table-bordered table-striped TableData"><tr><th>Name of Person/Sex/Relative Name</th></tr>
    <?php foreach($search_result as $row) { ?>
    <tr>
        <td>
			<a href="javascript:load_form(\'/Patient/person_record/<?=$row->id?>');"><?=$row->Sresult?></a>
		</td>
	</tr>
    <?php } ?>
    </table>
<?php }  ?>