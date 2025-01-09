<!DOCTYPE html>
<html>
<head>
    <title>Form Example</title>
    <script>
        function setFormAction() {
            var form = document.getElementById('myForm');
            var option1 = document.getElementById('rc4').checked;
            if (option1) {
                form.action = 'rc4.php';
            } else {
                form.action = 'rc5.php';
            }
        }
    </script>
</head>
<body>
    <form id="myForm" method="post" onsubmit="setFormAction()">
        <label for="text">Insert below your text to hash: </label>
        <input type="text" id="data" name="data"><br>

        <label for="key">Insert below your key used for hashing: </label>
        <input type="text" id="key" name="key"><br>

        <hr>

        <label for="rc4">RC4</label>
        <input type="radio" id="rc4" name="option" value="1"><br>

        <label for="rc5">RC5</label>
        <input type="radio" id="rc5" name="option" value="2"><br><br>

        <input type="submit" value="Encrypt info">
    </form>
</body>
</html>