

<html>
	<body>
		<input type="text" name="input_cc" id="input_cc" />
		<button type="button" class="btn btn-primary" id="btn_update2">Click here to Show</button>
		
	<hr/>
	<input type="text" name="input_cust_name" id="input_cust_name" />
	<input type="text" name="input_cust_phone" id="input_cust_phone" />
	</body>


</html>


<script>
$('#btn_update2').click( function()
	{
		$.post('http://61.16.222.101:8080/WebService/custinfo.aspx',
		{ "cc":$('#input_cc').val() }, function(data){
        if(data.Error==1)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					
					$("#input_cust_name").val(data.customers_Name);
					$("#input_cust_phone").val(data.Mobile_Phone);
				}
		},'json');
	});	
</script>