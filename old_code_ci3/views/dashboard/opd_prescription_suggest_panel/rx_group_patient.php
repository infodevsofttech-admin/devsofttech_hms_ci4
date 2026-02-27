<?php 
foreach($rx_group_list as $row){
    $button_code= ($row->id % count($Color)) ;
    ?>
        <button type="button" 
            class="btn  btn-flat margin <?=$Color[$button_code]?>" 
            data-toggle="tooltip" 
            data-html="true" 
            title="<em><?=$row->rx_group_name?></em> <?=$row->med_name_list?>" >
            <?=$row->rx_group_name?>
        </button> 
<?php  } ?>