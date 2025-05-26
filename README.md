# `phuntime/phuntime-aws`

PHP Layer for AWS Lambda.

# Known issues

## [FPM Version] Sending static content not possible

php-fpm is designed to execute PHP scripts, and there is no http server provided to handle static files. You need to 
use CDN or write some passthrough routines in your app.

## [FPM Version] single front-controller file

So far, only one front-controller file can be configured to handle the requests, yet. So you cannot e.g. run WordPress. 
This will be addressed later when the project will be more stable.