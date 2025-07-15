<?php
// lib/ProofUploader.php

function uploadComprovante(array $file): string {
    // Diretório de destino (NÃO apague comprovantes antigos de propósito)
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/comprovantes/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Validações
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Ocorreu um erro com o envio do arquivo.");
    }
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        throw new Exception("Arquivo muito grande (máximo 5MB).");
    }
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $fileMimeType = mime_content_type($file['tmp_name']);
    if (!in_array($fileMimeType, $allowedTypes)) {
        throw new Exception("Tipo de arquivo inválido. Apenas JPG, PNG e PDF são permitidos.");
    }

    // Processamento
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = 'compra_' . uniqid() . '.' . $fileExtension;
    $destination = $uploadDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Retorna o caminho da URL para salvar no banco
        return '/uploads/comprovantes/' . $newFileName;
    }

    throw new Exception("Falha ao salvar o arquivo enviado.");
}