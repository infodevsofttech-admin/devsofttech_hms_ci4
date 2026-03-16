<!---- OLD Items List  Last 2 Month   ----->
<p class="text-danger">Search By Phone No. (Complete 10 Digit Phone No.)</p>
<div class="input-group margin">
    <input type="text" class="form-control input-sm" id="txt_phone" name="txt_phone">
        <span class="input-group-btn">
            <button type="button" class="btn btn-info btn-flat btn-sm"  onclick="search_med_item(1);">By Phone No</button>
        </span>
</div>
<hr/>
<p class="text-danger">Search By UHID (Type Last 5 to 6 Digit of UHID/Patient ID) </p>
<div class="input-group margin">
    <input type="text" class="form-control input-sm" id="txt_uhid" name="txt_uhid" >
        <span class="input-group-btn">
            <button type="button" class="btn btn-info btn-flat btn-sm" onclick="search_med_item(2);">By UHID</button>
        </span>
</div>
<hr/>
<p class="text-danger">Search By Invoice Code (Type Last 5 to 6 Digit of invoice)</p>
<div class="input-group margin">
    <input type="text" class="form-control input-sm" id="txt_inv_no" name="txt_inv_no">
        <span class="input-group-btn">
            <button type="button" class="btn btn-info btn-flat btn-sm"  onclick="search_med_item(3);">By Invoice No</button>
        </span>
</div>
<hr/>
<p class="text-danger">Search By Product</p>
<div class="input-group margin">
    <input type="text" class="form-control input-sm" id="txt_product_code" name="txt_product_code">
        <span class="input-group-btn">
            <button type="button" class="btn btn-info btn-flat btn-sm"  onclick="search_med_item(4);">By Product</button>
        </span>
</div>
<!----- OLD Data End Here   ---->
<script>
    

    function search_med_item(s_method)
    {
        search_text='';

        if(s_method=='1')
        {
            search_text=$('#txt_phone').val();
        }else if(s_method=='2'){
            search_text=$('#txt_uhid').val();
        }else if(s_method=='3'){
            search_text=$('#txt_inv_no').val();
        }else if(s_method=='4'){
            search_text=$('#txt_product_code').val();
        }
		
		if(search_text=='')
        {
            notify('error','Please Attention','Search TEXT should not be blank');
			return false;
        }

		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/Medical/Invoice_Item_old',{
			"search_text":search_text,
			"search_type":s_method,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value}, function(data){
                $('#search_body_part').html(data);;
        	});
    }
</script>