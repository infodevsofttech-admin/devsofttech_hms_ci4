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

<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >
			<?php
			$srno=0;
            $head_start=0;
            $inv_date='';
				foreach($inv_items as $row)
				{
                    $srno=$srno+1;
                    if($row->str_inv_date<>$inv_date){
                        if(($srno % 3)==2 )
                        {
                            echo '<td></td><td></td></tr>';
                        }elseif(($srno % 3)==0){
                            echo '<td></td></tr>';
                        }
                        
                        $srno=1;
						echo '  <tr>
                                    <td colspan="3"><hr/></td>
                                </tr>';
                            echo '<tr><td colspan="3" style="font-size=12px;"><b>Dated : </b>'
                                .$row->str_inv_date.'</td></tr>';
                    }
                   
                    if(($srno % 3)==1)
                    {   
                        echo '<tr>';
                    }
                       
                    echo '<td>'.$srno.' : '
                        .$row->item_Name.' '.$row->formulation. ' /Qty :'
                        .$row->qty.'</td>';
                        
                    if(($srno % 3)==0){
                        echo '</tr>';
                    }

                    $inv_date=$row->str_inv_date;
				}
			?>
		</table>
	<p>
	<?=$doc_list_sign?>
	</p>
