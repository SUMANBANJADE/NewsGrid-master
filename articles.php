<?php
  // Fetching all the Navbar Data
  require('./includes/nav.inc.php');
  // Include the recommender helper
  require('./includes/recommender.inc.php');
?>

<!-- Article List Container -->
<section class="py-1 category-list">
  <div class="container">
    <h2 class="headings">Articles</h2>
    <div class="card-container">
      <?php
        // Pagination setup
        $limit = 6;
        $page  = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Determine category filter
        $category_id = isset($_GET['id']) && $_GET['id'] !== '' ? (int)$_GET['id'] : null;

        // Build main article query
        if ($category_id) {
          $articleQuery = "
            SELECT c.category_name, c.category_color, a.*
            FROM article a
            JOIN category c ON a.category_id = c.category_id
            WHERE a.article_active = 1
              AND c.category_id = {$category_id}
            ORDER BY a.article_title
            LIMIT {$offset}, {$limit}"
          ;
        } else {
          $articleQuery = "
            SELECT c.category_name, c.category_color, a.*
            FROM article a
            JOIN category c ON a.category_id = c.category_id
            WHERE a.article_active = 1
            ORDER BY a.article_title
            LIMIT {$offset}, {$limit}"
          ;
        }

        $result = mysqli_query($con, $articleQuery);
        $rowCount = mysqli_num_rows($result);

        if ($rowCount > 0) {
          // Collect user bookmarks for exclusion
          $userBookmarks = [];
          if (isset($_SESSION['USER_ID'])) {
            $bmRes = mysqli_query(
              $con,
              "SELECT article_id FROM bookmark WHERE user_id = {$_SESSION['USER_ID']}"
            );
            while ($bmRow = mysqli_fetch_assoc($bmRes)) {
              $userBookmarks[] = (int)$bmRow['article_id'];
            }
          }

          while ($data = mysqli_fetch_assoc($result)) {
            extract($data);
            // Format title & description
            $shortTitle = substr($article_title, 0, 55) . ' . . . . .';
            $shortDesc  = substr($article_description, 0, 150) . ' . . . . .';

            // "New" flag
            $daysOld = (time() - strtotime($article_date)) / (60*60*24);
            $new     = $daysOld < 2;

            // Bookmark flag
            $bookmarked = false;
            if (isset($_SESSION['USER_ID'])) {
              $bq = mysqli_query(
                $con,
                "SELECT 1 FROM bookmark WHERE user_id = {$_SESSION['USER_ID']} AND article_id = {$article_id}"
              );
              $bookmarked = mysqli_num_rows($bq) > 0;
            }

            createArticleCard(
              htmlspecialchars($shortTitle),
              $article_image,
              htmlspecialchars($shortDesc),
              htmlspecialchars($category_name),
              $category_id,
              $article_id,
              $category_color,
              $new,
              $article_trend,
              $bookmarked
            );
          }

          // After listing, show recommendations
          if (isset($_SESSION['USER_ID'])) {
            $recommendations = getRecommendations(
              $con,
              $_SESSION['USER_ID'],
              array_merge($userBookmarks, [$article_id]),
              5
            );

            if (count($recommendations) > 0) {
              echo '</div>'; // close card-container
              echo '<h2 class="headings">Recommended For You</h2>';
              echo '<div class="card-container">';
              foreach ($recommendations as $rid) {
                $rq = mysqli_query(
                  $con,
                  "SELECT a.article_title, a.article_image, au.author_name, a.article_date
                   FROM article a
                   JOIN author au ON a.author_id = au.author_id
                   WHERE a.article_id = {$rid}"
                );
                if ($row = mysqli_fetch_assoc($rq)) {
                  $t   = substr(htmlspecialchars($row['article_title']), 0, 55) . ' . . . . .';
                  $dt  = date("d M Y", strtotime($row['article_date']));
                  createAsideCard(
                    $row['article_image'],
                    $rid,
                    $t,
                    htmlspecialchars($row['author_name']),
                    $dt
                  );
                }
              }
              echo '</div>'; // close recommendations container
            }
          }

        } else {
          createNoArticlesCard();
        }
      ?>
    </div>

    <!-- Pagination -->
    <?php
      // Count total articles for pagination
      if ($category_id) {
        $countQ = "SELECT COUNT(*) AS cnt FROM article WHERE category_id = {$category_id} AND article_active = 1";
      } else {
        $countQ = "SELECT COUNT(*) AS cnt FROM article WHERE article_active = 1";
      }
      
      $cr = mysqli_fetch_assoc(mysqli_query($con, $countQ));
      $totalArticles = (int)$cr['cnt'];
      $totalPages    = (int)ceil($totalArticles / $limit);
      if ($totalPages > 1) {
        echo '<div class="text-center py-2"><div class="pagination">';
        $baseParams = $category_id ? "?id={$category_id}&" : "?";
        if ($page > 1) {
          echo "<a href=\"articles.php{$baseParams}page=" . ($page-1) . "&\">&laquo;</a>";
        }
        for ($i = 1; $i <= $totalPages; $i++) {
          $cls = $i === $page ? 'page-active' : '';
          echo "<a href=\"articles.php{$baseParams}page={$i}\" class=\"{$cls}\">{$i}</a>";
        }
        if ($page < $totalPages) {
          echo "<a href=\"articles.php{$baseParams}page=" . ($page+1) . "&\">&raquo;</a>";
        }
        echo '</div></div>';
      }
    ?>
  </div>
</section>

<?php
  require('./includes/footer.inc.php');
?>
