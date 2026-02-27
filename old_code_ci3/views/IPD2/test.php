@extends('app')

@section('content')
{!! header('Access-Control-Allow-Origin: *'); !!}
{!! header("content-type: Access-Control-Allow-Methods: GET");!!}
<div class="container">
	<div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="alert alert-info text-center">
                Project -  {!! $project_title !!}
            </div>
            <div class="panel panel-success">
                    <div class="panel-heading">Enter Flat/Room Booking Details</div>
                    <div class="panel-body">
                        {!! Form::open(array('route' => 'client.store', 'method' => 'post', 'files' => 'true')) !!}
                        {!! Form::hidden('user_id', Auth::user()->id) !!}
                        {!! Form::hidden('flat_id', $flat_id )!!}
                        {!! Form::hidden('booking_status', $booking_status )!!}
                            <div class="col-md-12">Fields with <span style="color:red">*</span> asteric sign are required</div>
                            <div id="jsError" class="col-md-12"></div>
                            <div class="form-group {!! $errors->has('cust_type') ? 'has-error':'' !!}" >
                                {!! $errors->first('cust_type','<span class="help-block">:message</span>') !!}
                            <div class="col-md-4">
                                <label for="cust_type">New Customer</label>
                               {!! Form::radio('cust_type', 'new', Input::old('cust_type'), array('id'=>'cust_new' )) !!}                      
                            </div>
                            <div class="col-md-4">
                               <label for="cust_type">Old Customer</label> 
                                {!! Form::radio('cust_type', 'old', Input::old('cust_type'), array('id'=>'cust_old')) !!}
                                </div>
                               
                          </div>
                        <div class="col-md-4"></div>
                        
                        <div class="col-md-6">
                            <div class="form-group" >
                            <label for="franchise">Franchise Name</label>
                              {!! Form::text('franchise', Auth::user()->name, array('id'=>'franchise','class' => 'form-control', 'readonly'=>'true', 'placeholder'=> Auth::user()->name ,'value'=>Auth::user()->name)) !!}
                          </div>
                        </div>
                        <div class="col-md-6">
                             <label for="cust_code">Agent Name</label>
                              {!! Form::text('agent', Input::old('agent'), array('id'=>'agent','class' => 'form-control')) !!}
                        
                        </div>
                        
                        
                         <div class="col-md-12">
                            <div class="form-group">
                            <label for="cust_code">Project Name</label>
                                {!! Form::text('project_name', $project_title, array('id'=>'project_name','class' => 'form-control', 'placeholder' => $project_title, 'readonly'=>'true',  )) !!}
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group {!! $errors->has('area') ? 'has-error':'' !!}">
                                <label for="area"><span style="color:red">*</span>Flat / Room Area <span style="font-size:9px;">(only numbers)</span></label>
                                {!! Form::text('area', Input::old('area'), array('id'=>'area','class' => 'form-control', 'placeholder' => 'Enter Area')) !!}
                                 {!! $errors->first('area','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>
                        
                         <div class="col-md-5">
                            <div class="form-group ">
                                <label for="area">Estimated Date</label>
                                {!! Form::text('estimated_date', Input::old('estimated_date'), array('id'=>'estimated_date','class' => 'form-control', 'placeholder' => 'Select Date')) !!}
                                
                            </div>
                        </div>
                        
                        <div class="col-md-12" id = "customer_code_status" >
                        <div class="col-md-6">
                            <div class="form-group" >
                            <label for="cust_code">Customer Code</label>
                              {!! Form::text('cust_code', Input::old('cust_code'), array('id'=>'cust_code','class' => 'form-control', 'placeholder' => 'Enter Customer Code')) !!}
                               
                          </div>
                        </div>
                        <div class="col-md-6">
                             <label for="cust_code">Customer Status</label>
                              {!! Form::text('cust_status', Input::old('cust_status'), array('id'=>'cust_status','class' => 'form-control', 'readonly')) !!}
                        
                        </div>
                        </div>
                    <div class="col-md-12">
                        
                          <div class="form-group {!! $errors->has('customers_name') ? 'has-error':'' !!}" >
                            <label for="customers_name"><span style="color:red">*</span>Customer Name</label>
                              {!! Form::text('customers_name', Input::old('customers_name'), array('id'=>'customers_name','class' => 'form-control', 'placeholder' => 'Enter Customer Name')) !!}
                               {!! $errors->first('customers_name','<span class="help-block">:message</span>') !!}
                          </div>
                      
                      
                    </div>   
                  
                        <div class="col-md-6"> 
                            <div class="form-group {!! $errors->has('phone') ? 'has-error':'' !!}" >
                                <label for="client_lastname"><span style="color:red">*</span>Phone Number</label>
                                  {!! Form::text('phone', Input::old('phone'), array('id'=>'phone','class' => 'form-control', 'placeholder' => 'Enter Phone Number')) !!}
                                   {!! $errors->first('phone','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group {!! $errors->has('email') ? 'has-error':'' !!}" >
                                <label for="email"><span style="color:red">*</span>Email Address</label>
                                  {!! Form::text('email', Input::old('email'), array('id'=>'email','class' => 'form-control', 'placeholder' => 'Enter Email Address')) !!}
                                   {!! $errors->first('email','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>
                    <div class="col-md-12"> 
                           <div class="form-group {!! $errors->has('address') ? 'has-error':'' !!}">
                                <label for="address"><span style="color:red">*</span>Address</label>
                                   {!! Form::textarea('address', Input::old('address'),array('id'=>'address','class' => 'form-control','rows'=>'5','placeholder'=>'Address')) !!}
                               {!! $errors->first('address','<span class="help-block">:message</span>') !!}
                                
                              </div>
                        </div>
                        
                       
                      <div class="col-md-12"> 
                               <div class="form-group">
                                <label for="description">Remark</label>
                                   {!! Form::textarea('description', Input::old('description'),array('class' => 'form-control','rows'=>'5')) !!}
                                
                              </div>
                        </div>
                         <div class="col-md-12"> 
                        <div class="checkbox {!! $errors->has('terms_conditions') ? 'has-error':'' !!}">
                                <label>
                                   {!!  Form::checkbox('terms_conditions', '1', Input::old('terms_conditions'),  array()) !!} <span style="color:red">*</span>I authorize Krishna Buildcon to contact me.
                                </label>
                             {!! $errors->first('terms_conditions','<span class="help-block">:message</span>') !!}
                              </div>
                        </div>
                        <div class="col-md-12">
                              <button type="submit" class="btn btn-success">Book Flat / Room</button>
                              <a href="{!! url('/floor/'.$floor_id.'/flats') !!}" class="btn btn-danger">Cancel</a>
                        </div> 
                        {!! Form::close() !!}
                </div>
            </div>
        </div>
	</div>
</div>

<script type="application/javascript">
  
    $(document).ready(function() {
       $('#cust_code').on('change',function()
        {
            //alert($('#cust_code').val());
            $.post('http://61.16.222.101:8080/WebService/custinfo.aspx',
            { "cc":$('#cust_code').val() }, function(data){
            if(data.Error==1)
                    {
                        $('#jsError').html(data.error_text);
                    }else
                    {

                        $("#customers_name").val(data.customers_Name);
                        
                    }
            },'json');
        });	
        
    });
    
function test(){
    alert('success');
}
     
</script>

@endsection