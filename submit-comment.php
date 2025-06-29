<?php
include("includes/database.inc.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($con)) {
    $article_id = $_POST['article_id'];
    $user_name = $_POST['user_name'];
    $email = $_POST['email'];
    $comment = $_POST['comment'];

    $stmt = $con->prepare("INSERT INTO comments (article_id, user_name, email, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $article_id, $user_name, $email, $comment);

    if ($stmt->execute()) {
        header("Location: news.php?id=$article_id#comments");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
