<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Login</title>
        <link rel="stylesheet" href="<?= $assetsPath ;?>/styles.css"  />
    </head>
    <body class="login">
        <div class="container" style="padding-top:50px; padding-bottom: 50px">
            <div class="row">
                <div class="col-md-4 col-md-offset-4 col-sm-8 col-sm-offset-2 col-xs-10 col-xs-offset-1">
                    <form method="post">
                        <div class="panel panel-default login-box">
                            <div class="panel-body">
                                <h3 class="text-center">Login</h3>

                                <?php foreach( $messages as $type => $message){ ?>
                                    <div class="alert alert-<?= $type ;?>"><?= $message ;?></div>
                                <?php } ?>

                                <?php if( config_item('csrf_protection') === TRUE) { ?>

                                    <?php
                                        $csrf = array(
                                            'name' => $this->security->get_csrf_token_name(),
                                            'hash' => $this->security->get_csrf_hash()
                                        );
                                    ?>

                                    <input type="hidden" name="<?= $csrf['name'] ;?>" value="<?= $csrf['hash'] ;?>" />

                                <?php } ?>

                                <div class="form-group">
                                    <input type="email" name="email" placeholder="Email" class="form-control" required />
                                </div>
                                <div class="form-group">
                                    <input type="password" name="password" placeholder="Password" class="form-control" required />
                                </div>
                                <?php if( config_item('simpleauth_enable_remember_me') == true){ ?>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="remember_me" id="" /> Remember me
                                    </label>
                                </div>
                                <?php } ?>
                                <button type="submit" class="btn btn-primary btn-block">Enter</button>
                                <hr />
                                <div class="form-group text-center">
                                    <a href="<?= route('password_reset') ;?>">
                                        Forgotten password?
                                    </a>
                                </div>
                                <div class="form-group text-center">
                                    <a href="<?= route('signup') ;?>">
                                        Not user? Register now
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>