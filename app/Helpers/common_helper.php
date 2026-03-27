<?php

function str_to_MysqlDate(string $strDate): string
{
    $strDate = trim($strDate);

    if ($strDate !== '') {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $strDate) === 1) {
            return $strDate;
        }

        $date = explode('/', $strDate);
        if (count($date) === 3) {
            return $date[2] . '-' . $date[1] . '-' . $date[0];
        }
    }

    return '1900-01-01';
}

function MysqlDate_to_str(?string $strDate): string
{
    if ($strDate === null || $strDate === '') {
        return '';
    }

    $date = explode('-', $strDate);
    if (count($date) !== 3) {
        return '';
    }

    return $date[2] . '/' . $date[1] . '/' . $date[0];
}

function radio_checked($rvalue, $dvalue): string
{
    return ((string) $rvalue === (string) $dvalue) ? 'checked' : '';
}

function combo_checked($rvalue, $dvalue): string
{
    return (strtoupper((string) $rvalue) === strtoupper((string) $dvalue)) ? 'selected' : '';
}

function checkbox_checked($value): string
{
    return ((int) $value > 0) ? 'checked' : '';
}

function Doc_Value($doc_id): string
{
    return 'Hello';
}

function number_to_word($number): string
{
    $no = round((float) $number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = strlen((string) $no);
    $i = 0;
    $str = [];
    $words = [
        '0' => '', '1' => 'one', '2' => 'two',
        '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six',
        '7' => 'seven', '8' => 'eight', '9' => 'nine',
        '10' => 'ten', '11' => 'eleven', '12' => 'twelve',
        '13' => 'thirteen', '14' => 'fourteen',
        '15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen',
        '18' => 'eighteen', '19' => 'nineteen', '20' => 'twenty',
        '30' => 'thirty', '40' => 'forty', '50' => 'fifty',
        '60' => 'sixty', '70' => 'seventy',
        '80' => 'eighty', '90' => 'ninety',
    ];
    $digits = ['', 'hundred', 'thousand', 'lakh', 'crore'];

    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str[] = ($number < 21)
                ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred
                : $words[floor($number / 10) * 10]
                . ' ' . $words[$number % 10] . ' '
                . $digits[$counter] . $plural . ' ' . $hundred;
        } else {
            $str[] = null;
        }
    }

    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point)
        ? '.' . $words[$point / 10] . ' ' . $words[$point = $point % 10]
        : '';

    return (string) ucwords($result) . ' Only' . $points;
}

function ExportExcel(string $table, string $filename): void
{
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '_' . date('dMy') . '.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo $table;
}

function objectToArray($d)
{
    if (is_object($d)) {
        $d = get_object_vars($d);
    }

    if (is_array($d)) {
        return array_map(__FUNCTION__, $d);
    }

    return $d;
}

function compare_arrays(array $data1, array $data2): string
{
    $data_change = '';

    foreach ($data2 as $key => $value) {
        if (isset($data1[$key]) && strtoupper((string) $key) !== 'LOG') {
            if ($value != $data1[$key]) {
                $data_change .= 'Field :' . $key . ' Old Value:{' . $data1[$key]
                    . '} => New Value:{' . $value . '}' . PHP_EOL;
            }
        }
    }

    return $data_change;
}

function cal_exp(string $exp)
{
    $string = $exp;
    $result = 'Not Valid';
    $pattern = '~^[0-9()+\-*\/.]+$~';
    if (preg_match($pattern, $string)) {
        $math_string = 'return (' . $string . ');';
        $result = floatval(@eval($math_string));
    }

    if (is_numeric($result)) {
        round($result, 2);
    }

    return $result;
}

if (! function_exists('hospital_setting_value')) {
    function hospital_setting_value(string $name, string $default = ''): string
    {
        static $settings = null;

        if ($settings === null) {
            $settings = [];

            try {
                $db = db_connect();
                if ($db && method_exists($db, 'tableExists') && $db->tableExists('hospital_setting')) {
                    $rows = $db->table('hospital_setting')
                        ->select('s_name, s_value')
                        ->get()
                        ->getResultArray();

                    foreach ($rows as $row) {
                        $key = trim((string) ($row['s_name'] ?? ''));
                        if ($key === '') {
                            continue;
                        }

                        $settings[$key] = trim((string) ($row['s_value'] ?? ''));
                    }
                }
            } catch (\Throwable $e) {
                $settings = [];
            }
        }

        return array_key_exists($name, $settings) ? (string) $settings[$name] : $default;
    }
}

if (! function_exists('hms_footer_version')) {
    function hms_default_version_id(): string
    {
        return date('y') . '.' . str_pad((string) ((int) date('z') + 1), 3, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('hms_footer_version')) {
    function hms_footer_version(string $default = ''): string
    {
        foreach (['HMS_UPDATE_ID', 'HMS_VERSION_NO', 'APP_VERSION_NO'] as $key) {
            $value = hospital_setting_value($key, '');
            if ($value !== '') {
                return $value;
            }
        }

        return $default !== '' ? $default : hms_default_version_id();
    }
}
