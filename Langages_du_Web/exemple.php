<?php
    // Importation de la config Omeka S
    require 'config.php';

    // Gestion des messages
    session_start();
    $success = [];
    $errors = [];

    $filename = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $form_type = $_POST['form_type'] ?? '';

        // Chargement du texte
        if ($form_type === 'text') {
            if (isset($_POST['content'])) {
                $content = $_POST['content'];
                $filename = null;
            } else {
                $errors[] = "Erreur lors du chargement du texte.";
            }

        // Chargement du fichier
        } elseif ($form_type === 'file') {
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $filename = $_FILES['file']['name'];
                $file_path = $_FILES['file']['tmp_name'];
                $content = file_get_contents($file_path);
            } else {
                $errors[] = "Erreur lors du chargement du fichier ou aucun fichier reçu.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            header("Location: index.php");
            exit;
        }
    }

    // On vérifie si variable $content n'est pas vide
    if (empty($content)) {
        $_SESSION['errors'] = ["Erreur : Le fichier importé est vide."];
        header("Location: index.php");
        exit;
    }

    // Nettoyage du texte
    $content_clean = trim(preg_replace('/\s+/', ' ', strip_tags($content)));

    // Résumé du texte
    $summary = substr($content_clean, 0, 20) . "...";
    
    // Construction du payload JSON-LD pour Omeka S
    $itemData = [
        "o:resource_template" => ["o:id" => 9],
        "o:resource_class"   => ["o:id" => 113],

        // Titre
        "dcterms:title" => [
            [
                "type" => "literal",
                "property_id" => 1,          // ID de la propriété Title
                "value" => $filename ? $filename : "Texte généré"
            ]
        ],

        // Texte
        "o:resource_template_property" => [
            [
                "property_id" => 195,        // ID de la propriété 'texte'
                "type" => "literal",
                "value" => $content_clean
            ]
        ]
    ];

    // Encodage JSON-LD
    $jsonData = json_encode($itemData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // URL API Omeka S
    $url = "$omeka_api_url/items?key_identity=$omeka_api_key_identity&key_credential=$omeka_api_key_credential";

    // Envoi vers Omeka S via CURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/ld+json",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Vérification du résultat et redirection
    if ($httpcode >= 200 && $httpcode < 300) {
        $_SESSION['success'] = "Item créé avec succès dans Omeka S.";
        header("Location: result.php");
        exit;
    } else {
        $_SESSION['errors'] = ["Erreur API Omeka S ($httpcode) : $response"];
        header("Location: index.php");
        exit;
    }
?>