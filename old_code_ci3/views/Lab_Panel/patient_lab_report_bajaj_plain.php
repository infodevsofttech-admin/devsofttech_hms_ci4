<style>
    @page {
        background: url('assets/images/baja_path_background.jpg') no-repeat 0 0;
        background-image-resize: 6;

        margin-top: 8.5cm;
        margin-bottom: 4.5cm;
        margin-left: 1cm;
        margin-right: 0.5cm;

        margin-header: 5.1cm;
        margin-footer: 4.2cm;
        header: html_myHeader;
        footer: html_myFooter;
    }

    body {
        font-family: times;
        font-size: 10pt;
    }

    h1 {
        font-size: 12pt;
    }

    h3 {
        font-size: 10pt;
    }
</style>

<htmlpageheader name="myHeader">
    <?= $report_header ?>
</htmlpageheader>

<htmlpagefooter name="LastPageFooter">
    <table border="0" style="width:100%;font-size:14px;">
        <tr>
            <td>
                <!-- <img width="100px" src="'.$sign_image_file.'"  />  -->
            </td>
            <td style="text-align:right">

            </td>
        </tr>
        <tr>
            <td>
                <b><?= nl2br($tech_name) ?></b>
            </td>
            <td style="text-align:right">
                <b><?= $docname ?></b><br />
                <?= $docedu ?>
            </td>
        </tr>

    </table>
</htmlpagefooter>
<?= $complete_report ?>

<p align="center">###################### END OF REPORT ######################</p>
<sethtmlpagefooter name="LastPageFooter" value="1" />