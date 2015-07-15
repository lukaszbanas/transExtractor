<?php
include ( '../' . 'app.php' );

$urlPath = '';
$secondUrlPath = '';
$translationFile = '';

if(isset($_POST['process'])) {

    $urlPath = (isset($_POST['urlPath']) ? $_POST['urlPath'] : '');
    $secondUrlPath = (isset($_POST['secondUrlPath']) ? $_POST['secondUrlPath'] : '');
    $translationFile = (isset($_POST['current']) ? $_POST['current'] : '');

    try {
        $extractor = new TransExtractor();
        $extractor->setUrlPath($urlPath);
        $extractor->setSecondUrlPath($secondUrlPath);
        $extractor->setInputCsv($translationFile);

        if(isset($_POST['process'])) {
            $extractor->process();
        }

    } catch (\Exception $e) {
        echo('<p class="alert alert-danger">Error during processing. ' . $e->getMessage() . '</p>');
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Translation Extractor</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
</head>
<body>

<div class="container">
    <div class="row">
        <div class="page-header">
            <h1><a href="<?php echo $_SERVER['PHP_SELF'] ?>">Translation extractor</a></h1>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 col-md-offset-4">
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <p class="input-group">
                    <label for="urlPath">Ścieżka do wyodrębnienia tłumaczeń: <code>../../tonatuszu/app/code/local/Tonatuszu/Wishlist</code></label>
                    <input required="required" class="form-control" type="text" name="urlPath" id="urlPath" value="<?php echo $urlPath; ?>" />
                </p>
                <p class="input-group">
                    <label for="secondUrlPath">Ścieżka do wyodrębnienia tłumaczeń (dodatkowa): <code>../../tonatuszu/app/code/local/Tonatuszu/Wishlist</code></label>
                    <input class="form-control" type="text" name="secondUrlPath" id="secondUrlPath" value="<?php echo $secondUrlPath; ?>" />
                </p>
                <p class="input-group">
                    <label for="current">Ścieżka zawierająca aktualne tłumacznia: <code>../../tonatuszu/app/locale/pl_PL/Mage_Wishlist.csv</code>
                    <input class="form-control" type="text" name="current" id="current" value="<?php echo $translationFile; ?>" />
                </p>
                <p class="input-group">
                    <input type="submit" name="process" value="Rozpocznij konwersję" class="btn btn-block btn-primary" />
                </p>
            </form>

        </div>
    </div>
</div>
<div class="jumbotron" style="margin-top:50px;">

    <div class="container">
        <div class="row">
            <div class="">
                <ul class="list-group">
                    <?php foreach (\TransExtractor::getFilelist() as $file) : ?>
                        <li class="list-group-item col-md-4">
                            <a href="<?php echo $file; ?>"><?php echo pathinfo($file, PATHINFO_FILENAME); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
</body>
</html>
