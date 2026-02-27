<section class="content-header">
  <h1>
    Already created on today 
	<small>
    <a href="javascript:load_form_div('/Medical/Invoice_counter_new/<?=$pno?>/<?=$ipd_id?>/<?=$case_id?>','maindiv');" >Add New Invoice</a>
	</small>
  </h1>
</section>
<section class="content">
<div class="row">
    <div class="col-md-6">
    <table class="table table-striped table-condensed table-hover" style="font-size:12px;">
    <?php
        $srno=0;
        $inv_id=0;

        foreach($med_inv_item_list as $row)
        { 
            if($inv_id<>$row->inv_id)
            {
                $srno=0;

                echo '<tr>';
                echo '<td colspan="6"></td>';
                echo '</tr>';

                echo '<tr  style="background:orange;">';
                echo '<td>#</td>';
                echo '<td colspan="2">'.$row->inv_med_code.'</td>';
                echo '<td >'.$row->inv_date.'</td>';
                echo '<td  style="text-align:right;">'.$row->net_amount.'</td>';
                echo '<td><a href="javascript:load_form_div(\'/Medical/Invoice_med_show/'.$row->inv_id.'/0\',\'maindiv\');" >Edit</a></td>';
                echo '</tr>';

                echo '<tr>';
                echo '<td >#</td>';
                echo '<td >Item</td>';
                echo '<td >Bat./Exp.Dt</td>';
                echo '<td >Rate</td>';
                echo '<td >Qty</td>';
                echo '<td >N.Amt.</td>';
                echo '</tr>';
            }

            $srno=$srno+1;
            echo '<tr>';
            echo '<td>'.$srno.'</td>';
            echo '<td>'.$row->item_Name.'</td>';
            echo '<td>'.$row->batch_no.'<br/>'.$row->exp_date.'</td>';
            echo '<td style="text-align:right;">'.$row->price.'</td>';
            echo '<td style="text-align:right;">'.$row->qty.'</td>';
            echo '<td style="text-align:right;">'.$row->twdisc_amount.'</td>';
            echo '</tr>';
           
            $inv_id=$row->inv_id;
        }
    ?>
    </table>

    </div>
</div>
</section>
