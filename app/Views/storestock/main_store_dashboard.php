<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
<section class="content-header">
    <h1>Store Main <small>Panel</small></h1>
</section>
<section class="content">
    <div class="module-hero">
        <h4>Store Operations</h4>
        <p>Use grouped controls to manage masters, inventory, and procurement operations.</p>
    </div>

    <div class="menu-grid">
        <div class="menu-section">
            <div class="title">Master Controls</div>
            <div class="menu-list">
                <a href="javascript:load_form_div('/Storestock/SupplierList','maindiv','Supplier : Store');">
                    <i class="fa fa-hand-pointer-o"></i> Supplier
                </a>
                <a href="javascript:load_form_div('/product_stock_master/drug_master_list','maindiv','Drug Master : Store');">
                    <i class="fa fa-hand-pointer-o"></i> Item Master
                </a>
                <a href="javascript:load_form_div('/product_master/company_master_list','maindiv','Drug Company : Store');">
                    <i class="fa fa-hand-pointer-o"></i> Company
                </a>
                <a href="javascript:load_form_div('/Storestock/location_master_list','maindiv','Location : Store');">
                    <i class="fa fa-hand-pointer-o"></i> Location Master
                </a>
                <a href="javascript:load_form_div('/Master_data/Employee_master_list','maindiv','Employee : Store');">
                    <i class="fa fa-hand-pointer-o"></i> Employee Master
                </a>
            </div>
        </div>

        <div class="menu-section">
            <div class="title">Inventory Controls</div>
            <div class="menu-list">
                <a href="javascript:load_form_div('/Storestock/Purchase','maindiv','Purchase : Store');">
                    <i class="fa fa-hand-pointer-o"></i> Purchase
                </a>
                <a href="javascript:load_form_div('/Storestock/Purchase_return','maindiv','Purchase Return : Store');">
                    <i class="fa fa-hand-pointer-o"></i> Purchase Return
                </a>
            </div>
        </div>
    </div>
</section>
</div>
