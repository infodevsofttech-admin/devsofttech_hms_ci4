<?php
$conn = new mysqli('localhost', 'root', '', 'hms_ci4_2026');

$docId = 1;
$sql = "SELECT i.id as ipd_id, i.ipd_code, i.p_id, i.r_doc_id, i.r_doc_name, i.register_date, i.problem,
               p.p_fname as fname, p.p_rname as rname, p.p_code as uhid, p.mphone1, p.gender, p.age, p.age_in_month, p.estimate_dob, p.dob,
               concat('Bed No :', coalesce(b.bed_number, b.bed_code, 'N/A'), ' [', coalesce(w.ward_name, 'General Ward'), ']') as Bed_Desc,
               b.bed_code, b.bed_number, w.ward_name,
               ipd_doc_list.doc_name as assigned_doctors,
               ipd_doc_list.doc_list as doc_list
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

if ($docId > 0) {
    $sql .= " AND (FIND_IN_SET(" . (int) $docId . ", coalesce(ipd_doc_list.doc_list, '')) OR i.r_doc_id = " . (int) $docId . " OR i.r_doc_id IS NULL OR i.r_doc_id = 0)";
}

$res = $conn->query($sql);
echo "SQL Query Output (" . $res->num_rows . " rows):\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
