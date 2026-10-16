<?php

namespace App\Console\Commands;

use Mcamara\LaravelLocalization\Commands\RouteTranslationsListCommand as BaseRouteTranslationsListCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'route:trans:list')]
class RouteTranslationsListCommand extends BaseRouteTranslationsListCommand
{
    protected $signature = 'route:trans:list
                    {locale : The locale to list routes for}
                    {--json : Output the route list as JSON}
                    {--method= : Filter the routes by method}
                    {--action= : Filter the routes by action}
                    {--name= : Filter the routes by name}
                    {--domain= : Filter the routes by domain}
                    {--middleware= : Filter the routes by middleware}
                    {--path= : Only show routes matching the given path pattern}
                    {--except-path= : Do not display routes matching the given path pattern}
                    {--r|reverse : Reverse the ordering of the routes}
                    {--sort=uri : The column (domain, method, uri, name, action, middleware, definition) to sort by}
                    {--except-vendor : Do not display routes defined by vendor packages}
                    {--only-vendor : Only display routes defined by vendor packages}';
}
