<?php
include('db.php');

if (isset($_GET['title'])) {
    $movieTitle = $_GET['title'];
    $omdbApiKey = '3a38a833'; 
    $tmdbApiKey = 'db530eb75fdd431140fb945e4903aeb4'; 
    $omdbUrl = "http://www.omdbapi.com/?t=" . urlencode($movieTitle) . "&apikey=" . $omdbApiKey;
    $omdbJson = file_get_contents($omdbUrl);
    $movieDetails = json_decode($omdbJson, true);
    if ($movieDetails['Response'] == 'True') {
        $title = $movieDetails['Title'];
        $imdbRating = $movieDetails['imdbRating'];
        $plot = $movieDetails['Plot'];
        $poster = $movieDetails['Poster']; 
        $genre = $movieDetails['Genre'];
        $rated = $movieDetails['Rated'];
        $runtime = $movieDetails['Runtime'];
        $releasedate = $movieDetails['Released'];
        $language = $movieDetails['Language'];
        $awards = $movieDetails['Awards'];
        $movietype = $movieDetails['Type'];
    } else {
        echo "Movie not found.";
        exit;
    }
    $tmdbSearchUrl = "https://api.themoviedb.org/3/search/movie?api_key=" . $tmdbApiKey . "&query=" . urlencode($movieTitle);
    $tmdbSearchJson = file_get_contents($tmdbSearchUrl);
    $tmdbSearchResult = json_decode($tmdbSearchJson, true);

    if (!empty($tmdbSearchResult['results'])) {
        $tmdbMovieId = $tmdbSearchResult['results'][0]['id'];

        $tmdbCastUrl = "https://api.themoviedb.org/3/movie/" . $tmdbMovieId . "/credits?api_key=" . $tmdbApiKey;
        $tmdbCastJson = file_get_contents($tmdbCastUrl);
        $tmdbCastResult = json_decode($tmdbCastJson, true);
    } else {
        echo "Movie cast not found in TMDb.";
        exit;
    }
} else {
    echo "No movie title provided.";
    exit;
}
$commentSql = "SELECT user_name, comment, created_at, rating FROM comments WHERE movie_title='$movieTitle' ORDER BY created_at DESC";
$commentsResult = $conn->query($commentSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Movie Details</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="landingpage">
    <div class="movieposter">
        <!-- Display Movie Poster -->
        <?php if (!empty($poster)) { ?>
            <img src="<?php echo $poster; ?>" alt="Movie Poster"><br>
        <?php } ?>
    </div>
    <div class="moviedetails">
        <h1><?php echo $title; ?></h1>
        <div class="title-info">
            <span class="certification"><?php echo $rated; ?></span> <!-- Certification -->
            <span class="movie-type"><?php echo ucfirst($movietype); ?></span> <!-- Movie Type -->
        </div>
        <p><strong>Released On:</strong> <?php echo $releasedate; ?></p>
        <div class="genre-buttons">
            <?php foreach (explode(", ", $genre) as $g) { ?>
                <button class="genre-button"><?php echo htmlspecialchars($g); ?></button>
            <?php } ?>
        </div>
        <p><strong>Language:</strong> <?php echo $language; ?></p>
        <p><strong>Awards:</strong> <?php echo $awards; ?></p>
        <p><strong>IMDb Rating:</strong> <?php echo $imdbRating; ?></p>
        
        <div class="plot-synopsis">
            <strong>Plot Synopsis:</strong>
            <p><?php echo $plot; ?></p>
        </div>
    </div>
</div>

<!-- Display Cast (up to 5 members) -->
<h2>Cast:</h2>
<div class="cast-container">
    <?php
    if (!empty($tmdbCastResult['cast'])) {
        $castCount = min(10, count($tmdbCastResult['cast']));
        for ($i = 0; $i < $castCount; $i++) {
            $actor = $tmdbCastResult['cast'][$i];
            echo "<div class='cast-member'>";
            echo "<img src='https://image.tmdb.org/t/p/w200" . $actor['profile_path'] . "' alt='" . htmlspecialchars($actor['name']) . "'>";
            echo "<p>" . htmlspecialchars($actor['name']) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>No cast information available.</p>";
    }
    ?>
</div>

<!-- Display Crew (Director, Producer, Music Director) -->
<h2>Crew:</h2>
<div class="crew-container">
    <?php
    if (!empty($tmdbCastResult['crew'])) {
        $crewMembers = [];
        foreach ($tmdbCastResult['crew'] as $member) {
            if ($member['job'] === 'Director' || $member['job'] === 'Producer' || $member['job'] === 'Music' || $member['job'] === 'Music Supervisor' || $member['job'] === 'Original Music Composer') {
                $crewMembers[] = [
                    'name' => $member['name'],
                    'job' => $member['job'],
                    'profile_path' => $member['profile_path'] ?? null
            }
        }

        // Display crew members
        foreach ($crewMembers as $crew) {
            echo "<div class='crew-member'>";
            if (!empty($crew['profile_path'])) {
                echo "<img src='https://image.tmdb.org/t/p/w200" . $crew['profile_path'] . "' alt='" . htmlspecialchars($crew['name']) . "'>";
            }
            echo "<p>" . htmlspecialchars($crew['name']) . "<br><small>" . htmlspecialchars($crew['job']) . "</small></p>";
            echo "</div>";
        }
    } else {
        echo "<p>No crew information available.</p>";
    }
    ?>
</div>


<h2>Comments:</h2>
<?php
if ($commentsResult->num_rows > 0) {
    while ($row = $commentsResult->fetch_assoc()) {
        $rating = str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']);
        echo "<div class='comment'>";
        echo "<p><strong>" . htmlspecialchars($row['user_name']) . "</strong>: " . htmlspecialchars($row['comment']) . " <i>on " . $row['created_at'] . "</i></p>";
        echo "<div class='rating'>" . $rating . "</div>"; 
        echo "</div>";
    }
} else {
    echo "<p>No comments yet. Be the first to comment!</p>";
}
?>

<!-- Comment Form -->
<h3>Leave a Comment:</h3>
<form action="submit_comment.php" method="POST">
    <input type="hidden" name="movie_title" value="<?php echo htmlspecialchars($title); ?>">
    <input type="text" name="user_name" placeholder="Your Name" required><br>
    <textarea name="comment" placeholder="Your Comment" required></textarea><br>
    <label for="rating">Rate this movie:</label>
    <select name="rating" id="rating" required>
        <option value="5">5 Stars</option>
        <option value="4">4 Stars</option>
        <option value="3">3 Stars</option>
        <option value="2">2 Stars</option>
        <option value="1">1 Star</option>
    </select><br>
    <input type="submit" value="Submit Comment">
</form>


</body>
</html>
