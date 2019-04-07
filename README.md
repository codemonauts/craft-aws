# AWS Plugin for Craft CMS 3.x

![Icon](resources/aws.png)

Let's run Craft CMS in a high-available AWS infrastructure with Cloudfront, S3, Loadbalancer, Auto Scaling, ElastcCache, RDS and much more.

## Background

If you want to run Craft CMS the way the AWS infrastructure enables you to, you'll quickly face some difficulties.

This plugin solves some of the worst challanges runnning Craft CMS 3 with Cloudfront, ALB and Auto Scaling.

These challenges are:
* Storing and distribution of the **control panel resources** through a bucket without the use of a shared file system (like EFS for example).
* Storing and distribution of **thumbnails** through a bucket. This avoids that every thumbnail is recreated on every instance.
* Detection of Cloudfront-Requests and using the included **device detection** to replace the Craft CMS function isMobileBrowser() (which does not work behind Cloudfront anymore).

The wiki also gives you some help with sample configurations and tips for the most important questions about running Craft CMS in an AWS environment.

Everybody who would like to contribute their experience and know-how is welcome.

## Requirements

 * Craft CMS >= 3.0.0

## Installation

Open your terminal and go to your Craft project:

``` shell
cd /path/to/project
composer require codemonauts/craft-aws
./craft install/plugin aws
```

## Usage

[...]

With ❤ by [codemonauts](https://codemonauts.com)
