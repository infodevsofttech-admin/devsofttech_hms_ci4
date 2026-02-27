
<?php 
foreach($rx_group_list as $row){
    $button_code= ($row->id % count($Color)) ;
    ?>
    <div class="btn-group " style="margin-bottom: 5px;">
        <button type="button" 
            class="btn  btn-flat <?=$Color[$button_code]?>  btn-xs" 
            >
            <?=$row->rx_group_name?>
        </button>
        <button type="button" class="btn <?=$Color[$button_code]?> dropdown-toggle btn-xs" data-toggle="dropdown">
            <span class="caret"></span>
            <span class="sr-only">Toggle Dropdown</span>
        </button>
        <ul class="dropdown-menu <?=$Color[$button_code]?>" role="menu">
            <li><a href="javascript:add_prescribe(<?=$row->id?>);">
                    Rx:
                    <p><?=$row->med_name_list?></p>
                </a>
            </li>
            <!--
            <li class="divider"></li>
            <li><a href="#">Add Only Prescribe</a></li>
            <li><a href="#">Replace in Prescribe</a></li>
            <li><a href="#">Edit</a></li>
            <li><a href="#">Delete</a></li>
            -->
        </ul>
    </div>
<?php  } ?>
