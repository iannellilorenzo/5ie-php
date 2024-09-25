<!doctype html>
<html lang="en">

<head>
    <title>Gestionale Famiglie</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>

<?php
    $provinces = [
        "AG" => "Agrigento",
        "AL" => "Alessandria",
        "AN" => "Ancona",
        "AO" => "Aosta",
        "AR" => "Arezzo",
        "AP" => "Ascoli Piceno",
        "AT" => "Asti",
        "AV" => "Avellino",
        "BA" => "Bari",
        "BT" => "Barletta-Andria-Trani",
        "BL" => "Belluno",
        "BN" => "Benevento",
        "BG" => "Bergamo",
        "BI" => "Biella",
        "BO" => "Bologna",
        "BZ" => "Bolzano",
        "BS" => "Brescia",
        "BR" => "Brindisi",
        "CA" => "Cagliari",
        "CL" => "Caltanissetta",
        "CB" => "Campobasso",
        "CI" => "Carbonia-Iglesias",
        "CE" => "Caserta",
        "CT" => "Catania",
        "CZ" => "Catanzaro",
        "CH" => "Chieti",
        "CO" => "Como",
        "CS" => "Cosenza",
        "CR" => "Cremona",
        "KR" => "Crotone",
        "CN" => "Cuneo",
        "EN" => "Enna",
        "FM" => "Fermo",
        "FE" => "Ferrara",
        "FI" => "Florence",
        "FG" => "Foggia",
        "FC" => "Forlì-Cesena",
        "FR" => "Frosinone",
        "GE" => "Genoa",
        "GO" => "Gorizia",
        "GR" => "Grosseto",
        "IM" => "Imperia",
        "IS" => "Isernia",
        "SP" => "La Spezia",
        "AQ" => "L'Aquila",
        "LT" => "Latina",
        "LE" => "Lecce",
        "LC" => "Lecco",
        "LI" => "Livorno",
        "LO" => "Lodi",
        "LU" => "Lucca",
        "MC" => "Macerata",
        "MN" => "Mantua",
        "MS" => "Massa and Carrara",
        "MT" => "Matera",
        "VS" => "Medio Campidano",
        "ME" => "Messina",
        "MI" => "Milan",
        "MO" => "Modena",
        "MB" => "Monza and Brianza",
        "NA" => "Naples",
        "NO" => "Novara",
        "NU" => "Nuoro",
        "OR" => "Oristano",
        "PD" => "Padua",
        "PA" => "Palermo",
        "PR" => "Parma",
        "PV" => "Pavia",
        "PG" => "Perugia",
        "PU" => "Pesaro and Urbino",
        "PE" => "Pescara",
        "PC" => "Piacenza",
        "PI" => "Pisa",
        "PT" => "Pistoia",
        "PN" => "Pordenone",
        "PZ" => "Potenza",
        "PO" => "Prato",
        "RG" => "Ragusa",
        "RA" => "Ravenna",
        "RC" => "Reggio Calabria",
        "RE" => "Reggio Emilia",
        "RI" => "Rieti",
        "RN" => "Rimini",
        "RM" => "Rome",
        "RO" => "Rovigo",
        "SA" => "Salerno",
        "SS" => "Sassari",
        "SV" => "Savona",
        "SI" => "Siena",
        "SR" => "Syracuse",
        "SO" => "Sondrio",
        "TA" => "Taranto",
        "TE" => "Teramo",
        "TR" => "Terni",
        "TO" => "Turin",
        "TP" => "Trapani",
        "TN" => "Trento",
        "TV" => "Treviso",
        "TS" => "Trieste",
        "UD" => "Udine",
        "VA" => "Varese",
        "VE" => "Venice",
        "VB" => "Verbano-Cusio-Ossola",
        "VC" => "Vercelli",
        "VR" => "Verona",
        "VV" => "Vibo Valentia",
        "VI" => "Vicenza",
        "VT" => "Viterbo",
    ];
?>

<body>
    <div class="container mt-5">
        <h1 class="alert alert-info">Gestionale Famiglie</h1>
        <form action="family-index.php" method="GET">
            <div class="form-group">
                <label for="familyIndex">Family Index</label>
                <input type="number" class="form-control" id="familyIndex" name="index" placeholder="Enter family index" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

        <br>

        <form action="tax-id-code.php" method="GET">
            <div class="form-group">
                <label for="taxIdCode">Tax ID Code</label>
                <input type="text" class="form-control" id="taxIdCode" name="tic" placeholder="Enter tax id code" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

        <br>

        <form action="family-id.php" method="GET">
            <div class="form-group">
                <label for="familyId">Family ID</label>
                <input type="number" class="form-control" id="familyId" name="fam-id" placeholder="Enter family id" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

        <br>

        <form action="greater-age.php" method="GET">
            <div class="form-group">
                <label for="greater-age">Members with age greater than</label>
                <input type="number" class="form-control" id="greaterAge" name="min-age" placeholder="Enter age" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

        <br>

        <form action="province.php" method="GET">
            <div class="form-group">
                <label for="province">Province</label>
                <select class="form-control" id="province" name="prov" required>
                    <?php
                        foreach ($provinces as $code => $name) {
                            echo "<option value='{$code}'>{$code} - {$name}</option>";
                        }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>

</html>