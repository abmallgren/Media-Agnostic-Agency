<?php
// Front controller: serve the built React app from dist
readfile(__DIR__ . '/dist/index.html');