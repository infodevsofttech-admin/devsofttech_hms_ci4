<style>
    @page {

        margin-top: 4.2cm;
        margin-bottom: 2.5cm;
        margin-left: 0.8cm;
        margin-right: 0.8cm;

        margin-header: 0.5cm;
        margin-footer: 0.5cm;
        header: html_myHeader;
        footer: html_myFooter;

    }
</style>

<htmlpageheader name="myHeader">
    <?php
    if ($print_type == 0) { ?>
        <table cellspacing="0" style="font-size: 10px;width:100%;border-style: inset;">
            <tr>
                <td style="width: 80px;vertical-align: top;">
                    <img style="width: 80px;vertical-align: top;" src="assets/images/<?= H_logo ?>" />
                </td>
                <td style="width: 700px;vertical-align: top; text-align:center;">
                    <span style="font-size: 25px;font-weight: bold;color:darkblue;"><b><?= H_Name ?></b></span><br />
                    <span style="font-size: 15px">Near khatu Shyam Mandir Kathgodam Bypass Road Mukhani Haldwani (Nainital) U.K 263139 <br>
                        Phone. No. <b>05946-310459 ,  +91 89795 65771</b> 
                    </span>
                </td>
            </tr>
        </table>
    <?php } ?>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table width="100%">
        <tr>
            <td width="33%">Page : {PAGENO}/{nbpg}</td>
            <td width="66%" style="text-align: right;"><i><?= $report_foot ?></i></td>
        </tr>
    </table>
</htmlpagefooter>
<?= $complete_report ?>