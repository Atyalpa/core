# Atyalpa Core

[![Latest Stable Version](https://img.shields.io/packagist/v/atyalpa/core)](https://packagist.org/packages/atyalpa/core)
[![Tests](https://github.com/Atyalpa/http/actions/workflows/php.yml/badge.svg)](https://github.com/Atyalpa/http/actions/workflows/php.yml)

`atyalpa/core` is a core component for `atyalpa/atyalpa` framework. It is responsible to handle incoming request,
fetching the approriate route for the request, then passing the request via middlewares if any, then finally to the
controller or closure based on the route's definition.
