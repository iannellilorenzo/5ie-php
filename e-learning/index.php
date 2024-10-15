<?php
    var_dump ($_GET);
    echo "<br>";
    $richiesta= $_SERVER["REQUEST_URI"];
    var_dump($richiesta);
    echo "<br>";
    $param=explode("/",$richiesta);
    if (count($param)>2)
        for($i=3;$i<count($param);$i++)
            echo ($i-2)." => ". $param[$i]."<br>";
?>