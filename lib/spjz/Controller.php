<?php

namespace spjz;

use D3R\Model;
use models\Payments;

class Controller
{

    private Router $router;
    private bool $api;
    private int $page = 1;
    private int $items = 10;

    public function __construct(Router $router, bool $api = false)
    {
        $this->router = $router;
        $this->api = $api;

        $query = $this->router->getQuery();

        $predicates = [];
        $params = [];

        if (isset($query['supplier'])) {
            $predicates[] = 'supplier LIKE "%:supplier%"';
            $params['supplier'] = $query['supplier'];
        }
        if (isset($query['rating'])) {
            $predicates[] = 'rating=":rating"';
            $params['rating'] = (int) $query['rating'];
        }
        
        $where = null;
        if (count($predicates)) {
            $where = implode(' AND ', $predicates);
        }

        if (isset($query['page'])) {
            $this->page = (int) $query['page'];
        }

        if (isset($query['rows'])) {
            $this->items = (int) $query['rows'];
        }

        $this->models = Payments::findPage($this->page, $this->items, $where, $params);
        $this->total = Payments::count($where, $params);

        return $this;
    }

    public function getRender()
    {
        return $this->render;
    }

    public function getRouter()
    {
        return $this->render;
    }

    public function respond()
    {
        $render = new Render(VIEWS.($this->api ? 'api.phtml' : 'template.phtml'));

        $rows = [];
        if (is_array($this->models) && count($this->models)) {
            foreach ($this->models as $model) {
                $row = new Render(VIEWS . 'rowData.phtml');
                $row->supplier = $model->supplier;
                $row->rating = $model->cost_rating;
                $row->reference = $model->ref;
                $row->value = $model->amount;

                $rows[] = $row();
            }
            $render->rows = $rows;
            
            if ($this->total > 1) {

                $this->pages = [];

                if ($this->page > 1) {
                    $pagination = new Render(VIEWS . 'pagination.phtml');

                    $pagination->id = 'page_prev';
                    $pagination->href = '';
                    $pagination->title = 'Previous Page';
                    $pagination->label = '&lt;';

                    $pages[] = $pagination();
                }

                $i = 1;
                for ($i = $this->page; $i < $this->total && $i < $this->page + 4; $i++) {
                    $pagination = new Render(VIEWS . 'pagination.phtml');

                    $pagination->id = 'page_' . $i;
                    $pagination->href = '';
                    $pagination->title = 'Page ' . $i;
                    $pagination->label = $i;

                    if ($i === $this->page) {
                        $pagination->class = 'active';
                    }

                    $pages[] = $pagination();
                }

                if ($i < $this->total) {
                    $pagination = new Render(VIEWS . 'pagination.phtml');

                    $pagination->id = 'page_next';
                    $pagination->href = '';
                    $pagination->title = 'Next Page';
                    $pagination->label = '&gt;';

                    $pages[] = $pagination();
                }   
            }
            
            $render->pagination = $pages;

            echo $render();
        }
    }

}