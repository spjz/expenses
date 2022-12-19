<?php
namespace spjz;

class Router
{
    private string $url;
    private string $route;
    private array $path = [];
    private array $query = [];

    public function __construct()
    {
        $this->url = $_SERVER['REQUEST_URI'];
        $this->route = strtok($this->url, '?');
        parse_str(parse_url($this->url, PHP_URL_QUERY), $this->query);
        $this->path = explode('/', $this->route);
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getPath()
    {
        return $this->path;
    }

}