## IntraLibrary-PHP

### Changes in 2.0:

- PHP 5.3 required
- composer required
- all classes under 'IntraLibrary' namespace

### TODO:

- rewrite a sword client, or move the swordapp-php-library into a composer.json dependency
- use dependency injection for Configuration, rather than a global state
- rewrite SRWResponse.php (and relevant classes) to better indicate difference between Metadata/Catalog IDs and intraLibrary internal package IDs
