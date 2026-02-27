<style>@page {

margin-top: 4cm;
margin-bottom: 1.2cm;
margin-left: 0.5cm;
margin-right: 0.5cm;

margin-header:0.5cm;
margin-footer:0.5cm;
header: html_myHeader;
footer: html_myFooter;
}
</style>

<htmlpageheader name="myHeader">
<table style="font-size: 12px;width: 100%;" >
	<tr>
        <td style="width: 20%;vertical-align: top;text-align:center;">
			<img src="assets/images/<?=H_logo?>" width="100px" /> 
        </td>
    	<td style="width: 70%;vertical-align: top;">
		    <p align="center" style="font-size: 25px;" ><?=H_Name?></p>
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
        <td>
            <barcode code="<?=$bar_content?>" size="0.8" type="QR" error="M" class="barcode" />
        </td>
	</tr>
</table>
</htmlpageheader>

<?=$content?>