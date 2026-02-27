<div class="row">
  <div class="col-md-12">
     <nav class="navbar navbar-inverse">
      <div class="container-fluid">
        <ul class="nav navbar-nav">
          <li class="active"><a href="#">Work Panel</a></li>
		  <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">Invoice
            <span class="caret"></span></a>
            <ul class="dropdown-menu">
                <li><a href="javascript:load_form_div('/Orgcase/search_all','sub_content');"   ><i class="fa fa-circle-o"></i> Organization Invoice</a></li>
                <li><a href="javascript:load_form_div('/Invoice/opdlist','sub_content');" ><i class="fa fa-circle-o"></i> OPD Invoice</a></li>
                <li><a href="javascript:load_form_div('/Invoice/chargeslist','sub_content');"><i class="fa fa-circle-o"></i> Charges Invoice</a></li>
                <li><a href="javascript:load_form_div('/Invoice/list_refund','sub_content');"><i class="fa fa-circle-o"></i> Refund Request</a></li>
                <li><a href="javascript:load_form_div('/Invoice/list_req_payment','sub_content');"><i class="fa fa-circle-o"></i> Payment Request</a></li>
                <?php if ($this->ion_auth->in_group('admin')) { ?>
                    <li><a href="javascript:load_form_div('/Payment','sub_content');"><i class="fa fa-circle-o"></i> Payment Edit</a></li>
                <?php } ?>
            </ul>
          </li>
		  <?php if ($this->ion_auth->in_group('admin')) { ?>
          <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">Admin
            <span class="caret"></span></a>
            <ul class="dropdown-menu">
                <li><a href="javascript:load_form_div('/Doctor/search','sub_content');"><i class="fa fa-circle-o"></i> Doctors</a></li>
                <li><a href="javascript:load_form_div('/auth_dst/search','sub_content');" ><i class="fa fa-circle-o"></i> User Admin</a></li>
                <li><a href="javascript:load_form_div('/insurance/search','sub_content');"><i class="fa fa-circle-o"></i> Insurance Admin</a></li>
                <li><a href="javascript:load_form_div('/item/search','sub_content');"><i class="fa fa-circle-o"></i> Charges OPD</a></li>
                <li><a href="javascript:load_form_div('/Item_IPD/search','sub_content');"><i class="fa fa-circle-o"></i> Charges IPD</a></li>
                <li><a href="javascript:load_form_div('/Package/search','sub_content');"><i class="fa fa-circle-o"></i> Package IPD</a></li>
                <li><a href="javascript:load_form_div('/Lab_Admin/report_list','sub_content');"><i class="fa fa-circle-o"></i> Lab Admin</a></li>
                <li><a href="javascript:load_form_div('/Doc_Admin/doc_list','sub_content');"><i class="fa fa-circle-o"></i> Doc Admin</a></li>
            </ul>
          </li>
		 <?php } ?>
        </ul>
      </div>
    </nav> 
  </div>
</div>
<div class="row" >
  <div class="col-md-12">
      <div id="sub_content" ></div>
  </div>
</div>
