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
                <label for="greaterAge">Members with age greater than</label>
                <input type="number" class="form-control" id="greaterAge" name="min-age" placeholder="Enter age" required>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

        <br>

        <form action="province.php" method="GET">
            <div class="form-group">
                <label for="province">Province</label>
                <input type="number" class="form-control" id="province" name="prov" placeholder="Enter province" required>
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