<?php
// lib/ImageUploader.php

function uploadBanner(array $file, string $currentBanner = null): ?string {
    // Diretório de destino final (acessível publicamente)
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/banners/';
    
    // Verifique e crie o diretório se ele não existir
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // --- Validações de Segurança ---

    // 1. Verificar erros de upload do PHP
    if ($file['error'] !== UPLOAD_ERR_OK) {
        // Não é um erro fatal, apenas ignora se não houver arquivo novo
        return null; 
    }

    // 2. Verificar tamanho do arquivo (ex: 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("Erro: O arquivo é muito grande (máximo 5MB).");
    }

    // 3. Verificar o tipo de arquivo (MIME type)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileMimeType = mime_content_type($file['tmp_name']);
    if (!in_array($fileMimeType, $allowedTypes)) {
        throw new Exception("Erro: Tipo de arquivo inválido. Apenas JPG, PNG, GIF e WebP são permitidos.");
    }

    // --- Processamento do Arquivo ---

    // 4. Gerar um nome de arquivo único e seguro
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = uniqid('banner_', true) . '.' . $fileExtension;
    $destination = $uploadDir . $newFileName;

    // 5. Mover o arquivo para o destino final
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Se o upload for bem-sucedido, apague o banner antigo (se existir)
        if ($currentBanner) {
            // Extrai o caminho do sistema a partir do caminho da URL
            $oldFilePath = $_SERVER['DOCUMENT_ROOT'] . parse_url($currentBanner, PHP_URL_PATH);
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        
        // Retorna o caminho da URL para ser salvo no banco de dados
        return 'public/uploads/banners/' . $newFileName;
    }

    throw new Exception("Erro: Falha ao mover o arquivo enviado.");
}