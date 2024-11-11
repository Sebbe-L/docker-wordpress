<!DOCTYPE html>
<html lang="sv">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
</head>

<body>
    <header>
        <h1>Gains4u</h1>
        <nav>
            <?php wp_nav_menu(); ?>
        </nav>
    </header>