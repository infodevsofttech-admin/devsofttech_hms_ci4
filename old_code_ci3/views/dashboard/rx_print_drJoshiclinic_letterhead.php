<style>@page {
    margin-top: 6cm;
    margin-bottom: 1.2cm;
    margin-left: 0.5cm;
    margin-right: 0.5cm;

    margin-header:0.5cm;
    margin-footer:0.5cm;
    header: html_myHeader;
    footer: html_myFooter;
}

.RxPlace {
    position: absolute;
    overflow: visible;
    top: 73mm; 
    left: 70mm; 
    width: 135mm;   /* you must specify a width */
    margin: 0;
    padding: 0;
    
}

table p {
    font-size: 12px;
}

th, td {
  border-bottom: 1px solid #ddd;
  text-align: left;
  font-size: 12px;
}
</style>

<htmlpageheader name="myHeader">
<p align="center"></p>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<p align="center">Print Time : <?=date('d-m-Y H:i:s')?></p>
</htmlpagefooter>
<div class="RxPlace">
        <?php
            echo $vital_content;
            echo '<br/>';
            echo $Complaint;
            echo '<br/>';
            echo $Finding_Examinations;
            echo '<br/>';
            echo $Provisional_diagnosis;
            echo '<br/>';
            echo $diagnosis;
            echo '<br/>';
            echo $medical;
            echo '<br/>';
            echo $investigation;
            echo '<br/>';
            echo $advice;
            echo '<br/>';
            echo $next_visit;
            echo '<br/>';
            echo $refer_to;
            echo '<br/>';
            echo $doctor;
            echo '<br/>';

        ?>
</div>