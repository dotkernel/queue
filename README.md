# Queue

> [!IMPORTANT]
> Dotkernel component used to queue tasks to be processed asynchronously based on [netglue/laminas-messenger](https://github.com/netglue/laminas-messenger)

 A queue system is a vital component in modern web applications that enables the decoupling of certain tasks from the regular request-response cycle.

 This is especially useful for time-consuming and resource-intensive operations which are thus handled asynchronously by background workers on a separate system.

The greatest benefit is to application responsiveness which allows faster execution, while the heavy lifting is scheduled in the queue based on available resources.

 The queue system uses logs to ensure maintainability and implements retry features for reliability and stability.

<img width="641" height="481" alt="Queue process" src="https://github.com/user-attachments/assets/8eb60c02-4e3a-4a88-b3ff-811d0410337b" />



## Badges

![OSS Lifecycle](https://img.shields.io/osslifecycle/dotkernel/queue)
![PHP from Packagist (specify version)](https://img.shields.io/packagist/php-v/dotkernel/queue/1.0)

[![GitHub issues](https://img.shields.io/github/issues/dotkernel/queue)](https://github.com/dotkernel/queue/issues)
[![GitHub forks](https://img.shields.io/github/forks/dotkernel/queue)](https://github.com/dotkernel/queue/network)
[![GitHub stars](https://img.shields.io/github/stars/dotkernel/queue)](https://github.com/dotkernel/queue/stargazers)
[![GitHub license](https://img.shields.io/github/license/dotkernel/queue)](https://github.com/dotkernel/queue/blob/1.0/LICENSE.md)

[![Build Status](https://github.com/mezzio/mezzio-skeleton/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/mezzio/mezzio-skeleton/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/dotkernel/queue/graph/badge.svg?token=pexSf4wIhc)](https://codecov.io/gh/dotkernel/queue)
[![Qodana](https://github.com/dotkernel/queue/actions/workflows/qodana_code_quality.yml/badge.svg?branch=main)](https://github.com/dotkernel/queue/actions/workflows/qodana_code_quality.yml)
[![PHPStan](https://github.com/dotkernel/queue/actions/workflows/static-analysis.yml/badge.svg?branch=main)](https://github.com/dotkernel/queue/actions/workflows/static-analysis.yml)


## Installation

> Until we have a compiled documentation, read the files from /doc/book/v1 folder

## Documentation

Documentation is available at: https://docs.dotkernel.org/queue-documentation
