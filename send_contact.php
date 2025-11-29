<?php
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $subject = $_POST['subject'];
    $msg     = $_POST['message'];

    // Change this to your email
    $to = "chandrusri247@gmail.com";

    $body = "
      Name: $name\n
      Email: $email\n
      Subject: $subject\n
      Message:\n$msg
    ";

    mail($to, "New Contact Message", $body);

    echo "<script>alert('Message sent!'); window.location='contact.php';</script>";
}
?>
