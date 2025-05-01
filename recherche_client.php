<?php
require 'db.php';
header('Content-Type: application/json');

$recherche = $_POST['recherche'] ?? '';

if(strlen($recherche) < 2) {
    echo json_encode(['error' => 'Veuillez entrer au moins 2 caractères']);
    exit;
}

// Préparation de la requête avec protection contre les injections SQL
$query = "SELECT id_client, nom_client, prenom_client, telephone, email, date_naissance FROM client 
          WHERE nom_client LIKE ? OR prenom_client LIKE ? 
          LIMIT 10";
$stmt = $conn->prepare($query);
$term = "%".$recherche."%";
$stmt->bind_param("ss", $term, $term);
$stmt->execute();
$result = $stmt->get_result();

$clients = [];
while($row = $result->fetch_assoc()) {
    $clients[] = [
        'id' => $row['id_client'],
        'nom' => htmlspecialchars($row['nom_client']),
        'prenom' => htmlspecialchars($row['prenom_client']),
        'telephone' => htmlspecialchars($row['telephone']),
        'email' => htmlspecialchars($row['email']),
        'date_naissance' => htmlspecialchars($row['date_naissance'])
    ];
}

if(empty($clients)) {
    echo json_encode(['message' => 'Aucun client trouvé']);
} else {
    echo json_encode(['clients' => $clients]);
}

$stmt->close();
$conn->close();
?>