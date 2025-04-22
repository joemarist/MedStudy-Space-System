<?php
$client_id = "80047855417-dfsmenc4jgtr2me0vm4a5tl76s91bf45.apps.googleusercontent.com";
$id_token = $_POST['credential'] ?? null;

if ($id_token) {
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = file_get_contents($url);
    $payload = json_decode($response, true);

    if (isset($payload['email']) && $payload['aud'] === $client_id) {
        $email = $payload['email'];
        $domain = explode('@', $email)[1];

        if ($domain === 'usep.edu.ph') {
            header("Location: index.html?status=success");
            exit();
        } else {
            header("Location: index.html?status=denied");
            exit();
        }
    } else {
        header("Location: index.html?status=invalid");
        exit();
    }
} else {
    header("Location: index.html?status=missing");
    exit();
}
?>
