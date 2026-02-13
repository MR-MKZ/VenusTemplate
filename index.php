<?php

if ((isset($_SERVER['HTTP_USER_AGENT']) and empty($_SERVER['HTTP_USER_AGENT'])) or !isset($_SERVER['HTTP_USER_AGENT'])) {
    header('Location: /');
    exit();
}

if (!function_exists('str_contains'))
    die('Please upgrade your PHP version to 8 or above');
$isTextHTML = str_contains(($_SERVER['HTTP_ACCEPT'] ?? ''), 'text/html');

// ======================================================================= //


const LICENSE = "paste-your-license-token-here";

const APP_LIST = "https://watchwithme.ir/static/apps.json"; // github LIST of your app.json config
const OS_LIST = "https://watchwithme.ir/static/os.json"; // github link of your os.json config

const BASE_URL = "https://your-panel.com:8080"; // Replace IP address and port and set https for SSL
// if port is 80 or 443 you don't need to add it.


// ======================================================================= //

$URL = BASE_URL . $_SERVER['REQUEST_URI'] ?? '';
$URL .= $isTextHTML ? '/info' : '';

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 17);
curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
if (curl_error($ch)) {
    throw new Exception('Error fetching usage: ' . curl_error($ch));
}
curl_close($ch);


$header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
$response = trim(str_replace($header_text, '', $response));
$user = json_decode($response, true);

function getDateByDaysAgo($daysAgo) {
    $today = new DateTime();
    $today->modify("-{$daysAgo} days");
    return $today->format("Y-m-d");
}

function getUsage($start, $end) {;
    $url = sprintf(
        "%s/usage?start=%s%%2000:00:00&end=%s%%2023:59:59",
        BASE_URL,
        urlencode($start),
        urlencode($end)
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 17);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('Error fetching usage: ' . curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($result, true);
    return $data;
}

$usages = [0, 0, 0];

for ($i = 0; $i < count($usages); $i++) {
    $date = getDateByDaysAgo($i);

    try {
        $response = getUsage($date, $date);
    } catch (Exception $e) {
        error_log($e->getMessage());
        $response = null;
    }

    if ($response && isset($response['usages']) && is_array($response['usages'])) {
        foreach ($response['usages'] as $usage) {
            if (isset($usage['used_traffic'])) {
                $usages[$i] += $usage['used_traffic'];
            }
        }
    }
}


if ($isTextHTML) {
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet" />
        <title>Venus Template</title>
        <style>
            * {
                font-family: "Vazirmatn";
            }
        </style>
        <script>
            function authenticateAndLoadApp() {



                // =========================================================
                const license = "<?= LICENSE;?>";
                // =========================================================


                // ===================Configuration Zone====================
                const appList = "<?= APP_LIST?>"; // github link of your app.json config
                const osList = "<?= OS_LIST?>"; // github link of your os.json config
                // =========================================================


                const text = document.getElementById("text");

                localStorage.removeItem("authToken");

                localStorage.setItem("appList", appList);
                localStorage.setItem("osList", osList);

                fetch('https://watchwithme.ir/api/v1/sub/auth', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            license
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data) {
                            const token = data.token;

                            localStorage.setItem("authToken", token);
                            localStorage.setItem("HostMode", true);
                            localStorage.setItem('VenusInfo', JSON.stringify(<?= json_encode($user); ?>));
                            localStorage.setItem("VenusUsages", JSON.stringify(<?= json_encode($usages); ?>));

                            fetch(`https://watchwithme.ir/api/v1/sub/${license}`, {
                                    headers: {
                                        "Authorization": `Bearer ${token}`
                                    }
                                })
                                .then(res => {
                                    if (res.status == 401) {
                                        text.innerHTML = "License is expired or invalid."
                                        throw new Error("Invalid or expired license.");
                                    } else {
                                        return res.text();
                                    }
                                })
                                .then(html => {
                                    document.open();
                                    document.write(html);
                                    document.close();
                                })
                        } else {
                            alert('Authentication failed: ' + data.message);
                        }
                    })
                    .catch(err => {
                        text.innerHTML = "Authentication failed!"
                        console.error('Error during authentication:', err);
                    });
            }

            window.onload = function() {
                authenticateAndLoadApp();
            };
        </script>
    </head>

    <body style="background: #E6E8F6;">
        <h1 id="text" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">Loading...</h1>
    </body>

    </html>
<?php
    return;
}

$isOK = false;
foreach (explode("\r\n", $header_text) as $i => $line) {
    if ($i === 0)
        continue;
    list($key, $value) = explode(': ', $line);
    if (in_array($key, ['content-disposition', 'content-type', 'subscription-userinfo', 'profile-update-interval'])) {
        header("$key: $value");
        $isOK = true;
    }
}

if (!$isTextHTML and !$isOK)
    die('Error !' . __LINE__);


echo $response;

?>
