<?php

/**
 * Helper untuk memanggil layanan ML (Python) di http://103.196.155.206:8000/predict
 * Versi ini SELARAS dengan model kp_smote.py yang pakai fitur:
 *   JK, Umur, BB, TB, LILA
 */

/**
 * Panggil layanan ML (Python) untuk memprediksi status gizi.
 *
 * @param int         $umur_bulan      Umur balita (bulan)
 * @param string      $jenis_kelamin   'L' atau 'P'
 * @param float       $berat_badan     kg
 * @param float       $tinggi_badan    cm
 * @param float|null  $lingkar_lengan  cm (boleh null, default 0 kalau null)
 *
 * @return array|null
 *   Contoh sukses:
 *   [
 *     'success'     => true,
 *     'class_id'    => 2,
 *     'class_label' => 'Gizi Baik',
 *     'status_gizi' => 'Gizi Baik'
 *   ]
 *   atau kalau error:
 *   [ 'error' => '...' ]
 */
function ml_predict_status_gizi(
    int $umur_bulan,
    string $jenis_kelamin,
    float $berat_badan,
    float $tinggi_badan,
    ?float $lingkar_lengan = null
): ?array {
    $url = " http://103.196.155.206/api/predict";

    // Payload HARUS cocok dengan yang diharapkan Python:
    //   JK, Umur, BB, TB, LILA
    $payload = [
        "JK" => $jenis_kelamin,             // 'L' / 'P'
        "Umur" => (string) $umur_bulan,       // boleh "24" atau "24 Bulan"
        "BB" => (string) $berat_badan,      // kg
        "TB" => (string) $tinggi_badan,     // cm
        "LILA" => $lingkar_lengan !== null
            ? (string) $lingkar_lengan
            : "0",
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $err = curl_error($ch);
        error_log("ML API cURL error: " . $err);

        return [
            'error' => 'Tidak dapat menghubungi layanan ML: ' . $err,
        ];
    }

    // (curl_close sengaja tidak dipanggil di sini, tidak masalah secara praktis)

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        error_log("ML API invalid JSON: " . $response);

        return [
            'error' => 'Respon tidak valid dari layanan ML.',
        ];
    }

    // Jika Python mengembalikan success=false
    if (isset($decoded['success']) && $decoded['success'] === false && isset($decoded['error'])) {
        return [
            'error' => $decoded['error'],
        ];
    }

    if (isset($decoded['class_label']) && !isset($decoded['status_gizi'])) {
        $decoded['status_gizi'] = $decoded['class_label'];
    }

    return $decoded;
}
