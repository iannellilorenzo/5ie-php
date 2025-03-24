<?php
// Avvia la sessione
session_start();

// Connessione al database
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=azienda', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configurazione paginazione
    $itemsPerPage = isset($_GET['perpage']) ? (int)$_GET['perpage'] : 10;
    $itemsPerPage = min(max($itemsPerPage, 5), 100); // Limite tra 5 e 100
    
    // Query per contare il totale dei record
    $countStmt = $pdo->query("SELECT COUNT(*) AS total FROM impiegati");
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $itemsPerPage);
    
    // Gestione della navigazione
    if (isset($_GET['page'])) {
        $_SESSION['current_page'] = (int)$_GET['page'];
        $_SESSION['items_per_page'] = $itemsPerPage;
    } elseif (!isset($_SESSION['current_page'])) {
        $_SESSION['current_page'] = 1;
        $_SESSION['items_per_page'] = $itemsPerPage;
    } else {
        $itemsPerPage = isset($_SESSION['items_per_page']) ? $_SESSION['items_per_page'] : $itemsPerPage;
    }
    
    // Assicurarsi che la pagina sia valida
    $currentPage = max(1, min($_SESSION['current_page'], $totalPages));
    $_SESSION['current_page'] = $currentPage;
    
    // Calcola l'offset per la query SQL
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    // Query per ottenere i record della pagina corrente
    $stmt = $pdo->prepare("SELECT * FROM impiegati LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Impiegati | Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation Library -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --secondary-color: #4cc9f0;
            --dark-color: #2b2d42;
            --light-color: #f8f9fa;
            --success-color: #06d6a0;
            --warning-color: #ffd166;
            --danger-color: #ef476f;
            --border-radius: 10px;
            --card-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7ff;
            color: var(--dark-color);
            transition: var(--transition);
        }
        
        .dark-mode {
            --primary-color: #4cc9f0;
            --primary-hover: #73d5f3;
            --secondary-color: #4361ee;
            --dark-color: #f8f9fa;
            --light-color: #2b2d42;
            background-color: #121212;
            color: #f8f9fa;
        }
        
        .navbar {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            z-index: 1000;
        }
        
        .dark-mode .navbar {
            background-color: rgba(25, 25, 25, 0.95);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }
        
        .page-link {
            border-radius: 6px;
            margin: 0 2px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0.75rem;
            transition: var(--transition);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
            color: white;
            font-weight: 600;
        }
        
        .pagination .page-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: var(--transition);
        }
        
        .card-header {
            border-bottom: none;
            padding: 1.25rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-top: none;
            background-color: rgba(0, 0, 0, 0.04);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
        }
        
        .dark-mode .table thead th {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .table-hover tbody tr {
            transition: var(--transition);
            cursor: pointer;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.08) !important;
            transform: translateY(-2px);
        }
        
        .dark-mode .table-hover tbody tr:hover {
            background-color: rgba(76, 201, 240, 0.1) !important;
        }
        
        .employee-id {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .employee-name {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .dark-mode .employee-name {
            color: var(--light-color);
        }
        
        .employee-salary {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            color: var(--success-color);
        }
        
        .badge-counter {
            font-weight: 600;
            padding: 0.4rem 0.8rem;
            border-radius: 30px;
            box-shadow: 0 3px 8px rgba(67, 97, 238, 0.15);
        }
        
        .form-select {
            border-radius: var(--border-radius);
            padding: 0.5rem 2rem 0.5rem 1rem;
            border: 1px solid rgba(0,0,0,0.1);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .form-select:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.25);
            border-color: var(--primary-color);
        }
        
        .table-row-animate {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .theme-switch {
            position: relative;
            width: 60px;
            height: 30px;
            margin-left: 15px;
        }
        
        .theme-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 30px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--primary-color);
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px var(--primary-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(30px);
        }
        
        .slider:after {
            content: '☀️';
            position: absolute;
            left: 8px;
            top: 5px;
            font-size: 12px;
        }
        
        input:checked + .slider:after {
            content: '🌙';
            position: absolute;
            left: 34px;
            top: 5px;
            font-size: 12px;
        }
        
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: #ddd;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }
        
        .dark-mode .loading-overlay {
            background-color: rgba(18, 18, 18, 0.8);
        }
        
        .pulse {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            animation: pulse 1.5s infinite ease-in-out;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(0.8);
                opacity: 1;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.6;
            }
            100% {
                transform: scale(0.8);
                opacity: 1;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .d-flex.justify-content-between {
                flex-direction: column;
            }
            
            .d-flex.justify-content-between > * {
                margin-bottom: 1rem;
            }
            
            .d-flex.align-items-center {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="pulse"></div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="#">
                <i class="fas fa-building me-2"></i>AziendaManager
            </a>
            
            <div class="d-flex align-items-center">
                <div class="d-flex align-items-center">
                    <span class="me-2">Tema</span>
                    <label class="theme-switch">
                        <input type="checkbox" id="themeToggle">
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mb-5" data-aos="fade-up" data-aos-delay="100">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold text-primary mb-0">
                    <i class="fas fa-user-tie me-2"></i>Elenco Impiegati
                </h1>
                <p class="text-muted">Gestione e visualizzazione del personale</p>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="input-group me-3">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-list"></i>
                    </span>
                    <select class="form-select border-start-0" id="itemsPerPage" onchange="changeItemsPerPage(this.value)">
                        <option value="5" <?= $itemsPerPage == 5 ? 'selected' : '' ?>>5 elementi</option>
                        <option value="10" <?= $itemsPerPage == 10 ? 'selected' : '' ?>>10 elementi</option>
                        <option value="25" <?= $itemsPerPage == 25 ? 'selected' : '' ?>>25 elementi</option>
                        <option value="50" <?= $itemsPerPage == 50 ? 'selected' : '' ?>>50 elementi</option>
                    </select>
                </div>
                
                <span class="badge bg-primary badge-counter">
                    <i class="fas fa-users me-1"></i> Totale: <?= number_format($totalRecords, 0, ',', '.') ?>
                </span>
            </div>
        </div>
        
        <div class="card mb-4" data-aos="zoom-in-up" data-aos-delay="200">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="fas fa-table me-2"></i> Pagina <?= $currentPage ?> di <?= $totalPages ?>
                </h5>
                <span class="badge bg-light text-dark fs-6 shadow-sm">
                    Risultati <?= number_format($offset+1, 0, ',', '.') ?>-<?= number_format(min($offset+$itemsPerPage, $totalRecords), 0, ',', '.') ?> di <?= number_format($totalRecords, 0, ',', '.') ?>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>MATRICOLA</th>
                                <th>NOMINATIVO</th>
                                <th class="text-end">STIPENDIO</th>
                                <th class="text-center">AZIONI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rowNum = $offset + 1; ?>
                            <?php foreach($results as $index => $row): ?>
                            <tr class="table-row-animate" style="animation-delay: <?= $index * 0.05 ?>s">
                                <td class="text-center text-muted"><?= $rowNum++ ?></td>
                                <td class="employee-id"><?= htmlspecialchars($row['Matricola']) ?></td>
                                <td class="employee-name"><?= htmlspecialchars($row['Nominativo']) ?></td>
                                <td class="text-end employee-salary">€ <?= number_format((float)$row['Stipendio'], 2, ',', '.') ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary rounded-circle" title="Visualizza dettagli">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success rounded-circle" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($results)): ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <h4>Nessun risultato trovato</h4>
                                    <p class="text-muted">Non ci sono impiegati da visualizzare in questa pagina.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Paginazione Avanzata -->
        <nav aria-label="Navigazione pagine" data-aos="fade-up" data-aos-delay="300">
            <ul class="pagination pagination-md justify-content-center">
                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=1&perpage=<?= $itemsPerPage ?>" aria-label="Prima" title="Prima pagina">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                </li>
                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= ($currentPage - 1) ?>&perpage=<?= $itemsPerPage ?>" aria-label="Precedente" title="Pagina precedente">
                        <i class="fas fa-angle-left me-1"></i> Precedente
                    </a>
                </li>
                
                <?php
                // Determina il range di pagine da mostrare
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                // Mostra eventualmente la prima pagina e i puntini di sospensione
                if ($startPage > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1&perpage=' . $itemsPerPage . '">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                // Mostra le pagine nel range
                for ($i = $startPage; $i <= $endPage; $i++) {
                    echo '<li class="page-item ' . ($i == $currentPage ? 'active' : '') . '">
                            <a class="page-link" href="?page=' . $i . '&perpage=' . $itemsPerPage . '">' . $i . '</a>
                          </li>';
                }
                
                // Mostra eventualmente i puntini di sospensione e l'ultima pagina
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&perpage=' . $itemsPerPage . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= ($currentPage + 1) ?>&perpage=<?= $itemsPerPage ?>" aria-label="Successivo" title="Pagina successiva">
                        Successivo <i class="fas fa-angle-right ms-1"></i>
                    </a>
                </li>
                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $totalPages ?>&perpage=<?= $itemsPerPage ?>" aria-label="Ultima" title="Ultima pagina">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <footer class="bg-white py-4 mt-auto border-top">
        <div class="container text-center">
            <p class="text-muted mb-0">© <?= date('Y') ?> AziendaManager - Gestione Risorse Umane</p>
        </div>
    </footer>

    <!-- Script per gestire il cambio del numero di elementi per pagina -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        // Inizializzazione AOS (Animate On Scroll)
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
            
            // Nascondi loading overlay dopo il caricamento
            setTimeout(function() {
                const overlay = document.getElementById('loadingOverlay');
                overlay.style.opacity = '0';
                setTimeout(function() {
                    overlay.style.display = 'none';
                }, 500);
            }, 800);
            
            // Tema chiaro/scuro
            const themeToggle = document.getElementById('themeToggle');
            
            // Controlla se è stato salvato un tema nelle preferenze
            if (localStorage.getItem('darkMode') === 'true') {
                document.body.classList.add('dark-mode');
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                themeToggle.checked = true;
            }
            
            themeToggle.addEventListener('change', function() {
                if (this.checked) {
                    document.body.classList.add('dark-mode');
                    document.documentElement.setAttribute('data-bs-theme', 'dark');
                    localStorage.setItem('darkMode', 'true');
                } else {
                    document.body.classList.remove('dark-mode');
                    document.documentElement.setAttribute('data-bs-theme', 'light');
                    localStorage.setItem('darkMode', 'false');
                }
            });
        });
        
        function changeItemsPerPage(value) {
            // Mostra overlay di caricamento
            const overlay = document.getElementById('loadingOverlay');
            overlay.style.display = 'flex';
            overlay.style.opacity = '1';
            
            // Timeout per dare l'impressione di caricamento
            setTimeout(function() {
                window.location.href = "?page=1&perpage=" + value;
            }, 500);
        }
    </script>
</body>
</html>
<?php
} catch(PDOException $e) {
    echo '<div class="container mt-4"><div class="alert alert-danger shadow"><i class="fas fa-exclamation-triangle me-2"></i> Errore: ' . $e->getMessage() . '</div></div>';
}
?>