<?php
    if(count($opd_patient_advice)>0){
        echo '<table class="table">';
        foreach($opd_patient_advice as $row)
        {
            echo '<tr>';
            echo '<td width="80%">'.$row->advice_txt.'<br/>'.$row->advice_txt_hindi.'</td><td><a href="javascript:del_advice('.$row->id.')" >Del</a></td>';
            echo '</tr>';
        }

        echo '</table>';
    }
?>