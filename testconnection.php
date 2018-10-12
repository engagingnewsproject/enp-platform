<?php
$link = mysqli_connect('localhost', 'root', '');
if (!$link) {
die('Could not connect: ' . mysqli_error());
}
echo 'Connected successfully';
mysqli_close($link);
?>