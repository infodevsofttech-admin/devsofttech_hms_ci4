<section class="content-header">
    <h1>
        Lab Panel
    </h1>
	<input type="hidden" id="lab_type" name="lab_type" value="<?=$lab_type ?>" />
</section>
<section class="content">
	<div class="row">
		<div class="col-md-12">
			<div id="tabs" class="nav-tabs-custom">
				<ul class="nav nav-tabs" id="prodTabs">
					<li><a aria-expanded="true" href="#tab_1" data-url="/Lab_Admin/lab_tab_1/<?=$lab_type ?>"  >New Request for Sample</a></li>
					<li><a aria-expanded="true" href="#tab_2" data-url="/Lab_Admin/lab_tab_2/<?=$lab_type ?>">On Process</a></li>
					<!-- <li><a aria-expanded="true" href="#tab_3" data-url="/Lab_Admin/lab_tab_3/<?=$lab_type ?>">On Verification</a></li>  -->
					<li><a aria-expanded="true" href="#tab_4" data-url="/Lab_Admin/lab_tab_4/<?=$lab_type ?>">On Printing</a></li>
				</ul>
				<div class="tab-content">
					<div id="tab_1" class="tab-pane active"></div>
					<div id="tab_2" class="tab-pane active"></div>
					<div id="tab_3" class="tab-pane active"></div>
					<div id="tab_4" class="tab-pane active"></div>
				</div>
			</div>
		</div>
	</div>

</section>
<script>
	$('#tabs').on('click','.tablink,#prodTabs a',function (e) {
    e.preventDefault();
    var url = $(this).attr("data-url");
	
    if (typeof url !== "undefined") {
        var pane = $(this), href = this.hash;

        // ajax load from data-url
        $(href).load(url,function(result){      
            pane.tab('show');
        });
    } else {
        $(this).tab('show');
    }
	});

</script>