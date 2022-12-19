<?php
namespace spjz;

use RuntimeException;

class Render
{

    protected $layout;
    protected $key;
    protected $vars = [];

    public function __construct($view = null, $key = 'content')
    {
       $this->view = $view;
       $this->key = $key;
    }

    protected function render($filename)
    {
        if (!file_exists($filename)) {
            throw new RuntimeException('View does not exist: ' . $filename);
        }

        ob_start();
        include $filename;
        return ob_get_clean();
    }

    public function __get($name)
    {
        if(!isset($this->vars[$name])){
            return;
        }

        return $this->vars[$name];
    }

    public function __set($name, $value)
    {
        $this->vars[$name] = $value;
    }

    public function __invoke($view = null)
    {
        if ($view) {
            $this->vars[$this->key] = $this->render($view);
        }

        return $this->render($view ?: $this->view);
    }

    public function __call($name, $arguments)
    {
        if(!isset($this->vars[$name])){
            return;
        }

        if(!is_callable($this->vars[$name])){
            return;
        }

        $func = $this->vars[$name];
        return call_user_func_array($func, $arguments);
    }

}