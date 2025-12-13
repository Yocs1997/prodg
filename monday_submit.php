<?php
// Handles submissions from index.html and pushes data + files to Monday.com.
// Configure via environment variables when possible:
//   MONDAY_TOKEN      – Your Monday.com API token
//   MONDAY_BOARD_ID   – Target board ID

$MONDAY_TOKEN = getenv('MONDAY_TOKEN') ?: 'REPLACE_WITH_YOUR_MONDAY_TOKEN';
$BOARD_ID     = getenv('MONDAY_BOARD_ID') ?: '18391883391';

if ($MONDAY_TOKEN === 'REPLACE_WITH_YOUR_MONDAY_TOKEN' || empty($MONDAY_TOKEN)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['result' => 'error', 'message' => 'Monday API token is not configured.']);
    exit;
}

function monday_graphql(string $token, string $query, array $variables = []): array
{
    $ch = curl_init('https://api.monday.com/v2');
    $payload = ['query' => $query, 'variables' => $variables];

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: {$token}",
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($resp, true);
    if ($http >= 400) {
        throw new Exception("HTTP {$http}: {$resp}");
    }
    if (isset($json['errors'])) {
        throw new Exception('Monday errors: ' . json_encode($json['errors']));
    }

    return $json;
}

/**
 * Upload a single file to a Monday.com file column using the GraphQL multipart spec.
 */
function monday_add_file_to_column(string $token, string $itemId, string $columnId, string $tmpPath, string $filename): array
{
    $mutation = 'mutation ($file: File!, $itemId: ID!, $columnId: String!) {
  add_file_to_column(file: $file, item_id: $itemId, column_id: $columnId) { id }
}';

    $operations = json_encode([
        'query'     => $mutation,
        'variables' => [
            'file'     => null, // placeholder populated via the map + multipart body
            'itemId'   => (int) $itemId,
            'columnId' => (string) $columnId,
        ],
    ]);

    $map = json_encode(['file' => ['variables.file']]);

    $postFields = [
        'operations' => $operations,
        'map'        => $map,
        'file'       => new CURLFile(
            $tmpPath,
            mime_content_type($tmpPath) ?: 'application/octet-stream',
            $filename
        ),
    ];

    $ch = curl_init('https://api.monday.com/v2/file');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: {$token}"],
        CURLOPT_POSTFIELDS     => $postFields,
    ]);

    $resp = curl_exec($ch);
    if ($resp === false) {
        throw new Exception('Upload cURL error: ' . curl_error($ch));
    }

    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($resp, true);
    if ($http >= 400) {
        throw new Exception("Upload HTTP {$http}: {$resp}");
    }
    if (isset($json['errors'])) {
        throw new Exception('Upload errors: ' . json_encode($json['errors']));
    }

    return $json;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method Not Allowed');
    }

    // ---- Read fields ----
    $fullName        = trim($_POST['fullName'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $vin             = trim($_POST['vin'] ?? '');
    $color           = trim($_POST['color'] ?? '');
    $mileage         = trim($_POST['mileage'] ?? '');
    $durationLabelId = trim($_POST['duration'] ?? ''); // expects 2 or 3 from the dropdown
    $notes           = trim($_POST['notes'] ?? '');

    if ($fullName === '' || $phone === '' || $email === '' || $vin === '' || $color === '' || $mileage === '' || $durationLabelId === '') {
        throw new Exception('Missing required fields.');
    }

    // ---- Column IDs ----
    $COL = [
        'phone'      => 'phone_mkyk6fyq',
        'email'      => 'email_mkyjr5zs',
        'vin'        => 'text_mkyj99cg',
        'color'      => 'text_mkyjmxt0',
        'mileage'    => 'numeric_mkyjctfg',
        'duration'   => 'dropdown_mkykwmea',
        'notes'      => 'text_mkyjaskr',
        'titleFront' => 'file_mkyj8gft',
        'titleBack'  => 'file_mkyjz76w',
        'idPicture'  => 'file_mkyj3x9q',
    ];

    // ---- Build column_values for create_item ----
    $columnValues = [
        $COL['phone']   => ['phone' => $phone, 'countryShortName' => 'US'],
        $COL['email']   => ['email' => $email, 'text' => $email],
        $COL['vin']     => $vin,
        $COL['color']   => $color,
        $COL['mileage'] => is_numeric($mileage) ? (float) $mileage : $mileage,
        $COL['notes']   => $notes,
        // Monday dropdown expects an array of label IDs
        $COL['duration'] => ['labels' => [(int) $durationLabelId]],
    ];

    $mutation = 'mutation($boardId: ID!, $itemName: String!, $columnValues: JSON!) {
  create_item(board_id: $boardId, item_name: $itemName, column_values: $columnValues) { id }
}';

    $vars = [
        'boardId'      => (string) $BOARD_ID,
        'itemName'     => $fullName,
        'columnValues' => json_encode($columnValues), // Monday expects a JSON string here
    ];

    $created = monday_graphql($MONDAY_TOKEN, $mutation, $vars);
    $itemId  = $created['data']['create_item']['id'] ?? null;
    if (!$itemId) {
        throw new Exception('Could not create Monday item.');
    }

    // ---- Upload files to file columns ----
    $fileMap = [
        'titleFront' => $COL['titleFront'],
        'titleBack'  => $COL['titleBack'],
        'idPicture'  => $COL['idPicture'],
    ];

    foreach ($fileMap as $fieldName => $columnId) {
        if (!isset($_FILES[$fieldName])) {
            continue;
        }

        if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload error for {$fieldName}: " . $_FILES[$fieldName]['error']);
        }

        if (empty($_FILES[$fieldName]['tmp_name']) || !is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
            throw new Exception("No tmp file for {$fieldName}. Details: " . json_encode($_FILES[$fieldName]));
        }

        monday_add_file_to_column(
            $MONDAY_TOKEN,
            (string) $itemId,
            $columnId,
            $_FILES[$fieldName]['tmp_name'],
            $_FILES[$fieldName]['name']
        );
    }

    header('Content-Type: application/json');
    echo json_encode(['result' => 'success', 'itemId' => $itemId]);
} catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['result' => 'error', 'message' => $e->getMessage()]);
}
