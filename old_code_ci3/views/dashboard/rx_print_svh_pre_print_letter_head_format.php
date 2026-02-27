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
    top: 80mm; 
    left: 10mm; 
    width: 175mm;   /* you must specify a width */
    margin: 0;
    padding: 0;
}

table p {
    font-size: 12px;
}

th, td {
  
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
            
            if(strlen($Complaint)>0){
                echo $Complaint;
                echo '<br/>';
            }
            
            if(strlen($vital_content)>0){
                echo $vital_content;
                echo '<br/>';
            }
            
                        
            if(strlen($diagnosis)>0){
                echo $diagnosis;
                echo '<br/>';
            }
            
            echo $medical;
            echo '<br/>';
            
            if(strlen($investigation)>0){
                echo $investigation;
                echo '<br/>';
            }

            if(strlen($Finding_Examinations)>0){
                echo $Finding_Examinations;
                echo '<br/>';
            }

            if(strlen($Prescriber_Remarks)>0){
                echo $Prescriber_Remarks;
                echo '<br/>';
            }
            
            
            if(strlen($advice)>0){
                echo $advice;
                echo '<br/>';
            }

            if(strlen($next_visit)>0){
                echo $next_visit;
                echo '<br/>';
            }

            if(strlen($refer_to)>0){
                echo $refer_to;
                echo '<br/>';
            }
            echo '<br/>';
            

            echo '<p align="Right">';
            echo '<br/>';
            echo '<br/>';
            echo '<br/>';
            echo '<b>'.$doc_name.'</b>';
            echo '<br/>';
            echo $doc_sign;
            echo '</p>';
        ?>
</div>