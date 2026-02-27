<style>
    @page {

        margin-top:4.2cm;
        margin-bottom: 1.2cm;
        margin-left: 0.5cm;
        margin-right: 0.5cm;

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
                <td style="width: 500px;vertical-align: top; text-align:center;">
                    <span style="font-size: 25px;font-weight: bold;color:darkblue;"><b><?= H_Name ?></b></span><br />
                    <span style="font-size: 15px">An ISO Certified 9001 : 2015, <b>NABH</b>(Fully Accredited) Hospital<br>
                        Ramnagar Road, KASHIPUR - 244713 (U.S.Nagar) Uttarakhand<br />
                        Mob. No. <b>9411166999</b> / E Mail : chamunda_surgical@rediffmail.com
                    </span>
                </td>
                <td style="width: 80px;vertical-align: top;text-align: right;">
                    <img style="width: 80px;vertical-align: top;" src="assets/images/<?= H_NABH ?>" />
                </td>
            </tr>
        </table>
        <hr />
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