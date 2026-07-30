<?php
namespace Services;

use Config\CloudinaryConfig;

class CloudinaryUploader {
    
    /**
     * Uploads a file to Cloudinary using cURL.
     * 
     * @param string $filePath The local path of the file to upload (e.g. $_FILES['file']['tmp_name'])
     * @param string $resourceType 'image', 'raw', 'video', or 'auto'
     * @return string The secure_url of the uploaded file
     * @throws \Exception If the upload fails or configuration is missing
     */
    public static function upload($filePath, $resourceType = 'auto') {
        $config = CloudinaryConfig::getConfig();
        $cloudName = $config['cloud_name'] ?? '';
        $uploadPreset = $config['upload_preset'] ?? '';

        if (empty($cloudName) || empty($uploadPreset) || $cloudName === 'YOUR_CLOUD_NAME') {
            throw new \Exception("Cloudinary configuration is missing or invalid. Please set your credentials in CloudinaryConfig.php.");
        }

        if (!file_exists($filePath)) {
            throw new \Exception("File to upload does not exist at path: " . $filePath);
        }

        $url = "https://api.cloudinary.com/v1_1/{$cloudName}/{$resourceType}/upload";

        // Determine MIME type
        $mime = mime_content_type($filePath);
        if (!$mime) $mime = 'application/octet-stream';
        
        // Prepare cURL file object
        if (class_exists('CURLFile')) {
            $cfile = new \CURLFile($filePath, $mime, basename($filePath));
        } else {
            // Fallback for very old PHP versions
            $cfile = '@' . realpath($filePath);
        }

        $postData = [
            'file' => $cfile,
            'upload_preset' => $uploadPreset
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Optional: Ignore SSL verification if running on local XAMPP without proper certs
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds timeout

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL Error during Cloudinary upload: " . $error);
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 400 || isset($responseData['error'])) {
            $errorMsg = $responseData['error']['message'] ?? 'Unknown Cloudinary API error';
            throw new \Exception("Cloudinary API Error (HTTP $httpCode): " . $errorMsg);
        }

        if (empty($responseData['secure_url'])) {
            throw new \Exception("Upload succeeded but secure_url was not returned by Cloudinary.");
        }

        return $responseData['secure_url'];
    }
}
