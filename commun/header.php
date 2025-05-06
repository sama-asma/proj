<head>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .search-bar {
            display: flex;
            align-items: center;
            background-color: #f5f5f5;
            border-radius: 20px;
            padding: 5px 15px;
            margin: 0 20px;
            max-width: 600px;
            width: 100%;
        }
        
        .search-bar i {
            color: #555;
            margin-right: 10px;
        }
        
        .search-bar input {
            border: none;
            background: transparent;
            padding: 8px 5px;
            flex-grow: 1;
            outline: none;
            font-size: 14px;
        }
        
        .search-bar select {
            border: none;
            background: #e0e0e0;
            padding: 8px 10px;
            border-radius: 15px;
            margin-right: 10px;
            outline: none;
            font-size: 14px;
            cursor: pointer;
        }
        
        .search-bar button {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 15px;
            padding: 8px 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .search-bar button:hover {
            background-color: #45a049;
        }
        
        @media (max-width: 768px) {
            .search-bar {
                flex-direction: column;
                align-items: stretch;
                padding: 10px;
            }
            
            .search-bar select,
            .search-bar input,
            .search-bar button {
                margin: 5px 0;
                width: 100%;
            }
        }
    </style>
</head>
<div class="header">
    <div class="topbar">
        <div class="toggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
    <form class="search-bar" method="GET" action="contrats.php">
        <select name="filtre" id="filtre">
            <option value="tous" <?php echo (isset($_GET['filtre']) && $_GET['filtre'] == 'tous') ? 'selected' : ''; ?>>Tous les champs</option>
            <option value="numero" <?php echo (isset($_GET['filtre']) && $_GET['filtre'] == 'numero') ? 'selected' : ''; ?>>Numéro de contrat</option>
            <option value="client" <?php echo (isset($_GET['filtre']) && $_GET['filtre'] == 'client') ? 'selected' : ''; ?>>Nom du client</option>
            <option value="type" <?php echo (isset($_GET['filtre']) && $_GET['filtre'] == 'type') ? 'selected' : ''; ?>>Type d'assurance</option>
        </select>
        <i class="fas fa-search"></i>
        <input type="text" name="recherche" placeholder="Rechercher..." value="<?php echo isset($_GET['recherche']) ? htmlspecialchars($_GET['recherche']) : ''; ?>">
        <!-- Conserver les paramètres de tri et pagination -->
        <?php if (isset($_GET['ordre'])): ?>
            <input type="hidden" name="ordre" value="<?php echo htmlspecialchars($_GET['ordre']); ?>">
        <?php endif; ?>
        <button type="submit">Rechercher</button>
    </form>
    <div class="user-info">
        <span class="username"><?= $_SESSION['username'] ?></span>
    </div>
</div>