<?php
$conn = new mysqli('localhost', 'root', '', 'hms_ci4_2026');

$sql = "SELECT i.id, i.ipd_code, p.p_code, p.p_fname, p.p_rname,
               concat('Bed No :', b.bed_number, '[', w.ward_name, ']') as Bed_Desc,
               date_format(i.register_date,'%d-%m-%Y') as str_register_date,
               if(i.ipd_status = 0,(to_days(sysdate()) - to_days(i.register_date)),(to_days(i.discharge_date) - to_days(i.register_date))) as no_days,
               ipd_doc_list.doc_name as doc_name,
               ipd_doc_list.doc_list as doc_list,
               i.r_doc_id,
               i.r_doc_name
        FROM ipd_master i
        JOIN patient_master p ON i.p_id = p.id
        LEFT JOIN (
            select max(id) as id, ipd_id from bed_assignment_history group by ipd_id
        ) bah_latest ON bah_latest.ipd_id = i.id
        LEFT JOIN bed_assignment_history bah ON bah.id = bah_latest.id
        LEFT JOIN bed_master b ON b.id = bah.bed_id
        LEFT JOIN ward_master w ON w.id = bah.ward_id
        LEFT JOIN (
            select i.ipd_id,
                group_concat(distinct concat_ws(' ', 'Dr.', d.p_fname, d.p_mname, d.p_lname)) as doc_name,
                group_concat(distinct d.id) as doc_list
            from ipd_master_doc_list i
            join doctor_master d on i.doc_id = d.id
            group by i.ipd_id
        ) ipd_doc_list ON i.id = ipd_doc_list.ipd_id
        WHERE i.ipd_status = 0";

$res = $conn->query($sql);
echo "HMS CURRENT ADMISSIONS QUERY RESULTS (" . $res->num_rows . " rows):\n\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
