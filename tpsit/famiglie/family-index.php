<?php
    $families = file_get_contents("Famiglie.json");
    $db = json_decode($families);
    //var_dump($db);
    $index = $_GET["index"];

    if ($index < 0 || $index >= count($db)) {
        echo "Index must be between 0 and " . (count($db) - 1);
        exit();
    }

    $person = $db[$index];

    // Array to display the info better in the modal
    $fields = [];
    $fields["id_famiglia"] = "Family Code";
    $fields["id_compo"] = "Component Number";
    $fields["tipo"] = "Role in the Family";
    $fields["cognome"] = "Last Name";
    $fields["nome"] = "First Name";
    $fields["sesso"] = "Gender";
    $fields["nas_luogo"] = "Place of Birth";
    $fields["nas_regione"] = "Region of Birth";
    $fields["nas_prov"] = "Province of Birth";
    $fields["nas_cap"] = "Postal Code of Birthplace";
    $fields["nas_belf"] = "Municipality Code";
    $fields["nas_pre"] = "Phone Area Code";
    $fields["data_nascita"] = "Date of Birth";
    $fields["cod_fis"] = "Tax Code";
    $fields["res_luogo"] = "Place of Residence";
    $fields["res_regione"] = "Region of Residence";
    $fields["res_prov"] = "Province of Residence";
    $fields["res_cap"] = "Postal Code of Residence";
    $fields["indirizzo"] = "Address";
    $fields["telefono"] = "Phone Number";
    $fields["email"] = "E-mail";
    $fields["pwd_email"] = "Password";
    $fields["tit_studio"] = "Education Level";
    $fields["professione"] = "Profession";
    $fields["sta_civ"] = "Marital Status";
    $fields["targa"] = "Car License Plate";
    $fields["part_iva"] = "VAT Number";
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Families Management</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="jumbotron text-center">
            <h1 class="display-4">Family Member Details</h1>
            <p class="lead">Detailed information about the selected family member</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card mb-4">
                    <?php
                        $maleOrFemale = $person->sesso == 'M' ? 'img/male.jpg' : 'img/female.jpg';
                    ?>
                    <img src="<?php echo $maleOrFemale; ?>" class="card-img-top" alt="Card Image">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $person->cognome . " " . $person->nome; ?></h5>
                        <p class="card-text">Role: <?php echo $person->tipo; ?></p>
                        <p class="card-text">Date of Birth: <?php echo $person->data_nascita; ?></p>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#infoModal">Tell me more</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="infoModalLabel<?php echo $person->id; ?>" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="infoModalLabel<?php echo $person->id; ?>">Family Member Information</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php
                    foreach ($fields as $field => $label) {
                        echo "<p><strong>{$label}:</strong> {$person->$field}</p>";
                    }
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>