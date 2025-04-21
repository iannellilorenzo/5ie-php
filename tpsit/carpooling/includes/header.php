includes\header.php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . " - RideTogether" : "RideTogether - Carpooling Made Simple"; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom styles -->
    <link href="<?php echo $rootPath; ?>assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #5e72e4;
            --secondary-color: #f7fafc;
            --accent-color: #11cdef;
        }
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), #825ee4);
            color: white;
            padding: 100px 0;
            position: relative;
        }
        .feature-card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            background-color: rgba(94, 114, 228, 0.1);
            color: var(--primary-color);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #4d60d6;
            border-color: #4d60d6;
        }
        .text-primary {
            color: var(--primary-color) !important;
        }
        .section-padding {
            padding: 80px 0;
        }
    </style>
    <?php if (isset($extraCSS)) echo $extraCSS; ?>
</head>
<body></body>