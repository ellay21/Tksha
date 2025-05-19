<?php
header("Access-Control-Allow-Origin: *");
header('Location: https://petstore.swagger.io/?url=' . urlencode('https://yourdomain.com/swagger.yaml'));
exit();
