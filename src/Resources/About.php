<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Welcome to Luthier CI!</title>
        <link href="http://fonts.googleapis.com/css?family=Open+Sans:300,regular" rel="stylesheet" type="text/css">
        <style>
            body
            {
                background: white;
                margin: 0;
                padding: 0;
                font-family: "Open Sans", "Arial", "Helvetica", sans-serif
            }
            h1
            {
                font-weight: 300;
                font-size: 50px;
                margin: 0;
            }
            .container
            {
                padding-top: 100px;
                text-align: center;
                display: block;
                margin: 0px auto;
            }
            .version
            {
                color: silver;
            }
            a
            {
                text-decoration: none;
                color: #ed6113;
            }
            a:hover, a:focus
            {
                text-decoration: underline;
                color: #D15310;
            }

        </style>
</head>
    <body>
        <div class="container">
            <img src="data:image/png;base64,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" width="100" height="100" alt="Luthier-CI" class="logo" />

            <h1>Welcome to Luthier CI</h1>
            <p class="version">Version <?= LUTHIER_CI_VERSION ;?></p>
            <p class="links">
                <a href="https://github.com/ingeniasoftware/luthier-ci">GitHub</a> &middot;
                <a href="https://luthier.ingenia.me/ci/en/docs">Documentation</a>
                <a href="https://luthier.ingenia.me/ci/es/docs">(Español)</a> &middot;
                <a href="https://forum.codeigniter.com/thread-70497.html">Help</a>
            </p>

            <?php if(route_exists('login') && class_exists('Auth')){ ?>
            <p>
                Auth:

                <?php if( Auth::isGuest() ){ ?>

                    <a href="<?= route('login') ;?>">Login</a>

                    <?php if(route_exists('signup')){ ?>
                        &middot;
                        <a href="<?= route('signup') ;?>">Sign up</a>
                    <?php }?>

                <?php } else {  ?>

                    <form method="post" action="<?= route('logout') ;?>">
                        <?php if( config_item('csrf_protection') === TRUE) { ?>

                            <?php
                                $csrf = array(
                                    'name' => ci()->security->get_csrf_token_name(),
                                    'hash' => ci()->security->get_csrf_hash()
                                );
                            ?>

                            <input type="hidden" name="<?= $csrf['name'] ;?>" value="<?= $csrf['hash'] ;?>" />

                        <?php } ?>

                        <input type="submit" value="Log out" />
                    </form>

                <?php }?>
            </p>
            <?php } ?>
        </div>
    </body>
</html>


