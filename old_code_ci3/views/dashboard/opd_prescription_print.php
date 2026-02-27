<style>@page {
        margin-top: 4.2cm;
        margin-bottom: 1.2cm;
        margin-left: 1cm;
        margin-right: 0.5cm;
        
        margin-header:0.5cm;
        margin-footer:0.5cm;
        header: html_myHeader;
        footer: html_myFooter;
}

.myfixed_RxGroup {
    position: absolute;
    overflow: visible;
    top: 66mm; 
    left: 34mm; 
    width: 80mm;   /* you must specify a width */
    margin-top: auto;
    margin-bottom: auto;
    margin-left: auto;
    margin-right: auto;
}
</style>

<htmlpageheader name="myHeader">
<table style="font-size: 12px;" cellpadding="5">
	<tr>
        <td style="width: 40%;">
		 	<p style="font-size: 12px">
            <?php
             echo $top_content;
            ?>
			</p>
		 </td>
    	<td style="width: 60%;vertical-align: top;">
		    <p align="center" style="font-size: 30px;" ><?=H_Name?></p>
		    <p align="center" style="font-size: 12px" ><?=H_address_1?>, Uttarakhand<br>
            <?php 
                if(H_phone_No!='')
                {
                    echo 'Phone: '.H_phone_No;
                } 
                
                if(H_Email!='')
                {
                   echo ' ,Email: '.H_Email;
                }
                
                echo '<br>';
            ?>
		 </td>
		 
	</tr>
</table>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table width="100%" style="font-size: 10px;">
        <tr>
            <td width="33%" >Page : {PAGENO}/{nbpg}</td>
            <td width="66%" style="text-align: right;"></td>
        </tr>
    </table>
</htmlpagefooter>
<table  cellspacing="0" boder="0" >
    <tr>
        <td width="10%" ></td>
        <td width="90%" style="text-align: left;">
        <h3 align="Left">Rx</h3>
        <?php
            echo $vital_content;
            echo '<br/>';
            echo $Complaint;
            echo '<br/>';
            echo $diagnosis;
            echo '<br/>';
            echo $investigation;
            echo '<br/>';
            echo $medical;
            echo '<br/>';
            echo $doctor;
            echo '<br/>';

        ?>
        </td>
    </tr>
</table>
