<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<section class="content-header">
    <h1>
	Online OPD Booked : Date <?=date('d-m-Y')?>
    </h1>
</section>
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="box box-primary">
            <div class="box-header with-border">
                    <h3 class="box-title">Online OPD Book List</h3>
            </div>
            <div class="box-body">
                <table class="table table-striped table-condensed table-hover" >
                <?php foreach($opd_online_list as $row_item) { ?>
                    <tr>
                        <td>
                            <?=$row_item->p_code?>
                        </td>
                        <td>
                            <?=$row_item->p_fname?>
                        </td>
                        <td>
                            <?=$row_item->opd_code?>
                        </td>
                        <td>
                            <?=$row_item->opd_book_date?>
                        </td>
                        <td>
                            <?=$row_item->apointment_date?>
                        </td>
                        <td>
                            Dr. <?=$row_item->doc_name?> 
                        </td>
                        <td>
                            <?=$row_item->doc_spec?> 
                        </td>
                        <td>
                            <a href="javascript:load_form('Opd/invoice/<?=$row_item->opd_id?>/0');" >Print OPD</a> 
                        </td>
                    </tr>
                <?php } ?>
                </table>
            </div>
        </div>
    </div>
</section>
