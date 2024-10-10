<?php
include('db.php'); // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $movie_title = $_POST['movie_title'];
    $user_name = $_POST['user_name'];
    $comment = $_POST['comment'];
    $rating = $_POST['rating']; // Get the star rating from the form

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO comments (movie_title, user_name, comment, rating, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssi", $movie_title, $user_name, $comment, $rating);

    // Execute the statement
    if ($stmt->execute()) {
        echo "Comment submitted successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Redirect back to the movie page or wherever appropriate
    header("Location: movie.php?title=" . urlencode($movie_title));
    exit();
}
?>
