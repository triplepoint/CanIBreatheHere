# Can I Breathe Here
master: [![Build Status](https://travis-ci.org/triplepoint/CanIBreatheHere.png?branch=master)](https://travis-ci.org/triplepoint/CanIBreatheHere)

## Introduction
Single-purpose site that answers the question "Can I breathe here?".
- See the site at http://canibreathehere.com/ .

## Installation
First, install Composer:

``` bash
cd {path_to_project_root}
php -r "eval('?>'.file_get_contents('https://getcomposer.org/installer'));"
```
For development, install with the dev dependencies:

``` bash
./composer.phar install --verbose --prefer-dist --dev
```
For production, leave out the dev dependencies and optimize the autoloader:

``` bash
./composer.phar install --verbose --prefer-dist -o
```

## Testing and Development
### Continuous Integration
Continuous integration is handled through Travis-CI.
- https://travis-ci.org/triplepoint/CanIBreatheHere

### Unit Tests
All the tests associated with this project can be manually run with:

``` bash
vendor/bin/phpunit -c ./tests/phpunit.xml.dist ./tests
```

### CodeSniffer
Codesniffer verifies that coding standards are being met.  Once the project is build with development dependencies, you can run the checks with:

``` bash
vendor/bin/phpcs --encoding=utf-8 --extensions=php --standard=./tests/phpcs.xml -nsp ./
```
