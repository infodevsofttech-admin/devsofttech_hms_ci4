<style>
    @page {

        margin-top: 3.5cm;
        margin-bottom: 1.2cm;
        margin-left: 0.5cm;
        margin-right: 0.5cm;

        margin-header: 0.5cm;
        margin-footer: 0.5cm;
        header: html_myHeader;
        footer: html_myFooter;

    }

    body {
        font-family: freeserif;
    }

    table {
        border-collapse: collapse;
    }

    thead {
        vertical-align: bottom;
        text-align: center;
        font-weight: bold;
    }

    tfoot {
        text-align: center;
        font-weight: bold;
    }

    th {
        text-align: left;
        padding-left: 0.35em;
        padding-right: 0.35em;
        padding-top: 0.35em;
        padding-bottom: 0.35em;
        vertical-align: top;
    }

    td {
        padding-left: 0.35em;
        padding-right: 0.35em;
        padding-top: 0.35em;
        padding-bottom: 0.35em;
        vertical-align: top;
    }

    p,
    td {
        font-family: freeserif;
        font-size: 11pt
    }
</style>

<htmlpageheader name="myHeader">
    <?php
    if ($print_type == 0) { ?>
        <table cellspacing="0" style="font-size: 10px;width:100%;border-style: inset;">
            <tr>
                <td style="width: 100px;vertical-align: top;">
                    <img style="width: 100px;vertical-align: top;" src="assets/images/<?= H_logo ?>" />
                </td>
                <td style="width: 400px;vertical-align: top; text-align:center;">
                    <span style="font-size: 32px;color:darkblue;"><b><?= H_Name ?></b></span><br />
                    <span style="font-size: 12px"><?= H_address_1 ?><br /><?= H_address_2 ?><br>
                        <?php
                        if (H_phone_No != '') {
                            echo '<b>Phone: </b> ' . H_phone_No;
                        }
                        ?>
                        <?php
                        if (H_Email != '') {
                            echo '   <b>Email: </b> ' . H_Email;
                        }
                        ?>
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
<hr />