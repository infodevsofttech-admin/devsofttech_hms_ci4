<style>@page {
        margin-top: 5cm;
        margin-bottom: 2cm;
        margin-left: 1cm;
        margin-right: 0.5cm;
        
        margin-header:0.5cm;
        margin-footer:1cm;
        header: html_myHeader;
        footer: html_myFooter;
}
</style>

<htmlpageheader name="myHeader">
    
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table width="100%" style="font-size: 10px;">
        <tr>
            <td width="33%" >Page : {PAGENO}/{nbpg}</td>
            <td width="66%" style="text-align: right;">IPD-ID:<?=$ipd_master[0]->ipd_code ?> / UHID:<?=$ipd_master[0]->p_code ?> /Name : <?=$ipd_master[0]->p_fname ?></td>
        </tr>
    </table>
</htmlpagefooter>
<table width="100%" >
	<tr>
		<td colspan="2">
			<h3>Medicine Prescription</h3>
		</td>
	</tr>
	<tr>
		<td><h1>Rx</h1></td>
		<td><p style="font-size: 9px">
			 	<i>Patient Info.</i><br>
			 	<strong><?=$ipd_master[0]->p_fname ?></strong><br>
				<b>UHID ID :</b> <?=$ipd_master[0]->p_code ?><br>
				<b>IPD Code :</b> <?=$ipd_master[0]->ipd_code ?><br>
			</p>
		</td>
	</tr>
</table>

<table  cellspacing="0"  style="font-size: 10px;width:100%;border-collapse: collapse;" >
			<?php
			$srno=0;
            $head_start=0;
            $inv_date='';
				foreach($inv_items as $row)
				{
                    $srno=$srno+1;
                    if($row->str_inv_date<>$inv_date){
                        if(($srno % 2)==0 && $srno<>1){
                            echo '<td style="border-bottom: 1pt solid gray;"></td></tr>';
                        }
                        $srno=1;
						echo '  <tr>
                                    <td colspan="2"> ..</td>
                                </tr>';
                            echo '<tr><td colspan="2" style="font-size=12px;"><h3>Dated : '.$row->str_inv_date.'</h3></td></tr>';
                    }
                   
                    if(($srno % 2)==1)
                    {   
                        echo '<tr >';
                    }
                       
                    echo '<td style="border-bottom: 1pt solid gray;">'.$srno.' : '
                        . $row->formulation . ' ' . $row->item_Name . '<br/>(<i>' . $row->genericname . '</i>) /Qty :'
                        .$row->tot_qty.'</td>';
                        
                    if(($srno % 2)==0){
                        echo '</tr>';
                    }

                    $inv_date=$row->str_inv_date;
				}

                

			?>
		</table>
	<p>
	<?=$doc_list_sign?>
	</p>
