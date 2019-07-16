<?php

// Information required about your WHMCS and Virtualmin installations
$whmcs_root_directory = ""; // e.g. /home/domain.com/public_html (no trailing slash)
$virtualmin_username  = "";
$virtualmin_password  = "";
$virtualmin_url       = ""; // e.g. https://cp.domain.com:10000 (no trailing slash)

// Uncomment one or the other if you want to read from disk instead of the API. Useful to study API output.
$flag = 'output'; // Write the API results to a file on disk
//$flag = 'readfile'; // Read the file on disk instead of calling the API

require_once "$whmcs_root_directory/configuration.php";

error_reporting(0); // Turn off error checking otherwise we get unknown output and script doesn't work properly

if ($flag == 'readfile') {
    $api_result = file_get_contents('virtualmin_api_output.txt');
} else {
    $api_result = shell_exec("wget -O - --quiet --http-user=$virtualmin_username --http-passwd=$virtualmin_password --no-check-certificate '$virtualmin_url/virtual-server/remote.cgi?program=list-domains&multiline'");
    if ($flag == 'output') file_put_contents('virtualmin_api_output.txt', $api_result);
}

$input = explode("\n", $api_result);
$lines = array_splice($input, 0, -3);

$hosting[]      = $lines[0];
$current_domain = $lines[0];

$parent_domain = "";

foreach ($lines as $line) { //                123456789012345678901234567890

    if (substr($line, 0, 10) == '    Type: ') {
        $type = substr($line, 10);
    }

    if (substr($line, 0, 19) == '    Parent domain: ') {
        $parent_domain = substr($line, 19);
    }

    if (substr($line, 0, 26) == '    Bandwidth byte limit: ') {
        $bwlimit = substr($line, 26);
        if ($bwlimit == 'Unlimited') {
            $hosting[$current_domain]['bwlimit'] = 0;
        } else {
            $hosting[$current_domain]['bwlimit'] = $bwlimit / 1024 / 1024;
        }
    }

    if (substr($line, 0, 26) == '    Bandwidth byte usage: ') {
        $hosting[$current_domain]['bwusage'] = substr($line, 26) / 1024 / 1024;
    }

    if (substr($line, 0, 24) == '    Server block quota: ') {
        $disklimit = substr($line, 24);
        if ($disklimit == 'Unlimited') {
            $hosting[$current_domain]['disklimit'] = 0;
        } else {
            $hosting[$current_domain]['disklimit'] = $disklimit / 1024;
        }
    }

    if (substr($line, 0, 28) == '    Server byte quota used: ') {
        $hosting[$current_domain]['diskusage'] = substr($line, 28) / 1024 / 1024;
    }

    if (substr($line, 0, 25) == '    Databases byte size: ') {
        $dbusage                             = substr($line, 25) / 1024 / 1024;
        $hosting[$current_domain]['dbusage'] = $dbusage;
        if ($type == 'Sub-server') {
            $hosting[$parent_domain]['dbusage']   += $dbusage;
            $hosting[$parent_domain]['diskusage'] += $dbusage;
        }
    }

    if (substr($line, 0, 4) != '    ') {
        $current_domain = $line;
        if ($type == "Top-level server") $parent_domain = "";
    }

}

$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

foreach ($hosting as $key => $value) {
    $sql = "UPDATE tblhosting 
        SET disklimit={$value['disklimit']}, diskusage={$value['diskusage']},
            bwlimit={$value['bwlimit']}, bwusage={$value['bwusage']},             
            lastupdate = now()
         WHERE domain = '$key'";
    if ($conn->query($sql) === TRUE) {
        echo "$key\n";
    }

}
