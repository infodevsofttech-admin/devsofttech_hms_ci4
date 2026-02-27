<script>
//FOOD DRUG INTERACTION 

	function save_FOOD_DRUG_INTERACTION(){
		
		var chkArray = [];
		$(".chk_food:checked").each(function() {
			chkArray.push($(this).val());
		});
		var selected;
		selected = chkArray.join(':') ;
		
		$.post('/Ipd_discharge/update_food_interaction',
			{ 
			"ipd_id": $("#ipd_id").val(),
			"FOOD_DRUG_INTERACTION_list":selected},
			function(data){
				alert(data);
			});
	}

</script>